<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    protected $fillable = ['name', 'slug', 'plan_override'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot(['role', 'joined_at']);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentPremiumSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('provider', 'mercadopago')
            ->where('plan_code', 'premium')
            ->where('current_period_end', '>', now())
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere(fn ($q) => $q->where('status', 'past_due')->where('grace_until', '>', now()));
            })
            ->latest('current_period_end')
            ->first();
    }

    public function planCode(): string
    {
        if (in_array($this->plan_override, ['free', 'premium'], true)) {
            return $this->plan_override;
        }

        return $this->currentPremiumSubscription() ? 'premium' : 'free';
    }
}
