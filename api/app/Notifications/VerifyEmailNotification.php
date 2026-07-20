<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->locale === 'en' ? 'en' : 'pt_BR';
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())],
        );

        return (new MailMessage)
            ->subject(Lang::get('api.verification_subject', [], $locale))
            ->greeting(Lang::get('api.verification_greeting', ['name' => $notifiable->name], $locale))
            ->line(Lang::get('api.verification_line', [], $locale))
            ->action(Lang::get('api.verification_action', [], $locale), $verificationUrl)
            ->line(Lang::get('api.verification_expiration', [], $locale));
    }
}
