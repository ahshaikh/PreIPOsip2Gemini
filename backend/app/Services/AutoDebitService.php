<?php
// V-FINAL-1730-339 | V-AUDIT-MODULE7-001 (Removed Simulation Code) | V-AUDIT-MODULE7-003 (Performance Fix)

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
     * V-AUDIT-MODULE7-003 (HIGH): Identify subscriptions due for payment today.
     *
     * Performance Fix: Use database-level filtering with whereDoesntHave instead of
     * loading all subscriptions into memory and filtering with PHP.
     *
     * Previous Issue:
     * - get()->filter() loaded ALL active subscriptions into memory (10k+ records = OOM)
     * - N+1 query problem: Payment::where() executed for EACH subscription in the loop
     *
     * Solution:
     * - Use Eloquent whereDoesntHave to filter at SQL level
     * - Single optimized query with LEFT JOIN
     * - Scales efficiently to millions of subscriptions
     */
    public function getDueSubscriptions()
    {
        return Subscription::where('status', 'active')
            ->where('is_auto_debit', true)
            ->whereDate('next_payment_date', '<=', now())
            // V-AUDIT-MODULE7-003: Database-level filtering instead of PHP filter()
            // Excludes subscriptions that already have a pending/paid payment for current month
            ->whereDoesntHave('payments', function ($query) {
                $query->whereIn('status', ['pending', 'paid'])
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            })
            ->get();
    }

    /**
     * V-AUDIT-MODULE7-001 (CRITICAL): Initiate the payment (Attempt Charge).
     *
     * CRITICAL FIX: Removed dangerous simulation code that was randomly marking payments
     * as success/failure using rand(). This was creating fake payment results in production.
     *
     * Now uses real Razorpay API for recurring charges against subscription.
     */
    public function attemptAutoDebit(Subscription $sub)
    {
        // Create Payment Record
        $payment = Payment::create([
            'user_id' => $sub->user_id,
            'subscription_id' => $sub->id,
            'amount' => $sub->amount ?? $sub->plan->monthly_amount, // V-AUDIT: Support custom amounts
            'status' => 'pending',
            'gateway' => 'razorpay_auto',
            'retry_count' => 0,
            'is_on_time' => true,
        ]);

        try {
            // V-AUDIT-MODULE7-001: REAL RAZORPAY CHARGE LOGIC (Simulation code REMOVED)
            // Charge the subscription using Razorpay's recurring payment API

            if (!$sub->razorpay_subscription_id) {
                throw new \Exception("Subscription not linked to Razorpay. Missing razorpay_subscription_id.");
            }

            // Initialize Razorpay API
            $api = new Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );

            // Fetch the Razorpay subscription to ensure it's active
            $razorpaySub = $api->subscription->fetch($sub->razorpay_subscription_id);

            if ($razorpaySub->status !== 'active') {
                throw new \Exception("Razorpay subscription status is '{$razorpaySub->status}', not 'active'.");
            }

            // Create invoice for this billing cycle
            // Razorpay automatically charges the customer's saved payment method
            $invoice = $api->invoice->create([
                'subscription_id' => $sub->razorpay_subscription_id,
                'type' => 'invoice',
                'description' => "SIP Payment for " . $sub->plan->name,
                'customer' => [
                    'email' => $sub->user->email,
                    'contact' => $sub->user->phone ?? '',
                ],
            ]);

            // Check if invoice was successfully paid
            if ($invoice->status === 'paid') {
                $this->handleSuccess($payment, $sub, $invoice->payment_id);
                return true;
            } else {
                throw new \Exception("Invoice created but payment not immediately captured. Status: {$invoice->status}");
            }

        } catch (\Exception $e) {
            // Log the actual error for debugging
            Log::error("Auto-debit failed for Subscription #{$sub->id}: " . $e->getMessage(), [
                'subscription_id' => $sub->id,
                'user_id' => $sub->user_id,
                'razorpay_subscription_id' => $sub->razorpay_subscription_id ?? 'N/A'
            ]);

            $this->handleFailure($payment, $sub, $e->getMessage());
            return false;
        }
    }

    /**
     * V-AUDIT-MODULE7-001 (CRITICAL): Retry Logic (Called by Job).
     *
     * CRITICAL FIX: Removed simulation code (rand()) and implemented real retry logic.
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
            // V-AUDIT-MODULE7-001: REAL RETRY LOGIC (Simulation code REMOVED)
            // Attempt to charge the Razorpay subscription again

            if (!$sub->razorpay_subscription_id) {
                throw new \Exception("Subscription not linked to Razorpay. Cannot retry.");
            }

            // Initialize Razorpay API
            $api = new Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );

            // Fetch the existing invoice or create a new one for retry
            $invoice = $api->invoice->create([
                'subscription_id' => $sub->razorpay_subscription_id,
                'type' => 'invoice',
                'description' => "SIP Payment Retry for " . $sub->plan->name,
                'customer' => [
                    'email' => $sub->user->email,
                    'contact' => $sub->user->phone ?? '',
                ],
            ]);

            // Check if invoice was successfully paid
            if ($invoice->status === 'paid') {
                $this->handleSuccess($payment, $sub, $invoice->payment_id);
                return true;
            } else {
                throw new \Exception("Retry invoice created but payment not captured. Status: {$invoice->status}");
            }

        } catch (\Exception $e) {
            // Increment retry count and log failure
            $count = $payment->retry_count + 1;
            $payment->update([
                'retry_count' => $count,
                'failure_reason' => "Retry #{$count}: " . $e->getMessage()
            ]);

            Log::warning("Retry #{$count} failed for Payment #{$payment->id}: " . $e->getMessage());

            // Schedule next retry if under max attempts
            if ($count < 3) {
                RetryAutoDebitJob::dispatch($payment)->delay(now()->addDay());
            }

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

    /**
     * V-AUDIT-MODULE7-001: Handle successful payment with real gateway payment ID.
     *
     * @param Payment $payment
     * @param Subscription $sub
     * @param string|null $gatewayPaymentId Real Razorpay payment ID (replaces random string)
     */
    private function handleSuccess(Payment $payment, Subscription $sub, ?string $gatewayPaymentId = null)
    {
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            // V-AUDIT-MODULE7-001: Use real Razorpay payment ID instead of random string
            'gateway_payment_id' => $gatewayPaymentId ?? 'AUTO-' . Str::random(10),
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