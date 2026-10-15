<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** One branded layout for every site email: heading, paragraphs, a details table and an optional button. */
class NotificationMail extends Mailable
{
    /**
     * @param  array<int, string>  $lines
     * @param  array<string, string|null>  $details
     */
    public function __construct(
        public string $subjectLine,
        public string $heading,
        public array $lines = [],
        public array $details = [],
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
        public ?string $footnote = null,
        public ?string $replyToAddress = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
            replyTo: $this->replyToAddress ? [$this->replyToAddress] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notification', text: 'emails.notification-text');
    }
}
