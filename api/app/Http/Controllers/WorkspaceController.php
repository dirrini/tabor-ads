<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use App\Services\PlanService;
use App\Services\WorkspaceAccessService;
use App\Services\WorkspaceInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    use ResolvesWorkspace;

    public function show(Request $request, WorkspaceAccessService $access): JsonResponse
    {
        $workspace = $this->workspace($request);
        $access->assertOwner($request->user(), $workspace);
        $members = $workspace->members()->get(['users.id', 'users.name', 'users.email'])->map(fn (User $member) => [
            ...$member->only(['id', 'name', 'email']),
            'role' => $member->pivot->role,
            'can_create_campaigns' => $member->pivot->role === 'owner' || (bool) $member->pivot->can_create_campaigns,
            'can_view_metrics' => $member->pivot->role === 'owner' || (bool) $member->pivot->can_view_metrics,
        ]);
        $pendingInvitations = WorkspaceInvitation::query()
            ->where('workspace_id', $workspace->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get(['id', 'name', 'email', 'role', 'can_create_campaigns', 'can_view_metrics', 'expires_at']);

        return response()->json(['data' => [
            ...$workspace->only(['id', 'name', 'slug']), 'plan' => $workspace->planCode(),
            'limits' => config('plans.'.$workspace->planCode()),
            'members' => $members,
            'pending_invitations' => $pendingInvitations,
        ]]);
    }

    public function invite(Request $request, PlanService $plans, WorkspaceAccessService $access): JsonResponse
    {
        $workspace = $this->workspace($request);
        $access->assertOwner($request->user(), $workspace);
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'can_create_campaigns' => ['sometimes', 'boolean'],
            'can_view_metrics' => ['sometimes', 'boolean'],
            'locale' => ['nullable', 'in:pt-BR,en'],
        ]);
        $email = strtolower($data['email']);
        $existing = WorkspaceInvitation::query()
            ->where('workspace_id', $workspace->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists();
        if (! $existing) {
            $plans->assertCanInvite($workspace);
        }
        $rawToken = Str::random(64);
        $invitation = WorkspaceInvitation::updateOrCreate(
            ['workspace_id' => $workspace->id, 'email' => $email],
            [
                'invited_by' => $request->user()->id,
                'name' => isset($data['name']) ? trim($data['name']) : null,
                'role' => 'member',
                'can_create_campaigns' => $data['can_create_campaigns'] ?? true,
                'can_view_metrics' => $data['can_view_metrics'] ?? true,
                'token' => hash('sha256', $rawToken),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ]
        );
        $acceptUrl = rtrim(config('app.frontend_url'), '/').'/invite/'.$rawToken;
        $recipientLocale = User::where('email', $email)->value('locale') ?: ($data['locale'] ?? $request->user()->locale);
        $notificationLocale = $recipientLocale === 'en' ? 'en' : 'pt_BR';
        $notification = (new WorkspaceInvitationNotification(
            $workspace,
            $acceptUrl,
            $recipientLocale,
            $invitation->name,
        ))->locale($notificationLocale);
        Notification::route('mail', $invitation->email)->notify($notification);

        return response()->json(['data' => [
            ...$invitation->only(['id', 'name', 'email', 'role', 'can_create_campaigns', 'can_view_metrics', 'expires_at']),
            'status' => 'pending',
            'accept_url' => app()->isLocal() ? $acceptUrl : null,
        ]], 201);
    }

    public function accept(Request $request, string $token, WorkspaceInvitationService $invitations): JsonResponse
    {
        DB::transaction(fn () => $invitations->accept($request->user(), $invitations->findValid($token)));

        return response()->json(['message' => __('api.invitation_accepted')]);
    }

    public function updatePermissions(Request $request, User $member, WorkspaceAccessService $access): JsonResponse
    {
        $workspace = $this->workspace($request);
        $access->assertOwner($request->user(), $workspace);
        $membership = $workspace->members()->where('users.id', $member->id)->firstOrFail();
        abort_if($membership->pivot->role === 'owner', 422, __('api.owner_permissions_immutable'));
        $data = $request->validate([
            'can_create_campaigns' => ['required', 'boolean'],
            'can_view_metrics' => ['required', 'boolean'],
        ]);
        $workspace->members()->updateExistingPivot($member->id, $data);

        return response()->json(['data' => ['id' => $member->id, ...$data]]);
    }

    public function removeMember(Request $request, User $member, WorkspaceAccessService $access): JsonResponse
    {
        $workspace = $this->workspace($request);
        $access->assertOwner($request->user(), $workspace);

        DB::transaction(function () use ($workspace, $member) {
            $membership = $workspace->members()->where('users.id', $member->id)->firstOrFail();
            abort_if($membership->pivot->role === 'owner', 422, __('api.workspace_owner_cannot_be_removed'));

            $workspace->members()->detach($member->id);
            if ($member->current_workspace_id === $workspace->id) {
                $member->update(['current_workspace_id' => $member->workspaces()->value('workspaces.id')]);
            }
        });

        return response()->json(['message' => __('api.workspace_member_removed')]);
    }

    public function cancelInvitation(Request $request, WorkspaceInvitation $invitation, WorkspaceAccessService $access): JsonResponse
    {
        $workspace = $this->workspace($request);
        $access->assertOwner($request->user(), $workspace);
        abort_unless($invitation->workspace_id === $workspace->id && $invitation->accepted_at === null, 404);
        $invitation->delete();

        return response()->json(['message' => __('api.workspace_invitation_canceled')]);
    }
}
