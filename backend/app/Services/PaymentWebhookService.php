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
     *
     * V-PAYMENT-INTEGRITY-2026: Added amount validation for one-time payments.
     * The webhook amount MUST match the original order amount.
     *
     * CORRECTION 7: Order Ownership Validation
     * - Validate gateway_order_id matches
     * - Validate payment belongs to correct user
     * - Prevents order hijacking and misbinding attacks
     */
    public function handleSuccessfulPayment(array $payload)
    {
        $orderId = $payload['order_id'] ?? null;
        $paymentId = $payload['id'] ?? null;
        $webhookAmountPaise = $payload['amount'] ?? null;

        $payment = Payment::where('gateway_order_id', $orderId)->first();

        if (!$payment) {
            Log::warning("Payment record not found for order: $orderId");
            return;
        }

        // CORRECTION 7: Order Ownership Validation
        $this->validateOrderOwnership($payment, $orderId, $paymentId, $payload);

        // V-PAYMENT-INTEGRITY-2026: Amount validation for one-time payments
        if ($webhookAmountPaise !== null) {
            $this->validateOneTimePaymentAmount($payment, $webhookAmountPaise, $paymentId);
        }

        // MODULE 8 FIX: Call the shared, locked fulfillment method
        $this->fulfillPayment($payment, $paymentId);
    }

    /**
     * CORRECTION 7: Validate order ownership to prevent order hijacking.
     *
     * This validation ensures:
     * 1. The gateway_order_id in webhook MATCHES our Payment record
     * 2. The Payment belongs to a valid user
     * 3. If subscription payment, the subscription ownership is verified
     *
     * @param Payment $payment
     * @param string|null $webhookOrderId
     * @param string|null $webhookPaymentId
     * @param array $payload Full webhook payload for logging
     * @throws \RuntimeException If ownership validation fails
     */
    private function validateOrderOwnership(
        Payment $payment,
        ?string $webhookOrderId,
        ?string $webhookPaymentId,
        array $payload
    ): void {
        // 1. Validate gateway_order_id matches exactly
        if ($payment->gateway_order_id !== $webhookOrderId) {
            Log::channel('financial_contract')->critical('ORDER OWNERSHIP VIOLATION: gateway_order_id mismatch', [
                'payment_id' => $payment->id,
                'payment_gateway_order_id' => $payment->gateway_order_id,
                'webhook_order_id' => $webhookOrderId,
                'webhook_payment_id' => $webhookPaymentId,
                'action' => 'PAYMENT_REJECTED',
                'threat' => 'POTENTIAL_ORDER_HIJACKING',
            ]);

            throw new \RuntimeException(
                "Order ownership violation: gateway_order_id mismatch. " .
                "Payment #{$payment->id} rejected. Potential order hijacking attempt."
            );
        }

        // 2. Validate payment has a valid user
        if (!$payment->user_id || !$payment->user) {
            Log::channel('financial_contract')->critical('ORDER OWNERSHIP VIOLATION: Payment has no user', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'webhook_order_id' => $webhookOrderId,
                'action' => 'PAYMENT_REJECTED',
                'threat' => 'ORPHAN_PAYMENT',
            ]);

            throw new \RuntimeException(
                "Order ownership violation: Payment #{$payment->id} has no associated user. " .
                "Cannot credit wallet for orphan payment."
            );
        }

        // 3. If this is a subscription payment, validate subscription ownership
        if ($payment->subscription_id) {
            $subscription = $payment->subscription;

            if (!$subscription) {
                Log::channel('financial_contract')->critical('ORDER OWNERSHIP VIOLATION: Subscription not found', [
                    'payment_id' => $payment->id,
                    'subscription_id' => $payment->subscription_id,
                    'webhook_order_id' => $webhookOrderId,
                    'action' => 'PAYMENT_REJECTED',
                    'threat' => 'SUBSCRIPTION_MISBINDING',
                ]);

                throw new \RuntimeException(
                    "Order ownership violation: Subscription #{$payment->subscription_id} not found. " .
                    "Payment #{$payment->id} rejected."
                );
            }

            // Verify subscription belongs to the same user as the payment
            if ($subscription->user_id !== $payment->user_id) {
                Log::channel('financial_contract')->critical('ORDER OWNERSHIP VIOLATION: User mismatch', [
                    'payment_id' => $payment->id,
                    'payment_user_id' => $payment->user_id,
                    'subscription_id' => $subscription->id,
                    'subscription_user_id' => $subscription->user_id,
                    'action' => 'PAYMENT_REJECTED',
                    'threat' => 'USER_SUBSCRIPTION_MISBINDING',
                ]);

                throw new \RuntimeException(
                    "Order ownership violation: Payment user ({$payment->user_id}) does not match " .
                    "subscription user ({$subscription->user_id}). Potential misbinding attack."
                );
            }
        }

        Log::channel('financial_contract')->debug('Order ownership validated', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'subscription_id' => $payment->subscription_id,
            'gateway_order_id' => $webhookOrderId,
            'validation' => 'PASSED',
        ]);
    }

    /**
     * V-PAYMENT-INTEGRITY-2026: Validate one-time payment amount against order.
     *
     * CORRECTION 2: NO DECIMAL FALLBACK. amount_paise MUST exist.
     *
     * V-PAYMENT-INTEGRITY-2026 HARDENING #4: Operational Tolerance Window
     * Gateways may apply minor fee adjustments (currency conversion, rounding).
     * Configurable tolerance allows operational flexibility while maintaining audit trail.
     *
     * Tolerance Policy (controlled via admin settings):
     * - `payment_amount_tolerance_paise`: Absolute tolerance in paise (default: 0 = strict)
     * - `payment_amount_tolerance_percent`: Percentage tolerance (default: 0 = strict)
     * - If EITHER tolerance is satisfied, payment is accepted with audit log
     * - Partial capture (webhook < expected) is ALWAYS rejected regardless of tolerance
     *
     * @param Payment $payment
     * @param int $webhookAmountPaise Amount from webhook in paise
     * @param string $paymentId Gateway payment ID for logging
     * @throws \RuntimeException If amounts exceed tolerance or amount_paise is null
     */
    private function validateOneTimePaymentAmount(
        Payment $payment,
        int $webhookAmountPaise,
        string $paymentId
    ): void {
        // CORRECTION 2: Use strict getter - NO FALLBACK to float conversion
        // This will throw if amount_paise is NULL
        $expectedAmountPaise = $payment->getAmountPaiseStrict();

        // Exact match - always valid
        if ($webhookAmountPaise === $expectedAmountPaise) {
            Log::channel('financial_contract')->debug('One-time payment amount validated', [
                'payment_id' => $payment->id,
                'amount_paise' => $expectedAmountPaise,
                'validation' => 'PASSED',
                'policy' => 'EXACT_MATCH',
            ]);
            return;
        }

        $differencePaise = $webhookAmountPaise - $expectedAmountPaise;
        $absDifference = abs($differencePaise);

        // HARDENING #4: Partial capture is NEVER allowed (webhook < expected)
        // This prevents accepting underpayments regardless of tolerance settings
        if ($differencePaise < 0) {
            Log::channel('financial_contract')->critical('PARTIAL CAPTURE REJECTED', [
                'payment_id' => $payment->id,
                'gateway_payment_id' => $paymentId,
                'expected_amount_paise' => $expectedAmountPaise,
                'webhook_amount_paise' => $webhookAmountPaise,
                'shortfall_paise' => $absDifference,
                'action' => 'PAYMENT_REJECTED',
                'policy' => 'PARTIAL_CAPTURE_DISALLOWED',
            ]);

            throw new \RuntimeException(
                "Partial capture rejected: expected {$expectedAmountPaise} paise, " .
                "received {$webhookAmountPaise} paise (shortfall: {$absDifference} paise). " .
                "Payment #{$payment->id} rejected. Underpayment is NOT allowed."
            );
        }

        // HARDENING #4: Check configurable tolerance for OVERPAYMENT only
        // This allows gateways to add minor fees without rejecting the payment
        $tolerancePaise = (int) setting('payment_amount_tolerance_paise', 0);
        $tolerancePercent = (float) setting('payment_amount_tolerance_percent', 0);

        // Calculate percentage tolerance in paise
        $percentTolerancePaise = $tolerancePercent > 0
            ? (int) ceil($expectedAmountPaise * ($tolerancePercent / 100))
            : 0;

        // Use the larger of the two tolerances
        $effectiveTolerance = max($tolerancePaise, $percentTolerancePaise);

        // Check if overpayment is within tolerance
        if ($differencePaise > 0 && $differencePaise <= $effectiveTolerance) {
            Log::channel('financial_contract')->info('PAYMENT AMOUNT WITHIN TOLERANCE', [
                'payment_id' => $payment->id,
                'gateway_payment_id' => $paymentId,
                'expected_amount_paise' => $expectedAmountPaise,
                'webhook_amount_paise' => $webhookAmountPaise,
                'overpayment_paise' => $differencePaise,
                'tolerance_paise' => $tolerancePaise,
                'tolerance_percent' => $tolerancePercent,
                'effective_tolerance_paise' => $effectiveTolerance,
                'validation' => 'PASSED',
                'policy' => 'TOLERANCE_APPLIED',
            ]);
            return;
        }

        // Amount exceeds tolerance - reject
        Log::channel('financial_contract')->critical('PAYMENT AMOUNT EXCEEDS TOLERANCE', [
            'payment_id' => $payment->id,
            'gateway_payment_id' => $paymentId,
            'expected_amount_paise' => $expectedAmountPaise,
            'webhook_amount_paise' => $webhookAmountPaise,
            'difference_paise' => $differencePaise,
            'tolerance_paise' => $tolerancePaise,
            'tolerance_percent' => $tolerancePercent,
            'effective_tolerance_paise' => $effectiveTolerance,
            'action' => 'PAYMENT_REJECTED',
            'policy' => 'TOLERANCE_EXCEEDED',
        ]);

        throw new \RuntimeException(
            "Payment amount mismatch: expected {$expectedAmountPaise} paise, " .
            "received {$webhookAmountPaise} paise (difference: {$differencePaise} paise, " .
            "tolerance: {$effectiveTolerance} paise). Payment #{$payment->id} rejected."
        );
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
     * V-PAYMENT-INTEGRITY-2026: Added refund amount validation and bounds checking
     *
     * CORRECTION 3: ATOMICITY GUARANTEE
     * This method uses a SINGLE outer DB::transaction() boundary.
     * AllocationService and WalletService operations participate in this
     * transaction via Laravel savepoints. NO PARTIAL COMMITS are possible.
     * If ANY step fails, the ENTIRE refund operation rolls back.
     *
     * Performs:
     * 1. Validates refund amount does not exceed credited amount
     * 2. Tracks partial vs full refunds
     * 3. Reverses share allocation (if full refund) - WITHIN transaction
     * 4. Credits wallet with refunded amount - WITHIN transaction
     * 5. Updates payment refund tracking
     *
     * CRITICAL: Refund amount cannot exceed payment amount.
     */
    public function handleRefundProcessed(array $payload)
    {
        $gatewayPaymentId = $payload['payment_id'];
        $refundAmountPaise = (int) ($payload['amount'] ?? 0);
        $refundGatewayId = $payload['refund_id'] ?? null;

        $payment = Payment::where('gateway_payment_id', $gatewayPaymentId)->first();

        if (!$payment) {
            Log::warning("Refund received for unknown payment: $gatewayPaymentId");
            return;
        }

        // V-PAYMENT-INTEGRITY-2026: Idempotency check using refund_gateway_id
        if ($refundGatewayId && $payment->refund_gateway_id === $refundGatewayId) {
            Log::info("Refund {$refundGatewayId} already processed for Payment {$payment->id}. Skipping.");
            return;
        }

        // V-PAYMENT-INTEGRITY-2026 HARDENING: Refund ONLY allowed on paid/settled
        // This prevents double reversal if refund webhook arrives AFTER chargeback confirmation
        //
        // Adversarial scenario:
        // 1. Payment captured (paid)
        // 2. Settlement webhook → paid → settled
        // 3. Chargeback initiated → settled → chargeback_pending
        // 4. Chargeback confirmed → chargeback_pending → chargeback_confirmed (TERMINAL)
        // 5. Late refund webhook arrives → MUST BE REJECTED (funds already reversed by chargeback)
        $refundableStatuses = [Payment::STATUS_PAID, Payment::STATUS_SETTLED];

        if (!in_array($payment->status, $refundableStatuses)) {
            Log::channel('financial_contract')->warning('REFUND REJECTED: Payment not in refundable state', [
                'payment_id' => $payment->id,
                'current_status' => $payment->status,
                'refund_gateway_id' => $refundGatewayId,
                'refund_amount_paise' => $refundAmountPaise,
                'reason' => 'Refund only allowed on paid/settled payments. ' .
                           'Chargeback or terminal state prevents refund.',
            ]);
            return;
        }

        // V-PAYMENT-INTEGRITY-2026: Check if payment already fully refunded
        if ($payment->status === Payment::STATUS_REFUNDED) {
            Log::info("Payment {$payment->id} already fully refunded. Skipping.");
            return;
        }

        // V-PAYMENT-INTEGRITY-2026: Validate refund amount doesn't exceed remaining
        $refundableAmountPaise = $payment->getRefundableAmountPaise();
        if ($refundAmountPaise > $refundableAmountPaise) {
            Log::critical('REFUND AMOUNT EXCEEDS PAYMENT', [
                'payment_id' => $payment->id,
                'gateway_payment_id' => $gatewayPaymentId,
                'payment_amount_paise' => $payment->amount_paise,
                'already_refunded_paise' => $payment->refund_amount_paise ?? 0,
                'refundable_amount_paise' => $refundableAmountPaise,
                'attempted_refund_paise' => $refundAmountPaise,
            ]);

            throw new \RuntimeException(
                "Refund amount ({$refundAmountPaise} paise) exceeds refundable amount " .
                "({$refundableAmountPaise} paise) for Payment #{$payment->id}"
            );
        }

        $refundAmountRupees = $refundAmountPaise / 100;
        $isFullRefund = ($refundAmountPaise === $refundableAmountPaise);

        // CRITICAL: Perform refund in a transaction
        DB::transaction(function () use (
            $payment,
            $refundAmountPaise,
            $refundAmountRupees,
            $refundGatewayId,
            $isFullRefund
        ) {
            // Update refund tracking FIRST (before any operations that might fail)
            $newTotalRefundPaise = ($payment->refund_amount_paise ?? 0) + $refundAmountPaise;
            $newStatus = $isFullRefund ? Payment::STATUS_REFUNDED : $payment->status;

            $payment->forceFill([
                'refund_amount_paise' => $newTotalRefundPaise,
                'refund_gateway_id' => $refundGatewayId,
                'refunded_at' => now(),
                'status' => $newStatus,
            ])->saveQuietly(); // Bypass state machine for refund tracking

            // 1. Reverse Share Allocation ONLY if full refund
            if ($isFullRefund) {
                $allocationService = app(AllocationService::class);
                $allocationService->reverseAllocation($payment, 'Full refund via gateway');
            }

            // 2. Credit wallet with refunded amount
            if ($refundAmountRupees > 0 && $payment->user) {
                $this->walletService->deposit(
                    $payment->user,
                    (string) $refundAmountRupees,
                    'refund',
                    ($isFullRefund ? "Full refund" : "Partial refund") . " for Payment #{$payment->id}",
                    $payment
                );

                Log::info("Wallet credited ₹{$refundAmountRupees} for refund on Payment {$payment->id}", [
                    'is_full_refund' => $isFullRefund,
                    'total_refunded_paise' => $newTotalRefundPaise,
                ]);
            }

            // 3. Reset subscription consecutive payment counter (only for full refund)
            if ($isFullRefund && $payment->subscription) {
                $subscription = $payment->subscription;
                $subscription->consecutive_payments_count = 0;
                $subscription->save();

                Log::info("Reset consecutive payment counter for Subscription #{$subscription->id}");
            }

            Log::info("Payment {$payment->id} refund processed", [
                'refund_amount_paise' => $refundAmountPaise,
                'total_refunded_paise' => $newTotalRefundPaise,
                'is_full_refund' => $isFullRefund,
                'new_status' => $newStatus,
            ]);
        });
    }

    /**
     * Fulfill a successful payment.
     *
     * V-PAYMENT-INTEGRITY-2026: ATOMIC WALLET CREDIT
     * - Wallet credit MUST happen inside the same transaction as status update
     * - ProcessSuccessfulPaymentJob dispatched AFTER transaction commits
     * - Uses afterCommit() to ensure job only runs if transaction succeeds
     *
     * MODULE 8 FIX: This method is public and concurrency-safe.
     * It uses an atomic lock to prevent race conditions between the
     * Controller (user verification) and Webhook (server verification).
     *
     * CORRECTION 4: DB-level lock is now AUTHORITATIVE.
     * Cache lock remains as secondary optimization, but SELECT FOR UPDATE
     * on Payment row is the primary concurrency guard.
     *
     * @param Payment $payment
     * @param string $gatewayPaymentId
     * @return bool True if fulfilled, False if already fulfilled
     */
    public function fulfillPayment(Payment $payment, string $gatewayPaymentId): bool
    {
        // 1. Acquire Cache Lock (10 seconds) - SECONDARY optimization
        $lock = Cache::lock("payment_fulfillment_{$payment->id}", 10);

        try {
            // Blocking wait for 5 seconds to acquire cache lock
            if ($lock->block(5)) {

                // CORRECTION 4: DB-level lock is AUTHORITATIVE
                // All status checks and transitions happen within this transaction
                // with row-level lock held via SELECT FOR UPDATE
                $fulfilled = DB::transaction(function () use ($payment, $gatewayPaymentId) {

                    // CORRECTION 4: Acquire row-level lock via SELECT FOR UPDATE
                    // This is the AUTHORITATIVE concurrency guard
                    $lockedPayment = Payment::where('id', $payment->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedPayment) {
                        Log::error("Payment {$payment->id} not found during fulfillment");
                        return false;
                    }

                    // Re-check status AFTER acquiring DB lock (not cache lock)
                    // This ensures only one process can transition to 'paid'
                    if ($lockedPayment->status === 'paid' || $lockedPayment->status === 'settled') {
                        Log::info("Payment {$payment->id} already fulfilled (status: {$lockedPayment->status}). Skipping.");
                        return false;
                    }

                    // Verify payment is in a state that can transition to paid
                    if (!in_array($lockedPayment->status, ['pending', 'processing'])) {
                        Log::warning("Payment {$payment->id} in terminal state '{$lockedPayment->status}'. Cannot fulfill.");
                        return false;
                    }

                    // Perform status transition (state machine enforced by model boot)
                    $lockedPayment->update([
                        'status' => 'paid',
                        'gateway_payment_id' => $gatewayPaymentId,
                        'paid_at' => now(),
                        'is_on_time' => $this->checkIfOnTime($lockedPayment->subscription),
                    ]);

                    // V-PAYMENT-INTEGRITY-2026 HARDENING #8: Lock subscription row
                    // Prevents race condition when two payments for same subscription arrive concurrently
                    $sub = Subscription::where('id', $lockedPayment->subscription_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$sub) {
                        Log::error("Subscription {$lockedPayment->subscription_id} not found during fulfillment");
                        throw new \RuntimeException("Subscription not found for Payment #{$lockedPayment->id}");
                    }

                    if ($sub->status === 'pending') {
                        $sub->status = 'active';
                        Log::info("Subscription #{$sub->id} activated after first payment");
                    }

                    // Update Next Payment Date Logic
                    $sub->next_payment_date = $sub->next_payment_date->addMonth();
                    if ($lockedPayment->is_on_time) {
                        $sub->increment('consecutive_payments_count');
                    } else {
                        $sub->consecutive_payments_count = 0;
                    }
                    $sub->save();

                    // V-PAYMENT-INTEGRITY-2026: CRITICAL - Wallet credit INSIDE transaction
                    // This ensures payment status and wallet credit are atomic
                    // Refresh the payment instance to reflect updates
                    $lockedPayment->refresh();
                    $this->creditWalletAtomically($lockedPayment);

                    return true;
                });

                if ($fulfilled) {
                    // Dispatch non-critical jobs AFTER transaction commits
                    // These jobs handle bonus calculation, referrals, notifications
                    // If they fail, the payment is still valid (wallet already credited)
                    $payment->refresh();
                    ProcessSuccessfulPaymentJob::dispatch($payment)->afterCommit();

                    Log::info("Payment {$payment->id} fulfilled successfully.");
                }

                return $fulfilled;
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

    /**
     * V-PAYMENT-INTEGRITY-2026: Credit wallet atomically within transaction.
     *
     * CORRECTION 2: NO DECIMAL FALLBACK. amount_paise MUST exist.
     *
     * This method MUST be called inside a DB::transaction().
     * It directly credits the wallet without dispatching a job.
     *
     * @param Payment $payment
     * @return void
     * @throws \RuntimeException If amount_paise is null or user missing
     */
    private function creditWalletAtomically(Payment $payment): void
    {
        $user = $payment->user;

        if (!$user) {
            Log::error("Cannot credit wallet: Payment #{$payment->id} has no user");
            throw new \RuntimeException("Payment #{$payment->id} has no associated user");
        }

        // Check if already credited (idempotency)
        $existingCredit = DB::table('transactions')
            ->where('reference_type', Payment::class)
            ->where('reference_id', $payment->id)
            ->where('type', 'deposit')
            ->exists();

        if ($existingCredit) {
            Log::info("Wallet already credited for Payment #{$payment->id}. Skipping.");
            return;
        }

        // CORRECTION 2: Use strict getter - NO FALLBACK to float conversion
        // This will throw if amount_paise is NULL
        $amountPaise = $payment->getAmountPaiseStrict();
        $amountRupees = $amountPaise / 100;

        $this->walletService->deposit(
            $user,
            $amountRupees,
            \App\Enums\TransactionType::DEPOSIT,
            "Payment received for SIP installment #{$payment->id}",
            $payment
        );

        Log::info("ATOMIC WALLET CREDIT: Payment #{$payment->id}: ₹{$amountRupees} credited to wallet");
    }

    /**
     * CORRECTION 5: Handle Settlement Webhook from Payment Gateway.
     *
     * Settlement is NOT the same as payment capture:
     * - `paid` = Payment captured (funds authorized/captured from customer)
     * - `settled` = Funds actually settled to merchant account by gateway
     *
     * This method enforces:
     * - settled_at ONLY set when gateway confirms settlement
     * - Proper state machine transition: paid → settled
     * - No arbitrary settlement transitions
     *
     * V-PAYMENT-INTEGRITY-2026 HARDENING #3/#5: Concurrency Symmetry
     * Uses DB::transaction() + lockForUpdate() to prevent race conditions
     * between settlement and refund webhooks arriving simultaneously.
     *
     * @param array $payload Settlement webhook payload
     */
    public function handleSettlementProcessed(array $payload): void
    {
        $gatewayPaymentId = $payload['payment_id'] ?? null;
        $settlementId = $payload['settlement_id'] ?? null;

        if (!$gatewayPaymentId || !$settlementId) {
            Log::warning('Settlement webhook missing required fields', [
                'payment_id' => $gatewayPaymentId,
                'settlement_id' => $settlementId,
            ]);
            return;
        }

        // V-PAYMENT-INTEGRITY-2026 HARDENING #3/#5: Wrap in transaction with row lock
        // This ensures settlement and refund webhooks cannot race on the same payment
        try {
            DB::transaction(function () use ($gatewayPaymentId, $settlementId) {
                // Acquire row-level lock via SELECT FOR UPDATE
                $payment = Payment::where('gateway_payment_id', $gatewayPaymentId)
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    Log::warning("Settlement received for unknown payment: $gatewayPaymentId");
                    return;
                }

                // Idempotency: Already settled (checked AFTER acquiring lock)
                if ($payment->isSettled()) {
                    Log::info("Payment {$payment->id} already settled. Skipping.", [
                        'existing_settlement_id' => $payment->settlement_id,
                        'webhook_settlement_id' => $settlementId,
                    ]);
                    return;
                }

                // Use the model's settlement method which enforces state machine
                $payment->markAsSettled($settlementId);

                Log::channel('financial_contract')->info('SETTLEMENT PROCESSED', [
                    'payment_id' => $payment->id,
                    'settlement_id' => $settlementId,
                    'user_id' => $payment->user_id,
                    'amount_paise' => $payment->amount_paise,
                ]);
            });
        } catch (\RuntimeException $e) {
            Log::channel('financial_contract')->critical('SETTLEMENT REJECTED', [
                'gateway_payment_id' => $gatewayPaymentId,
                'settlement_id' => $settlementId,
                'error' => $e->getMessage(),
            ]);
            // Don't re-throw - let webhook return success to prevent retries
        }
    }

    // =========================================================================
    // HARDENING #6: Chargeback Handlers
    // =========================================================================

    /**
     * V-PAYMENT-INTEGRITY-2026 HARDENING #6: Handle Chargeback Initiated Webhook.
     *
     * Chargebacks are BANK-INITIATED reversals, distinct from merchant refunds.
     * When a chargeback is initiated:
     * 1. Payment status transitions to chargeback_pending
     * 2. Subscription may be flagged for review
     * 3. No wallet/bonus reversal yet (awaiting final ruling)
     *
     * @param array $payload Chargeback webhook payload
     */
    public function handleChargebackInitiated(array $payload): void
    {
        $gatewayPaymentId = $payload['payment_id'] ?? null;
        $chargebackId = $payload['chargeback_id'] ?? $payload['dispute_id'] ?? null;
        $reason = $payload['reason'] ?? $payload['reason_code'] ?? 'Unknown';
        $amountPaise = isset($payload['amount']) ? (int) $payload['amount'] : null;

        if (!$gatewayPaymentId || !$chargebackId) {
            Log::warning('Chargeback webhook missing required fields', [
                'payment_id' => $gatewayPaymentId,
                'chargeback_id' => $chargebackId,
            ]);
            return;
        }

        try {
            DB::transaction(function () use ($gatewayPaymentId, $chargebackId, $reason, $amountPaise) {
                $payment = Payment::where('gateway_payment_id', $gatewayPaymentId)
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    Log::warning("Chargeback received for unknown payment: $gatewayPaymentId");
                    return;
                }

                // Idempotency: Already in chargeback process
                if ($payment->isChargebackPending() || $payment->isChargebackConfirmed()) {
                    Log::info("Payment {$payment->id} already in chargeback process. Skipping.", [
                        'current_status' => $payment->status,
                        'existing_chargeback_id' => $payment->chargeback_gateway_id,
                    ]);
                    return;
                }

                // Mark as chargeback pending
                $payment->markAsChargebackPending($chargebackId, $reason, $amountPaise);

                // Flag the subscription for review
                if ($payment->subscription) {
                    $payment->subscription->update([
                        'is_flagged' => true,
                        'flag_reason' => "Chargeback initiated on Payment #{$payment->id}: {$reason}",
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            Log::channel('financial_contract')->critical('CHARGEBACK INITIATION FAILED', [
                'gateway_payment_id' => $gatewayPaymentId,
                'chargeback_id' => $chargebackId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * V-PAYMENT-INTEGRITY-2026 HARDENING #6: Handle Chargeback Confirmed Webhook.
     *
     * Bank has ruled in customer's favor. CRITICAL ACTIONS:
     * 1. Confirm chargeback status (terminal state)
     * 2. Reverse wallet credit (debit user's wallet)
     * 3. Reverse bonus if applicable
     * 4. Reverse share allocation if applicable
     * 5. Flag subscription for suspension
     *
     * ATOMICITY GUARANTEE:
     * This method is FULLY ATOMIC. If ANY step fails, the ENTIRE chargeback
     * processing is rolled back and the webhook will retry. NO PARTIAL
     * financial reversals are allowed.
     *
     * @param array $payload Chargeback confirmation webhook payload
     * @throws \Exception Re-throws any exception to ensure webhook retry
     */
    public function handleChargebackConfirmed(array $payload): void
    {
        $gatewayPaymentId = $payload['payment_id'] ?? null;
        $chargebackId = $payload['chargeback_id'] ?? $payload['dispute_id'] ?? null;

        if (!$gatewayPaymentId) {
            Log::warning('Chargeback confirmation missing payment_id');
            return;
        }

        // ATOMICITY: No outer try/catch - let exceptions propagate for webhook retry
        DB::transaction(function () use ($gatewayPaymentId, $chargebackId) {
            $payment = Payment::where('gateway_payment_id', $gatewayPaymentId)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                Log::warning("Chargeback confirmation for unknown payment: $gatewayPaymentId");
                return;
            }

            // Idempotency: Already confirmed
            if ($payment->isChargebackConfirmed()) {
                Log::info("Payment {$payment->id} chargeback already confirmed. Skipping.");
                return;
            }

            // Confirm the chargeback
            $payment->confirmChargeback();

            $user = $payment->user;
            $chargebackAmountPaise = $payment->chargeback_amount_paise ?? $payment->amount_paise;

            // 1. Reverse wallet credit (debit user's wallet)
            // ATOMICITY: NO try/catch - failure rolls back entire chargeback
            if ($user) {
                $this->walletService->withdraw(
                    $user,
                    $chargebackAmountPaise,
                    \App\Enums\TransactionType::CHARGEBACK,
                    "Chargeback reversal for Payment #{$payment->id}",
                    $payment,
                    false, // lockBalance
                    true   // allowOverdraft - chargeback MUST succeed even if insufficient balance
                );

                Log::channel('financial_contract')->info('CHARGEBACK WALLET REVERSAL', [
                    'payment_id' => $payment->id,
                    'user_id' => $user->id,
                    'amount_paise' => $chargebackAmountPaise,
                ]);
            }

                // 2. Reverse share allocation
                $allocationService = app(AllocationService::class);
                $allocationService->reverseAllocation($payment, "Chargeback confirmed: {$payment->chargeback_reason}");

                // 3. Reverse bonuses associated with this payment
                foreach ($payment->bonuses as $bonus) {
                    if (!$bonus->is_reversed) {
                        $bonus->update([
                            'is_reversed' => true,
                            'reversed_at' => now(),
                            'reversal_reason' => 'Chargeback confirmed',
                        ]);
                    }
                }

                // 4. Suspend subscription
                if ($payment->subscription) {
                    $payment->subscription->update([
                        'status' => 'suspended',
                        'is_auto_debit' => false,
                        'flag_reason' => "Suspended due to chargeback on Payment #{$payment->id}",
                    ]);

                    Log::channel('financial_contract')->warning('SUBSCRIPTION SUSPENDED DUE TO CHARGEBACK', [
                        'subscription_id' => $payment->subscription->id,
                        'payment_id' => $payment->id,
                        'user_id' => $payment->user_id,
                    ]);
                }

                Log::channel('financial_contract')->critical('CHARGEBACK PROCESSING COMPLETE', [
                    'payment_id' => $payment->id,
                    'chargeback_id' => $chargebackId,
                    'amount_paise' => $chargebackAmountPaise,
                    'wallet_reversed' => true,
                    'allocations_reversed' => true,
                    'subscription_suspended' => $payment->subscription_id ? true : false,
                ]);
            });
        // ATOMICITY: No catch block - exceptions propagate for webhook retry
        // If ANY step fails, transaction rolls back and webhook will be retried
    }

    /**
     * V-PAYMENT-INTEGRITY-2026 HARDENING #6: Handle Chargeback Resolved Webhook.
     *
     * Bank has ruled in MERCHANT's favor. Restore payment status.
     *
     * @param array $payload Chargeback resolution webhook payload
     */
    public function handleChargebackResolved(array $payload): void
    {
        $gatewayPaymentId = $payload['payment_id'] ?? null;
        $resolution = $payload['resolution'] ?? 'merchant_favor';

        if (!$gatewayPaymentId) {
            Log::warning('Chargeback resolution missing payment_id');
            return;
        }

        if ($resolution !== 'merchant_favor' && $resolution !== 'won') {
            // Not resolved in our favor - this should be handled by handleChargebackConfirmed
            return;
        }

        try {
            DB::transaction(function () use ($gatewayPaymentId) {
                $payment = Payment::where('gateway_payment_id', $gatewayPaymentId)
                    ->lockForUpdate()
                    ->first();

                if (!$payment || !$payment->isChargebackPending()) {
                    Log::info("Chargeback resolution skipped: Payment {$gatewayPaymentId} not in pending state");
                    return;
                }

                // Determine what status to restore
                // If it was settled before chargeback, restore to settled
                $restoreStatus = $payment->settled_at ? Payment::STATUS_SETTLED : Payment::STATUS_PAID;

                $payment->resolveChargebackInFavor($restoreStatus);

                // Unflag subscription
                if ($payment->subscription) {
                    $payment->subscription->update([
                        'is_flagged' => false,
                        'flag_reason' => null,
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::channel('financial_contract')->error('CHARGEBACK RESOLUTION FAILED', [
                'gateway_payment_id' => $gatewayPaymentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function checkIfOnTime(Subscription $subscription): bool
    {
        return now()->lte($subscription->next_payment_date->addDays(setting('payment_grace_period_days', 2)));
    }
}