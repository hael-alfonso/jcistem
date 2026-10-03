<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class JciPasswordReset extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your JCISTEM password')
            ->greeting('Hello, '.$notifiable->name)
            ->line('A password reset was requested for your JCI Carmona chapter workspace account.')
            ->action('Reset password', $this->resetUrl($notifiable))
            ->line('This link expires in 60 minutes.')
            ->line('If you did not request this change, you can ignore this message.');
    }
}
