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
        $appName = config('app.name', 'TheCashFox');

        return new Envelope(
            subject: "Verify your new email address — {$appName} Admin",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-email-verification',
        );
    }
}
