<?php

namespace App\Services;

use App\Events\WorkspacePlanUpdated;
use App\Models\Subscription;
use Throwable;

class WorkspacePlanNotifier
{
    public function notify(Subscription $subscription): void
    {
        try {
            event(new WorkspacePlanUpdated($subscription->workspace()->firstOrFail(), $subscription));
        } catch (Throwable $error) {
            report($error);
        }
    }
}
