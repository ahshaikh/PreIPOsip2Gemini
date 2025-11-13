<?php
// V-FINAL-1730-209

namespace App\Jobs;

use App\Mail\PaymentReceiptMail;
use App\Models\Payment;
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

    public function handle(): void
    {
        $user = $this->payment->user;
        
        if ($user && $user->email) {
            Mail::to($user->email)->send(new PaymentReceiptMail($this->payment));
        }
    }
}