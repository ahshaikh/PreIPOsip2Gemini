<?php
// V-FINAL-1730-252

namespace App\Jobs;

use App\Mail\PaymentFailedMail;
use App\Models\Payment;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPaymentFailedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public string $reason
    ) {}

    public function handle(EmailService $emailService): void
    {
        $user = $this->payment->user;
        
        $variables = [
            'amount' => $this->payment->amount,
            'reason' => $this->reason,
            'retry_link' => env('FRONTEND_URL') . '/subscription'
        ];

        $emailService->send($user, 'payment.failed', $variables);
    }
}