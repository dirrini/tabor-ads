<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;

class WorkspaceAccessService
{
    public function permissions(User $user, Workspace $workspace): array
    {
        $member = $workspace->members()->where('users.id', $user->id)->firstOrFail();
        $owner = $member->pivot->role === 'owner';

        return [
            'role' => $member->pivot->role,
            'owner' => $owner,
            'can_create_campaigns' => $owner || (bool) $member->pivot->can_create_campaigns,
            'can_view_metrics' => $owner || (bool) $member->pivot->can_view_metrics,
        ];
    }

    public function assertOwner(User $user, Workspace $workspace): void
    {
        abort_unless($this->permissions($user, $workspace)['owner'], 403, __('api.workspace_owner_only'));
    }

    public function assertCanCreateCampaigns(User $user, Workspace $workspace): void
    {
        abort_unless($this->permissions($user, $workspace)['can_create_campaigns'], 403, __('api.campaign_permission_required'));
    }
}
