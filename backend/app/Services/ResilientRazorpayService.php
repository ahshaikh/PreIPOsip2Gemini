<?php
// V-PERFORMANCE-CIRCUIT-BREAKER - Razorpay service with circuit breaker protection

namespace App\Services;

use App\Services\Traits\HasCircuitBreaker;
use Illuminate\Support\Facades\Log;

/**
 * Resilient Razorpay Service with Circuit Breaker Protection
 *
 * This service wraps the RazorpayService with circuit breaker pattern to prevent
 * cascading failures when Razorpay API is down or slow.
 *
 * Usage:
 * ```php
 * $resilientRazorpay = app(ResilientRazorpayService::class);
 * $order = $resilientRazorpay->createOrder($amount, $receipt);
 * ```
 *
 * Configuration:
 * - failureThreshold: 5 failures opens circuit
 * - successThreshold: 2 successes closes circuit
 * - timeout: 120 seconds (Razorpay can be slow)
 * - halfOpenAttempts: 3 retry attempts before reopening
 *
 * Fallback Behavior:
 * - createOrder(): Returns null, caller should handle gracefully
 * - verifySignature(): Returns false (fail-safe)
 * - refundPayment(): Throws exception (critical operation, no fallback)
 */
class ResilientRazorpayService
{
    use HasCircuitBreaker;

    private RazorpayService $razorpay;

    public function __construct(RazorpayService $razorpay)
    {
        $this->razorpay = $razorpay;
    }

    /**
     * Create a Razorpay order with circuit breaker protection
     *
     * @param float $amount
     * @param string $receipt
     * @return mixed Razorpay order object or null if circuit is open
     */
    public function createOrder($amount, $receipt)
    {
        return $this->withCircuitBreaker(
            'razorpay-orders',
            fn() => $this->razorpay->createOrder($amount, $receipt),
            fallback: null,
            options: [
                'failureThreshold' => 5,
                'successThreshold' => 2,
                'timeout' => 120, // Razorpay can be slow
                'halfOpenAttempts' => 3,
            ]
        );
    }

    /**
     * Create or update Razorpay plan with circuit breaker protection
     *
     * @param mixed $plan
     * @return mixed
     */
    public function createOrUpdateRazorpayPlan($plan)
    {
        return $this->withCircuitBreaker(
            'razorpay-plans',
            fn() => $this->razorpay->createOrUpdateRazorpayPlan($plan),
            fallback: null,
            options: [
                'failureThreshold' => 5,
                'successThreshold' => 2,
                'timeout' => 120,
                'halfOpenAttempts' => 3,
            ]
        );
    }

    /**
     * Create Razorpay subscription with circuit breaker protection
     *
     * @param array $subscriptionData
     * @return mixed
     */
    public function createRazorpaySubscription(array $subscriptionData)
    {
        return $this->withCircuitBreaker(
            'razorpay-subscriptions',
            fn() => $this->razorpay->createRazorpaySubscription($subscriptionData),
            fallback: null,
            options: [
                'failureThreshold' => 5,
                'successThreshold' => 2,
                'timeout' => 120,
                'halfOpenAttempts' => 3,
            ]
        );
    }

    /**
     * Verify signature with circuit breaker protection
     *
     * Note: Signature verification is local, but we protect it in case
     * Razorpay SDK has issues
     *
     * @param array $attributes
     * @return bool
     */
    public function verifySignature(array $attributes): bool
    {
        try {
            return $this->withCircuitBreaker(
                'razorpay-verify',
                fn() => $this->razorpay->verifySignature($attributes),
                fallback: false, // Fail-safe: reject invalid signatures
                options: [
                    'failureThreshold' => 10, // More lenient for verification
                    'successThreshold' => 2,
                    'timeout' => 30,
                    'halfOpenAttempts' => 5,
                ]
            );
        } catch (\Exception $e) {
            Log::error("Razorpay signature verification failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Verify webhook signature
     *
     * @param string $webhookBody
     * @param string $signature
     * @return bool
     */
    public function verifyWebhookSignature(string $webhookBody, string $signature): bool
    {
        try {
            return $this->withCircuitBreaker(
                'razorpay-webhook-verify',
                fn() => $this->razorpay->verifyWebhookSignature($webhookBody, $signature),
                fallback: false,
                options: [
                    'failureThreshold' => 10,
                    'successThreshold' => 2,
                    'timeout' => 30,
                    'halfOpenAttempts' => 5,
                ]
            );
        } catch (\Exception $e) {
            Log::error("Razorpay webhook signature verification failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Refund payment with circuit breaker protection
     *
     * Note: No fallback for refunds - this is a critical operation
     * If Razorpay is down, the refund should fail and be retried later
     *
     * @param string $paymentId
     * @param float|null $amount
     * @return mixed
     * @throws \RuntimeException if circuit is open
     */
    public function refundPayment(string $paymentId, ?float $amount = null)
    {
        return $this->withCircuitBreaker(
            'razorpay-refunds',
            fn() => $this->razorpay->refundPayment($paymentId, $amount),
            fallback: null, // No fallback - throw exception
            options: [
                'failureThreshold' => 3, // More strict for refunds
                'successThreshold' => 2,
                'timeout' => 120,
                'halfOpenAttempts' => 2,
            ]
        );
    }

    /**
     * Fetch payment details
     *
     * @param string $paymentId
     * @return mixed
     */
    public function fetchPayment(string $paymentId)
    {
        return $this->withCircuitBreaker(
            'razorpay-fetch',
            fn() => $this->razorpay->fetchPayment($paymentId),
            fallback: null,
            options: [
                'failureThreshold' => 5,
                'successThreshold' => 2,
                'timeout' => 60,
                'halfOpenAttempts' => 3,
            ]
        );
    }

    /**
     * Get circuit breaker status for all Razorpay operations
     *
     * @return array
     */
    public function getCircuitStatus(): array
    {
        return [
            'orders' => $this->getCircuitBreakerStatus('razorpay-orders'),
            'plans' => $this->getCircuitBreakerStatus('razorpay-plans'),
            'subscriptions' => $this->getCircuitBreakerStatus('razorpay-subscriptions'),
            'verify' => $this->getCircuitBreakerStatus('razorpay-verify'),
            'refunds' => $this->getCircuitBreakerStatus('razorpay-refunds'),
            'fetch' => $this->getCircuitBreakerStatus('razorpay-fetch'),
        ];
    }

    /**
     * Reset all Razorpay circuit breakers (admin tool)
     *
     * @return void
     */
    public function resetAllCircuits(): void
    {
        $this->resetCircuitBreaker('razorpay-orders');
        $this->resetCircuitBreaker('razorpay-plans');
        $this->resetCircuitBreaker('razorpay-subscriptions');
        $this->resetCircuitBreaker('razorpay-verify');
        $this->resetCircuitBreaker('razorpay-refunds');
        $this->resetCircuitBreaker('razorpay-fetch');
        Log::info('All Razorpay circuit breakers reset');
    }

    /**
     * Pass through any other methods to underlying RazorpayService
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return $this->razorpay->$method(...$arguments);
    }
}
