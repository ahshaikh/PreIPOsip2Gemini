<?php
// V-FINAL-1730-339

namespace App\Services;

use App\Models\Subscription;
use App\Models\Payment;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Jobs\RetryAutoDebitJob;
use App\Jobs\SendPaymentFailedEmailJob;
use App\Jobs\SendPaymentReminderJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Razorpay\Api\Api;

class AutoDebitService
{
    /**
     * 1. Identify subscriptions due for payment today.
     */
    public function getDueSubscriptions()
    {
        return Subscription::where('status', 'active')
            ->where('is_auto_debit', true)
            ->whereDate('next_payment_date', '<=', now())
            ->get()
            ->filter(function ($sub) {
                // Filter out those who already have a pending/paid payment for this cycle
                return !Payment::where('subscription_id', $sub->id)
                    ->whereIn('status', ['pending', 'paid'])
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->exists();
            });
    }

    /**
     * 2. Initiate the payment (Attempt Charge).
     */
    public function attemptAutoDebit(Subscription $sub)
    {
        // Create Payment Record
        $payment = Payment::create([
            'user_id' => $sub->user_id,
            'subscription_id' => $sub->id,
            'amount' => $sub->plan->monthly_amount,
            'status' => 'pending',
            'gateway' => 'razorpay_auto',
            'retry_count' => 0,
            'is_on_time' => true,
        ]);

        try {
            // --- REAL RAZORPAY CHARGE LOGIC ---
            // In production, this uses the subscription/token
            // $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
            // $resp = $api->payment->createRecursion([...]);
            
            // SIMULATION for V1
            $success = (bool)rand(0, 10) > 1; // 90% success rate mock

            if ($success) {
                $this->handleSuccess($payment, $sub);
                return true;
            } else {
                throw new \Exception("Bank declined transaction (Simulation)");
            }

        } catch (\Exception $e) {
            $this->handleFailure($payment, $sub, $e->getMessage());
            return false;
        }
    }

    /**
     * 3. Retry Logic (Called by Job).
     */
    public function processRetry(Payment $payment)
    {
        if ($payment->status === 'paid') return true;

        $sub = $payment->subscription;
        
        // Max retries check
        if ($payment->retry_count >= 3) {
            $this->suspendSubscription($sub, $payment);
            return false;
        }

        try {
            // Retry Charge (Simulation)
            $success = (bool)rand(0, 10) > 3; 

            if ($success) {
                $this->handleSuccess($payment, $sub);
                return true;
            } else {
                throw new \Exception("Retry declined");
            }

        } catch (\Exception $e) {
            $count = $payment->retry_count + 1;
            $payment->update([
                'retry_count' => $count,
                'failure_reason' => "Retry #{$count}: " . $e->getMessage()
            ]);
            
            // Schedule next retry
            RetryAutoDebitJob::dispatch($payment)->delay(now()->addDay());
            
            return false;
        }
    }

    /**
     * 4. Send Reminders (3 Days Before).
     */
    public function sendReminders()
    {
        $upcomingSubs = Subscription::where('status', 'active')
            ->whereDate('next_payment_date', now()->addDays(3))
            ->get();

        foreach ($upcomingSubs as $sub) {
            SendPaymentReminderJob::dispatch($sub);
        }
        
        return $upcomingSubs->count();
    }

    /**
     * 5. Suspend Subscription (Max Failures).
     */
    public function suspendSubscription(Subscription $sub, Payment $payment)
    {
        $payment->update(['status' => 'failed']);
        
        $sub->update([
            'status' => 'payment_failed',
            'is_auto_debit' => false
        ]);

        SendPaymentFailedEmailJob::dispatch($payment, "Max retries reached. Subscription suspended.");
        Log::warning("Subscription #{$sub->id} suspended due to max retries.");
    }

    // --- HELPERS ---

    private function handleSuccess(Payment $payment, Subscription $sub)
    {
        $payment->update([
            'status' => 'paid', 
            'paid_at' => now(),
            'gateway_payment_id' => 'AUTO-' . Str::random(10),
            'failure_reason' => null
        ]);
        
        ProcessSuccessfulPaymentJob::dispatch($payment);
        $sub->update(['next_payment_date' => $sub->next_payment_date->addMonth()]);
    }

    private function handleFailure(Payment $payment, Subscription $sub, $reason)
    {
        $payment->update([
            'retry_count' => 1,
            'failure_reason' => $reason
        ]);
        
        // Schedule first retry
        RetryAutoDebitJob::dispatch($payment)->delay(now()->addDay());
        SendPaymentFailedEmailJob::dispatch($payment, "Payment failed. Retrying tomorrow.");
    }
}