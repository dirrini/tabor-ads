<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'email_verified_at', 'password', 'status', 'locale', 'current_workspace_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_members')
            ->withPivot(['role', 'can_create_campaigns', 'can_view_metrics', 'joined_at']);
    }

    public function oauthIdentities(): HasMany
    {
        return $this->hasMany(OauthIdentity::class);
    }

    public function currentWorkspace(): ?Workspace
    {
        if ($this->current_workspace_id) {
            $active = $this->workspaces()->where('workspaces.id', $this->current_workspace_id)->first();
            if ($active) {
                return $active;
            }
        }

        return $this->workspaces()->orderBy('workspace_members.id')->first();
    }

    public function sendEmailVerificationNotification(): void
    {
        $locale = $this->locale === 'en' ? 'en' : 'pt_BR';
        $this->notify((new VerifyEmailNotification)->locale($locale));
    }
}
