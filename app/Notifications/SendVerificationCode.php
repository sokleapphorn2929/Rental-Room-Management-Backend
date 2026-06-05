<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendVerificationCode extends Notification
{
    use Queueable;

    protected string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Your Admin Login Verification Code')
                    ->line('We detected a login attempt via Google.')
                    ->line('Your Verification Code: ' . $this->code)
                    ->line('This code will expire in 10 minutes.')
                    ->line('If you did not request this, please secure your account.');
    }
}