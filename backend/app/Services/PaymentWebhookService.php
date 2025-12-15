<?php
// V-PHASE3-1730-081 (Created) | V-FINAL-1730-338 | V-FINAL-1730-454 (Idempotent) | V-AUDIT-FIX-MODULE8 (Race Condition Fix)

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Jobs\SendPaymentFailedEmailJob;
use App\Notifications\PaymentFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; // Added for Atomic Locks

/**
 * PaymentWebhookService - Razorpay Webhook Event Handler & Fulfillment Core
 *
 * Processes incoming webhook events from Razorpay AND handles payment verification
 * from the Controller to ensure a single source of truth for payment fulfillment.
 *
 * ## CRITICAL SECURITY FIX: Race Condition Prevention
 * previously, the Controller (verify) and Webhook (payment.captured) could run
 * simultaneously. If verify finished first, the webhook would see the payment as
 * 'paid' and skip the fulfillment logic (dispatching jobs), resulting in missing investments.
 *
 * **The Fix:**
 * 1. `fulfillPayment` is now PUBLIC and used by both Controller and Webhook.
 * 2. It uses `Cache::lock` to ensure atomic execution.
 * 3. It checks `status !== 'paid'` *inside* the lock.
 *
 * @package App\Services
 */
class PaymentWebhookService
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Handle a standard one-time payment success via Webhook.
     */
    public function handleSuccessfulPayment(array $payload)
    {
        $orderId = $payload['order_id'] ?? null;
        $paymentId = $payload['id'] ?? null;

        // Note: We don't return early here based on simple existence check anymore.
        // We let fulfillPayment handle the idempotency safely with locks.

        $payment = Payment::where('gateway_order_id', $orderId)->first();

        if ($payment) {
            // MODULE 8 FIX: Call the shared, locked fulfillment method
            $this->fulfillPayment($payment, $paymentId);
        } else {
            Log::warning("Payment record not found for order: $orderId");
        }
    }

    /**
     * Handle a Recurring Subscription Charge (Auto-Debit) via Webhook.
     */
    public function handleSubscriptionCharged(array $payload)
    {
        $subscriptionId = $payload['subscription_id'];
        $paymentId = $payload['payment_id'];
        $amount = $payload['amount'] / 100;

        // Check for duplicate processing based on Gateway Payment ID
        if (Payment::where('gateway_payment_id', $paymentId)->exists()) {
            Log::info("Duplicate subscription.charged webhook: $paymentId already processed. Skipping.");
            return;
        }

        $subscription = Subscription::where('razorpay_subscription_id', $subscriptionId)->first();
        if (!$subscription) {
            Log::error("Recurring payment received for unknown subscription: $subscriptionId");
            return;
        }

        // Create the payment record for this new charge
        $payment = Payment::create([
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'status' => 'pending', 
            'gateway' => 'razorpay_auto',
            'gateway_payment_id' => $paymentId,
            'gateway_order_id' => $subscriptionId,
            'paid_at' => now(),
            'is_on_time' => true,
        ]);

        // MODULE 8 FIX: Fulfill securely
        $this->fulfillPayment($payment, $paymentId);
    }

    /**
     * Handle Payment Failure.
     */
    public function handleFailedPayment(array $payload)
    {
        $orderId = $payload['order_id'] ?? null;
        $description = $payload['error_description'] ?? 'Payment Failed';

        if ($orderId) {
            $payment = Payment::where('gateway_order_id', $orderId)->first();
            if ($payment && $payment->status === 'pending') {
                $payment->update(['status' => 'failed']);
                SendPaymentFailedEmailJob::dispatch($payment, $description);
                if ($payment->user) {
                    $payment->user->notify(new PaymentFailed($payment->amount, $description));
                }
                Log::info("Payment {$payment->id} marked as failed: $description");
            }
        }
    }
    
    /**
     * Handle Refund Processed
     */
    public function handleRefundProcessed(array $payload)
    {
        $paymentId = $payload['payment_id'];
        $payment = Payment::where('gateway_payment_id', $paymentId)->first();

        if ($payment && $payment->status !== 'refunded') {
            $payment->update(['status' => 'refunded']);
            Log::info("Payment {$payment->id} marked as refunded via webhook.");
        }
    }

    /**
     * Fulfill a successful payment.
     * * MODULE 8 FIX: This method is now public and concurrency-safe.
     * It uses an atomic lock to prevent race conditions between the 
     * Controller (user verification) and Webhook (server verification).
     *
     * @param Payment $payment
     * @param string $gatewayPaymentId
     * @return bool True if fulfilled, False if already fulfilled
     */
    public function fulfillPayment(Payment $payment, string $gatewayPaymentId): bool
    {
        // 1. Acquire Atomic Lock (10 seconds)
        // This prevents the Controller and Webhook from running this logic simultaneously
        $lock = Cache::lock("payment_fulfillment_{$payment->id}", 10);

        try {
            // Blocking wait for 5 seconds to acquire lock
            if ($lock->block(5)) {
                
                // 2. Re-check Status INSIDE the lock
                // If the other process finished, status will now be 'paid'.
                $payment->refresh();
                if ($payment->status === 'paid') {
                    Log::info("Payment {$payment->id} already fulfilled. Skipping.");
                    return false;
                }

                // 3. Perform Fulfillment Transaction
                DB::transaction(function () use ($payment, $gatewayPaymentId) {
                    $payment->update([
                        'status' => 'paid',
                        'gateway_payment_id' => $gatewayPaymentId,
                        'paid_at' => now(),
                        'is_on_time' => $this->checkIfOnTime($payment->subscription),
                    ]);

                    $sub = $payment->subscription;

                    if ($sub->status === 'pending') {
                        $sub->status = 'active';
                        Log::info("Subscription #{$sub->id} activated after first payment");
                    }

                    // Update Next Payment Date Logic
                    $sub->next_payment_date = $sub->next_payment_date->addMonth();
                    if ($payment->is_on_time) {
                        $sub->increment('consecutive_payments_count');
                    } else {
                        $sub->consecutive_payments_count = 0;
                    }
                    $sub->save();

                    // 4. Dispatch Critical Business Logic
                    // (Allocating shares, calculating bonuses)
                    ProcessSuccessfulPaymentJob::dispatch($payment);
                });

                Log::info("Payment {$payment->id} fulfilled successfully.");
                return true;
            } else {
                Log::warning("Could not acquire lock for payment {$payment->id}");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Fulfillment Error Payment {$payment->id}: " . $e->getMessage());
            throw $e;
        } finally {
            $lock->release();
        }
    }

    private function checkIfOnTime(Subscription $subscription): bool
    {
        return now()->lte($subscription->next_payment_date->addDays(setting('payment_grace_period_days', 2)));
    }
}