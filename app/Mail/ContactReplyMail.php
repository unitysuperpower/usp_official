<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;

class ContactReplyMail extends Mailable
{
    use SerializesModels;

    public ContactMessage $message;
    public string $subject;
    public string $replyContent;

    /**
     * Create a new message instance.
     */
    public function __construct(ContactMessage $message, string $subject, string $replyContent)
    {
        $this->message = $message;
        $this->subject = $subject;
        $this->replyContent = $replyContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-reply',
            with: [
                'originalMessage' => $this->message,
                'replyContent' => $this->replyContent,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
