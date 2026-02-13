<?php
// V-PHASE3-1730-081 (Created) | V-FINAL-1730-338 | V-FINAL-1730-454 (Idempotent) | V-AUDIT-FIX-MODULE8 (Race Condition Fix)
// V-CONTRACT-HARDENING-FINAL: Payment amount validation against subscription contract

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Jobs\SendPaymentFailedEmailJob;
use App\Notifications\PaymentFailed;
use App\Services\AllocationService; // V-AUDIT-MODULE4-003: For refund reversal
use App\Exceptions\PaymentAmountMismatchException;
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
     *
     * CRITICAL FIX (V-AUDIT-MODULE4-001): Fixed idempotency bug
     * Previous bug: If payment record existed but fulfillment failed, webhook retry
     * would skip processing entirely, leaving payment in 'pending' state forever.
     *
     * Fix: Check payment status before skipping:
     * - If payment exists AND is 'paid', skip (already processed)
     * - If payment exists but is 'pending', proceed with fulfillment (retry)
     * - If payment doesn't exist, create and fulfill
     *
     * V-CONTRACT-HARDENING-FINAL: Payment Amount Contract Enforcement
     * The webhook amount MUST match subscription.amount exactly.
     * If mismatch:
     * - Log CRITICAL to financial_contract channel
     * - Throw PaymentAmountMismatchException
     * - Do NOT create Payment record
     * - Do NOT advance subscription
     *
     * @throws PaymentAmountMismatchException If webhook amount doesn't match contract
     */
    public function handleSubscriptionCharged(array $payload)
    {
        $razorpaySubscriptionId = $payload['subscription_id'];
        $paymentId = $payload['payment_id'];
        $webhookAmountPaise = $payload['amount'];
        $webhookAmount = $webhookAmountPaise / 100; // Convert paise to rupees

        // CRITICAL FIX: Check for existing payment and its status
        $existingPayment = Payment::where('gateway_payment_id', $paymentId)->first();

        if ($existingPayment) {
            // If payment is already successfully processed, skip
            if ($existingPayment->status === 'paid') {
                Log::info("Duplicate subscription.charged webhook: Payment #{$existingPayment->id} already processed. Skipping.");
                return;
            }

            // If payment exists but is pending (fulfillment failed before), retry fulfillment
            if ($existingPayment->status === 'pending') {
                Log::warning("Retrying fulfillment for pending payment #{$existingPayment->id} (previous attempt failed)");
                $this->fulfillPayment($existingPayment, $paymentId);
                return;
            }

            // If payment has any other status (failed, refunded, etc.), log and skip
            Log::warning("Payment #{$existingPayment->id} has status '{$existingPayment->status}'. Skipping webhook processing.");
            return;
        }

        // Payment doesn't exist yet - fetch subscription first
        $subscription = Subscription::where('razorpay_subscription_id', $razorpaySubscriptionId)->first();
        if (!$subscription) {
            Log::error("Recurring payment received for unknown subscription: $razorpaySubscriptionId");
            return;
        }

        // V-CONTRACT-HARDENING-FINAL: Enforce payment amount matches contract
        $this->validatePaymentAmountAgainstContract(
            $subscription,
            $webhookAmount,
            $razorpaySubscriptionId,
            $paymentId
        );

        // Amount validated - safe to create the payment record
        $payment = Payment::create([
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'amount' => $webhookAmount, // Now validated against contract
            'status' => 'pending',
            'gateway' => 'razorpay_auto',
            'gateway_payment_id' => $paymentId,
            'gateway_order_id' => $razorpaySubscriptionId,
            'paid_at' => now(),
            'is_on_time' => true,
        ]);

        Log::channel('financial_contract')->info("Payment record created after amount validation", [
            'payment_id' => $payment->id,
            'subscription_id' => $subscription->id,
            'contract_amount' => (float) $subscription->amount,
            'webhook_amount' => $webhookAmount,
            'razorpay_subscription_id' => $razorpaySubscriptionId,
        ]);

        // MODULE 8 FIX: Fulfill securely with atomic lock
        $this->fulfillPayment($payment, $paymentId);
    }

    /**
     * V-CONTRACT-HARDENING-FINAL: Validate webhook payment amount against subscription contract.
     *
     * The subscription.amount is the SINGLE SOURCE OF TRUTH.
     * External payment gateway payloads are NOT trusted.
     *
     * Uses strict decimal comparison with 2-decimal precision normalization.
     *
     * @param Subscription $subscription The subscription with immutable amount
     * @param float $webhookAmount The amount received from webhook (in rupees)
     * @param string $razorpaySubscriptionId Razorpay subscription ID for logging
     * @param string $paymentId Razorpay payment ID for logging
     * @throws PaymentAmountMismatchException If amounts don't match
     */
    private function validatePaymentAmountAgainstContract(
        Subscription $subscription,
        float $webhookAmount,
        string $razorpaySubscriptionId,
        string $paymentId
    ): void {
        // Normalize both amounts to 2 decimal places for strict comparison
        $contractAmount = round((float) $subscription->amount, 2);
        $normalizedWebhookAmount = round($webhookAmount, 2);

        // Strict equality check after normalization
        if (bccomp((string) $contractAmount, (string) $normalizedWebhookAmount, 2) !== 0) {
            // Log CRITICAL before throwing
            Log::channel('financial_contract')->critical('PAYMENT AMOUNT MISMATCH - CONTRACT VIOLATION', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'plan_id' => $subscription->plan_id,
                'contract_amount' => $contractAmount,
                'webhook_amount' => $normalizedWebhookAmount,
                'amount_difference' => abs($contractAmount - $normalizedWebhookAmount),
                'razorpay_subscription_id' => $razorpaySubscriptionId,
                'razorpay_payment_id' => $paymentId,
                'action' => 'PAYMENT_REJECTED',
                'alert_level' => 'CRITICAL',
                'timestamp' => now()->toIso8601String(),
            ]);

            throw new PaymentAmountMismatchException(
                $subscription->id,
                $contractAmount,
                $normalizedWebhookAmount,
                $razorpaySubscriptionId,
                $paymentId
            );
        }

        // Log successful validation for audit trail
        Log::channel('financial_contract')->debug('Payment amount validated against contract', [
            'subscription_id' => $subscription->id,
            'contract_amount' => $contractAmount,
            'webhook_amount' => $normalizedWebhookAmount,
            'validation' => 'PASSED',
        ]);
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
     *
     * V-AUDIT-MODULE4-003 (HIGH) - Implemented Chargeback/Refund Reversal Logic
     * Previously only marked payment as refunded without reversing business operations.
     *
     * Now performs complete reversal:
     * 1. Reverses share allocation (returns units to inventory pool)
     * 2. Credits wallet with refunded amount
     * 3. Resets subscription consecutive payment counter
     * 4. Marks payment as refunded
     *
     * This ensures financial and inventory consistency after refunds/chargebacks.
     */
    public function handleRefundProcessed(array $payload)
    {
        $paymentId = $payload['payment_id'];
        $refundAmount = ($payload['amount'] ?? 0) / 100; // Convert paise to rupees

        $payment = Payment::where('gateway_payment_id', $paymentId)->first();

        if (!$payment) {
            Log::warning("Refund received for unknown payment: $paymentId");
            return;
        }

        // Skip if already refunded (idempotency)
        if ($payment->status === 'refunded') {
            Log::info("Payment {$payment->id} already refunded. Skipping.");
            return;
        }

        // CRITICAL: Perform full reversal in a transaction
        DB::transaction(function () use ($payment, $refundAmount) {

            // 1. Reverse Share Allocation (returns units to inventory pool)
            // This uses AllocationService::reverseAllocation() to undo the investment
            $allocationService = app(AllocationService::class);
            $allocationService->reverseAllocation($payment, 'Payment refunded via gateway');

            // 2. Credit wallet with refunded amount (if amount available)
            if ($refundAmount > 0 && $payment->user) {
                $this->walletService->deposit(
                    $payment->user,
                    (string) $refundAmount, // Convert to string for precision
                    'refund',
                    "Refund for Payment #{$payment->id}",
                    $payment
                );

                Log::info("Wallet credited ₹{$refundAmount} for refund on Payment {$payment->id}");
            }

            // 3. Reset subscription consecutive payment counter (if applicable)
            if ($payment->subscription) {
                $subscription = $payment->subscription;
                $subscription->consecutive_payments_count = 0;
                $subscription->save();

                Log::info("Reset consecutive payment counter for Subscription #{$subscription->id}");
            }

            // 4. Mark payment as refunded
            $payment->update(['status' => 'refunded']);

            Log::info("Payment {$payment->id} fully reversed and marked as refunded.");
        });
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