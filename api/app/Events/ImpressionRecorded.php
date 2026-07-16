<?php

namespace App\Events;

use App\Models\AdImpression;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ImpressionRecorded implements ShouldBroadcastNow
{
    public function __construct(public AdImpression $impression, public int $count = 1) {}

    public function broadcastOn(): array
    {
        $campaign = $this->impression->ad->campaign;

        return [new PrivateChannel('workspaces.'.$campaign->workspace_id.'.campaigns.'.$campaign->id)];
    }

    public function broadcastAs(): string
    {
        return 'impression.recorded';
    }

    public function broadcastWith(): array
    {
        return [
            'campaign_id' => $this->impression->ad->campaign_id,
            'ad_id' => $this->impression->ad_id,
            'browser' => $this->impression->browser,
            'date' => $this->impression->created_at->toDateString(),
            'count' => $this->count,
        ];
    }
}
