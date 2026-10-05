<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JciPasswordRecoveryOtp extends Notification
{
    use Queueable;

    public function __construct(private readonly string $code)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your JCISTEM password recovery code')
            ->greeting('Hello, '.$notifiable->name)
            ->line('Use this code to continue recovering your JCI Carmona chapter workspace account:')
            ->line($this->code)
            ->line('This code expires in 10 minutes. You can use it only once.')
            ->line('If you did not request this code, you can ignore this email.');
    }
}
