<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminEmailVerification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $otp,
        public readonly string $adminName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your new email address — CashFlow Admin',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin-email-verification',
        );
    }
}
