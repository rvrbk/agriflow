<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeSetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * @param string $token
     */
    public function __construct(
        private readonly string $token,
    ) {
    }

    /**
     * @param object $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param object $notifiable
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        $url = rtrim((string) config('app.url'), '/')
            . '/set-password?token=' . urlencode($this->token)
            . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage())
            ->subject('Welcome to AgriFlow - set your password')
            ->greeting('Welcome to AgriFlow!')
            ->line('An account has been created for you.')
            ->line('Please set your password to complete your account setup.')
            ->action('Set Password', $url)
            ->line('If you were not expecting this email, you can ignore it.');
    }
}
