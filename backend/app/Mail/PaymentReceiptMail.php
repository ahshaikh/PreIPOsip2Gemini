<?php
// V-FINAL-1730-208

namespace App\Mail;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Confirmation - Receipt #' . $this->payment->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment_confirmation', // We need to create this simple view
        );
    }

    public function attachments(): array
    {
        // Generate PDF in memory
        $data = [
            'payment' => $this->payment,
            'user' => $this->payment->user,
            'plan' => $this->payment->subscription->plan,
            'company' => [
                'name' => setting('site_name', 'PreIPO SIP'),
                'address' => '123 Financial District, Mumbai, India',
                'gst' => '27AABCU9603R1ZM',
                'website' => env('FRONTEND_URL')
            ]
        ];
        
        $pdf = Pdf::loadView('invoices.receipt', $data);

        return [
            Attachment::fromData(fn () => $pdf->output(), "receipt-{$this->payment->id}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}