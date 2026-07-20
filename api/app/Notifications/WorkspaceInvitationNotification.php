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

    public function __construct(
        private Workspace $workspace,
        private string $acceptUrl,
        private string $messageLocale = 'pt-BR',
        private ?string $recipientName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $this->messageLocale === 'en' ? 'en' : 'pt_BR';

        $greeting = $this->recipientName
            ? Lang::get('api.invitation_greeting_named', ['name' => $this->recipientName], $locale)
            : Lang::get('api.invitation_greeting', [], $locale);

        return (new MailMessage)->subject(Lang::get('api.invitation_subject', ['workspace' => $this->workspace->name], $locale))
            ->greeting($greeting)
            ->line(Lang::get('api.invitation_line', [], $locale))
            ->action(Lang::get('api.invitation_action', [], $locale), $this->acceptUrl)
            ->line(Lang::get('api.invitation_expiration', [], $locale));
    }
}
