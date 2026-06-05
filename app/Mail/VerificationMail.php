<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    // 1. Rename these properties to match your HTML variables exactly!
    public string $adminName;
    public string $code;

    /**
     * Create a new message instance.
     */
    public function __construct(string $fullName, string $verificationCode)
    {
        // 2. Map the incoming arguments to the HTML template variables
        $this->adminName = $fullName;          // Maps to {{ $adminName }} in HTML
        $this->code = $verificationCode;       // Maps to {{ $code }} in HTML
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Admin Account',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verification_code', // Uses the same HTML layout view
        );
    }
}