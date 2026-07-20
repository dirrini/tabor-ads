<?php

namespace App\Events;

use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class WorkspacePlanUpdated implements ShouldBroadcastNow
{
    public function __construct(public Workspace $workspace, public Subscription $subscription) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('workspaces.'.$this->workspace->id.'.billing')];
    }

    public function broadcastAs(): string
    {
        return 'workspace.plan.updated';
    }

    public function broadcastWith(): array
    {
        $plan = $this->workspace->planCode();

        return [
            'workspace_id' => $this->workspace->id,
            'payment_id' => $this->subscription->provider_subscription_id,
            'status' => $this->subscription->status,
            'plan' => $plan,
            'limits' => config('plans.'.$plan),
        ];
    }
}
