<?php

namespace App\Mail;

use App\Models\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public Admin $admin;

    // Pass the Admin model instance to the email
    public function __construct(Admin $admin)
    {
        $this->admin = $admin;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to the Team! Admin Account Created',
        );
    }

    public function content(): Content
    {
        return new Content(
            view:'emails.welcome_admin',
        );
    }
}