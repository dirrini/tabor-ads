<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Validation\ValidationException;

class PlanService
{
    public function limits(Workspace $workspace): array
    {
        return config('plans.'.$workspace->planCode(), config('plans.free'));
    }

    public function realtime(Workspace $workspace): bool
    {
        return (bool) $this->limits($workspace)['realtime'];
    }

    public function assertCanCreateCampaign(Workspace $workspace): void
    {
        $count = $workspace->campaigns()->where('kind', 'standard')->whereNull('archived_at')->count();
        if ($count >= $this->limits($workspace)['campaigns']) {
            throw ValidationException::withMessages(['plan' => __('api.plan_campaign_limit')]);
        }
    }

    public function assertCanCreateAd(Campaign $campaign): void
    {
        $count = $campaign->ads()->whereNull('archived_at')->count();
        if ($count >= $this->limits($campaign->workspace)['ads_per_campaign']) {
            throw ValidationException::withMessages(['plan' => __('api.plan_ad_limit')]);
        }
    }

    public function assertCanInvite(Workspace $workspace): void
    {
        $members = $workspace->members()->count();
        $pending = $workspace->hasMany(WorkspaceInvitation::class)
            ->whereNull('accepted_at')->where('expires_at', '>', now())->count();
        if (($members + $pending) >= $this->limits($workspace)['members']) {
            throw ValidationException::withMessages(['plan' => __('api.plan_member_limit')]);
        }
    }

    public function assertCanAddMember(Workspace $workspace): void
    {
        if ($workspace->members()->count() >= $this->limits($workspace)['members']) {
            throw ValidationException::withMessages(['plan' => __('api.plan_member_limit')]);
        }
    }
}
