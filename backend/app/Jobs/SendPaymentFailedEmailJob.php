<?php
// V-FINAL-1730-252

namespace App\Jobs;

use App\Mail\PaymentFailedMail;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPaymentFailedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payment $payment, public string $reason)
    {
    }

    public function handle(): void
    {
        $user = $this->payment->user;
        if ($user && $user->email) {
            Mail::to($user->email)->send(new PaymentFailedMail($this->payment, $this->reason));
        }
    }
}