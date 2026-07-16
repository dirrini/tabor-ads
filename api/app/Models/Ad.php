<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ad extends Model
{
    protected $fillable = ['campaign_id', 'name', 'status', 'tracking_key', 'destination_url', 'archived_at'];

    protected $casts = ['archived_at' => 'datetime'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function impressions(): HasMany
    {
        return $this->hasMany(AdImpression::class);
    }
}
