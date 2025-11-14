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
        public string $subject,
        public string $htmlContent
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlContent,
        );
    }
}