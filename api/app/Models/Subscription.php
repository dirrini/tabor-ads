<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'workspace_id', 'provider', 'provider_subscription_id', 'provider_plan_id', 'plan_code',
        'status', 'current_period_start', 'current_period_end', 'grace_until', 'canceled_at',
    ];

    protected $casts = [
        'current_period_start' => 'datetime', 'current_period_end' => 'datetime',
        'grace_until' => 'datetime', 'canceled_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
