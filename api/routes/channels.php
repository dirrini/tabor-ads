<?php

use App\Models\Campaign;
use App\Models\User;
use App\Services\PlanService;
use App\Services\WorkspaceAccessService;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('workspaces.{workspaceId}.campaigns.{campaignId}', function (User $user, int $workspaceId, int $campaignId) {
    if (! $user->hasVerifiedEmail()) {
        return false;
    }
    $workspace = $user->workspaces()->where('workspaces.id', $workspaceId)->first();
    if (! $workspace) {
        return false;
    }

    $campaign = Campaign::whereKey($campaignId)->where('workspace_id', $workspaceId)->first();
    if (! $campaign) {
        return false;
    }

    if (! app(WorkspaceAccessService::class)->permissions($user, $workspace)['can_view_metrics']) {
        return false;
    }

    return $campaign->kind === 'simulation' || app(PlanService::class)->realtime($workspace);
});

Broadcast::channel('workspaces.{workspaceId}.billing', function (User $user, int $workspaceId) {
    if (! $user->hasVerifiedEmail()) {
        return false;
    }
    $workspace = $user->workspaces()->where('workspaces.id', $workspaceId)->first();

    return $workspace && app(WorkspaceAccessService::class)->permissions($user, $workspace)['owner'];
});
