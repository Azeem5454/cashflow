<?php

namespace App\Mail;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookEmailReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Book $book,
        public readonly array $reportData,
        public readonly string $frequency,
    ) {}

    public function envelope(): Envelope
    {
        $period = $this->frequency === 'weekly' ? 'Weekly' : 'Monthly';

        return new Envelope(
            subject: "{$period} Report — {$this->book->name} | {$this->book->business->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.book-email-report',
        );
    }
}
