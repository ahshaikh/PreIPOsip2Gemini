<?php
// V-AUDIT-FIX-DECOUPLING (Created)

namespace App\Contracts;

use App\Models\Plan;

/**
 * Interface PaymentGatewayInterface
 * * Defines the contract for payment providers to prevent vendor lock-in.
 */
interface PaymentGatewayInterface
{
    /**
     * Create a one-time payment order.
     * * @param float $amount Amount in base currency (e.g., Rupees)
     * @param string $receiptId Internal receipt reference
     * @return object|array Provider response containing order_id
     */
    public function createOrder(float $amount, string $receiptId);

    /**
     * Sync a local Plan with the gateway provider.
     * * @param Plan $plan
     * @return string The Gateway Plan ID
     */
    public function createOrUpdatePlan(Plan $plan);

    /**
     * Create a recurring subscription/mandate.
     * * @param string $gatewayPlanId The provider's plan ID
     * @param string $customerEmail User's email
     * @param int $totalCount Total number of billing cycles
     * @return object|array Provider response containing subscription_id
     */
    public function createSubscription(string $gatewayPlanId, string $customerEmail, int $totalCount);

    /**
     * Fetch payment details from provider.
     * * @param string $paymentId
     * @return mixed
     */
    public function fetchPayment(string $paymentId);

    /**
     * Refund a payment.
     * * @param string $paymentId
     * @param float|null $amount Null for full refund
     * @return mixed
     */
    public function refundPayment(string $paymentId, ?float $amount = null);

    /**
     * Verify payment signature (usually post-checkout).
     * * @param array $attributes
     * @return bool
     */
    public function verifySignature(array $attributes): bool;
}