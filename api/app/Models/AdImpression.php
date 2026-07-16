<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdImpression extends Model
{
    public $timestamps = false;

    protected $fillable = ['ad_id', 'source', 'ip_address', 'user_agent', 'browser', 'platform', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}
