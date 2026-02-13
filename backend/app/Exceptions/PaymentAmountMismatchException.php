<?php
// V-CONTRACT-HARDENING-FINAL: Payment amount mismatch exception
// Thrown when webhook payment amount doesn't match subscription contract amount.
// This is a CRITICAL FAILURE - payment must NOT be recorded.

namespace App\Exceptions;

use Exception;

/**
 * PaymentAmountMismatchException
 *
 * Thrown when a recurring payment amount from Razorpay webhook doesn't match
 * the subscription's immutable contract amount.
 *
 * This is a contract enforcement exception - the subscription.amount is the
 * single source of truth. External payment gateway payloads are NOT trusted.
 *
 * RESPONSE PROTOCOL:
 * 1. REJECT the payment - do NOT create Payment record
 * 2. Log to financial_contract audit channel at CRITICAL level
 * 3. Alert platform administrators immediately
 * 4. Do NOT advance subscription.next_payment_date
 * 5. Do NOT dispatch bonus/allocation jobs
 *
 * ALERT LEVEL: CRITICAL
 * LOG CHANNEL: financial_contract
 */
class PaymentAmountMismatchException extends Exception
{
    protected $code = 500;

    protected int $subscriptionId;
    protected float $expectedAmount;
    protected float $webhookAmount;
    protected string $razorpaySubscriptionId;
    protected ?string $razorpayPaymentId;

    public function __construct(
        int $subscriptionId,
        float $expectedAmount,
        float $webhookAmount,
        string $razorpaySubscriptionId,
        ?string $razorpayPaymentId = null
    ) {
        $this->subscriptionId = $subscriptionId;
        $this->expectedAmount = $expectedAmount;
        $this->webhookAmount = $webhookAmount;
        $this->razorpaySubscriptionId = $razorpaySubscriptionId;
        $this->razorpayPaymentId = $razorpayPaymentId;

        parent::__construct(
            "[PAYMENT AMOUNT MISMATCH] Subscription #{$subscriptionId} expects ₹{$expectedAmount}, " .
            "but webhook sent ₹{$webhookAmount}. Razorpay subscription: {$razorpaySubscriptionId}. " .
            "Payment REJECTED. Contract enforcement active."
        );
    }

    public function getSubscriptionId(): int
    {
        return $this->subscriptionId;
    }

    public function getExpectedAmount(): float
    {
        return $this->expectedAmount;
    }

    public function getWebhookAmount(): float
    {
        return $this->webhookAmount;
    }

    public function getRazorpaySubscriptionId(): string
    {
        return $this->razorpaySubscriptionId;
    }

    public function getRazorpayPaymentId(): ?string
    {
        return $this->razorpayPaymentId;
    }

    public function getAmountDifference(): float
    {
        return abs($this->webhookAmount - $this->expectedAmount);
    }

    /**
     * V-CONTRACT-HARDENING-FINAL: Return structured context for audit logging
     */
    public function reportContext(): array
    {
        return [
            'exception_type' => 'PaymentAmountMismatchException',
            'alert_level' => 'CRITICAL',
            'subscription_id' => $this->subscriptionId,
            'expected_amount' => $this->expectedAmount,
            'webhook_amount' => $this->webhookAmount,
            'amount_difference' => $this->getAmountDifference(),
            'razorpay_subscription_id' => $this->razorpaySubscriptionId,
            'razorpay_payment_id' => $this->razorpayPaymentId,
            'action_taken' => 'Payment REJECTED - no record created',
            'action_required' => 'Investigate Razorpay subscription configuration mismatch',
            'financial_impact' => 'Payment blocked, bonus calculation prevented',
        ];
    }
}
