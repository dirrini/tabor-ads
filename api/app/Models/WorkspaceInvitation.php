<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceInvitation extends Model
{
    protected $fillable = [
        'workspace_id', 'invited_by', 'email', 'name', 'role', 'can_create_campaigns', 'can_view_metrics',
        'token', 'expires_at', 'accepted_at',
    ];

    protected $casts = [
        'can_create_campaigns' => 'boolean',
        'can_view_metrics' => 'boolean',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
