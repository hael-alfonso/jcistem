<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

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
            ->view([
                'html' => 'emails.password-recovery-otp',
                'text' => 'emails.password-recovery-otp-text',
            ], [
                'name' => $notifiable->name,
                'code' => $this->code,
            ])
            ->withSymfonyMessage(function (Email $message): void {
                $message->addPart(
                    DataPart::fromPath(public_path('assets/images/brand/logo.png'), 'jcistem-logo.png', 'image/png')
                        ->asInline()
                        ->setContentId('jcistem-logo@jcistem.local')
                );
            });
    }
}
