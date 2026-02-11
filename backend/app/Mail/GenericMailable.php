<?php
// V-FINAL-1730-389 (Created)

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected string $customSubject,
        protected string $body
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->customSubject
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.generic',
            with: [
                'body' => $this->body,
            ],
        );
    }
}
