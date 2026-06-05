<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $adminName;

    public function __construct(string $adminName, string $code)
    {
        $this->code = $code;
        $this->adminName = $adminName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your 2FA Verification Code - Rental Management System',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification_code',
        );
    }
}