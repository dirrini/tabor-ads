<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    use ResolvesWorkspace;

    public function show(Request $request): JsonResponse
    {
        $workspace = $this->workspace($request);

        return response()->json(['data' => [
            ...$workspace->only(['id', 'name', 'slug']), 'plan' => $workspace->planCode(),
            'limits' => config('plans.'.$workspace->planCode()),
            'members' => $workspace->members()->get(['users.id', 'users.name', 'users.email']),
        ]]);
    }

    public function invite(Request $request, PlanService $plans): JsonResponse
    {
        $workspace = $this->workspace($request);
        abort_unless(in_array($this->role($request, $workspace), ['owner', 'admin'], true), 403);
        $data = $request->validate(['email' => ['required', 'email'], 'role' => ['nullable', 'in:admin,member']]);
        $plans->assertCanInvite($workspace);
        $rawToken = Str::random(64);
        $invitation = WorkspaceInvitation::updateOrCreate(
            ['workspace_id' => $workspace->id, 'email' => strtolower($data['email'])],
            ['invited_by' => $request->user()->id, 'role' => $data['role'] ?? 'member', 'token' => hash('sha256', $rawToken), 'expires_at' => now()->addDays(7), 'accepted_at' => null]
        );
        $acceptUrl = rtrim(config('app.frontend_url'), '/').'/register?invite='.$rawToken.'&email='.urlencode($invitation->email);
        Notification::route('mail', $invitation->email)->notify(new WorkspaceInvitationNotification($workspace, $acceptUrl, $request->user()->locale));

        return response()->json(['data' => [...$invitation->only(['id', 'email', 'role', 'expires_at']), 'accept_url' => app()->isLocal() ? $acceptUrl : null]], 201);
    }

    public function accept(Request $request, string $token, PlanService $plans): JsonResponse
    {
        $invitation = WorkspaceInvitation::where('token', hash('sha256', $token))->whereNull('accepted_at')->where('expires_at', '>', now())->firstOrFail();
        abort_unless(strtolower($request->user()->email) === strtolower($invitation->email), 403);
        $workspace = Workspace::findOrFail($invitation->workspace_id);
        $plans->assertCanAddMember($workspace);
        $workspace->members()->syncWithoutDetaching([$request->user()->id => ['role' => $invitation->role, 'joined_at' => now()]]);
        $invitation->update(['accepted_at' => now()]);
        $request->user()->update(['current_workspace_id' => $workspace->id]);

        return response()->json(['message' => __('api.invitation_accepted')]);
    }
}
