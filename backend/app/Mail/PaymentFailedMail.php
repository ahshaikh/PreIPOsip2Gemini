<?php
// V-FINAL-1730-251

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment, public string $reason)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action Required: Payment Failed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment_failed',
        );
    }
}