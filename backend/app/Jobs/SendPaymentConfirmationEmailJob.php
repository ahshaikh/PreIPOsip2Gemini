<?php
// V-FINAL-1730-209

namespace App\Jobs;

use App\Mail\PaymentReceiptMail;
use App\Models\Payment;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPaymentConfirmationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payment $payment)
    {
    }

    public function handle(EmailService $emailService): void
    {
        $user = $this->payment->user;
        
        $variables = [
            'amount' => $this->payment->amount,
            'date' => $this->payment->paid_at->format('d M Y'),
            'transaction_id' => $this->payment->gateway_payment_id,
            'plan_name' => $this->payment->subscription->plan->name,
        ];

        $emailService->send($user, 'payment.confirmation', $variables);
    }
}