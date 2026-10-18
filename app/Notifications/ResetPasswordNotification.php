<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(private readonly string $token)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Atur ulang password akun Namikulo Course')
            ->view([
                'html' => 'emails.password-reset',
                'text' => 'emails.password-reset-text',
            ], [
                'recipient' => $notifiable,
                'resetUrl' => $resetUrl,
                'expiresMinutes' => (int) config('auth.passwords.users.expire'),
            ]);
    }
}
