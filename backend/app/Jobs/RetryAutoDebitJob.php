<?php
// V-FINAL-1730-306

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RetryAutoDebitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payment $payment)
    {
    }

    public function handle(): void
    {
        $payment = $this->payment;
        $sub = $payment->subscription;

        if ($payment->status === 'paid') {
            return; // Already paid, stop.
        }

        Log::info("Attempting retry #{$payment->retry_count} for Payment #{$payment->id}");

        // --- SIMULATE CHARGE ATTEMPT ---
        // In a real app, you would call: $razorpay->charge($sub->mandate_id, $payment->amount)
        // We'll simulate a 50/50 chance of success for the retry
        $success = (bool)rand(0, 1); 
        
        if ($success) {
            $this->handleSuccess($payment, $sub);
        } else {
            $this->handleFailure($payment, $sub);
        }
    }

    private function handleSuccess(Payment $payment, Subscription $sub)
    {
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'gateway_payment_id' => 'RETRY-' . Str::random(10),
            'failure_reason' => null
        ]);

        // Trigger Bonuses & Allocation
        ProcessSuccessfulPaymentJob::dispatch($payment);

        // Advance subscription date
        $sub->update(['next_payment_date' => $sub->next_payment_date->addMonth()]);
        
        // Update streak (late payments might reset streak depending on business rule, 
        // but usually we keep it if it succeeds within retry window)
        // For now, we treat it as "paid" but maybe not "on time" for consistency bonus
        
        Log::info("Retry successful for Payment #{$payment->id}");
    }

    private function handleFailure(Payment $payment, Subscription $sub)
    {
        $count = $payment->retry_count + 1;
        $payment->update([
            'retry_count' => $count,
            'failure_reason' => "Retry #{$count} failed."
        ]);

        if ($count < 3) {
            // Schedule next retry for 24 hours later
            Log::info("Retry failed. Scheduling attempt #".($count+1)." for tomorrow.");
            
            RetryAutoDebitJob::dispatch($payment)
                ->delay(now()->addDay());
                
            // Notify User: "Payment failed, we will try again tomorrow."
            SendPaymentFailedEmailJob::dispatch($payment, "Auto-debit attempt #{$count} failed. Retrying tomorrow.");

        } else {
            // Max retries reached. Give up.
            Log::error("Max retries reached for Payment #{$payment->id}. Suspending subscription.");
            
            $payment->update(['status' => 'failed']);
            
            $sub->update([
                'status' => 'payment_failed', // Special status
                'is_auto_debit' => false // Disable auto-debit to prevent loops
            ]);

            // Notify User: "Subscription suspended. Please update payment method."
            SendPaymentFailedEmailJob::dispatch($payment, "Max retries reached. Your subscription has been suspended.");
        }
    }
}