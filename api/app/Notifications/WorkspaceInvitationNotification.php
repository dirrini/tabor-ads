<?php

namespace App\Notifications;

use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class WorkspaceInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private Workspace $workspace, private string $acceptUrl, private string $locale = 'pt-BR') {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $this->locale === 'en' ? 'en' : 'pt_BR';

        return (new MailMessage)->subject(Lang::get('api.invitation_subject', ['workspace' => $this->workspace->name], $locale))
            ->greeting(Lang::get('api.invitation_greeting', [], $locale))
            ->line(Lang::get('api.invitation_line', [], $locale))
            ->action(Lang::get('api.invitation_action', [], $locale), $this->acceptUrl)
            ->line(Lang::get('api.invitation_expiration', [], $locale));
    }
}
