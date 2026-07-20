<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Auth\Events\Verified;

class WorkspaceInvitationService
{
    public function __construct(private PlanService $plans) {}

    public function findValid(string $token): WorkspaceInvitation
    {
        return WorkspaceInvitation::query()
            ->where('token', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();
    }

    public function accept(User $user, WorkspaceInvitation $invitation): Workspace
    {
        abort_unless(strtolower($user->email) === strtolower($invitation->email), 403, __('api.invitation_email_mismatch'));

        $workspace = Workspace::query()->lockForUpdate()->findOrFail($invitation->workspace_id);
        $alreadyMember = $workspace->members()->where('users.id', $user->id)->exists();
        if (! $alreadyMember) {
            $this->plans->assertCanAddMember($workspace);
        }

        $workspace->members()->syncWithoutDetaching([$user->id => [
            'role' => $invitation->role,
            'can_create_campaigns' => $invitation->can_create_campaigns,
            'can_view_metrics' => $invitation->can_view_metrics,
            'joined_at' => now(),
        ]]);
        $invitation->update(['accepted_at' => now()]);
        $user->update(['current_workspace_id' => $workspace->id]);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return $workspace;
    }
}
