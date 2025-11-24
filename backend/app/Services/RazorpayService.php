<?php
// V-FINAL-1730-336 (Full Features & Testable) | V-FINAL-1730-570 (Mandate Engine)

namespace App\Services;

use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\Plan; // <-- IMPORT

/**
 * RazorpayService - Payment Gateway Integration
 *
 * Provides a clean interface to Razorpay's payment gateway API for handling
 * one-time payments, subscriptions (mandates), and refunds.
 *
 * ## Configuration
 *
 * API credentials are loaded from database settings with env fallback:
 * - `razorpay_key_id` - Public API key
 * - `razorpay_key_secret` - Secret API key
 *
 * ## Payment Flow (One-Time)
 *
 * ```
 * 1. createOrder() → Razorpay Order ID
 * 2. Frontend: Razorpay Checkout with Order ID
 * 3. Webhook: payment.captured → handleSuccessfulPayment()
 * ```
 *
 * ## Subscription/Mandate Flow (Auto-Debit)
 *
 * ```
 * 1. createOrUpdateRazorpayPlan() → Sync Plan with Razorpay
 * 2. createRazorpaySubscription() → Create mandate
 * 3. Webhook: subscription.charged → handleSubscriptionCharged()
 * ```
 *
 * ## Key Methods
 *
 * | Method                      | Purpose                                    |
 * |-----------------------------|--------------------------------------------|
 * | createOrder()               | Create one-time payment order              |
 * | createOrUpdateRazorpayPlan()| Sync local Plan with Razorpay Plans        |
 * | createRazorpaySubscription()| Create recurring mandate for user          |
 * | verifySignature()           | Validate checkout callback signature       |
 * | verifyWebhookSignature()    | Validate webhook payload authenticity      |
 * | refundPayment()             | Process full or partial refund             |
 *
 * ## Amount Conversion
 *
 * Razorpay uses **paise** (smallest currency unit). All amounts are
 * converted: `amount * 100` before sending to Razorpay.
 *
 * ## Testability
 *
 * The `setApi()` method allows injecting a mock Api instance for unit tests.
 *
 * @package App\Services
 * @see \App\Services\PaymentWebhookService
 * @link https://razorpay.com/docs/api/
 */
class RazorpayService
{
    protected $api;
    protected $key;
    protected $secret;

    public function __construct()
    {
        $this->key = setting('razorpay_key_id', env('RAZORPAY_KEY')); // Use DB setting
        $this->secret = setting('razorpay_key_secret', env('RAZORPAY_SECRET')); // Use DB setting
        
        if ($this->key && $this->secret) {
            $this->api = new Api($this->key, $this->secret);
        }
    }

    public function setApi($api) { $this->api = $api; }
    public function getApi() { return $this->api; }

    // --- ORDER MANAGEMENT ---
    public function createOrder($amount, $receipt)
    {
        $this->log("Creating Order: Amount={$amount}, Receipt={$receipt}");
        try {
            $order = $this->api->order->create([
                'receipt' => (string) $receipt,
                'amount' => $amount * 100, // Paise
                'currency' => 'INR',
                'payment_capture' => 1
            ]);
            $this->log("Order Created: {$order->id}");
            return $order;
        } catch (Exception $e) {
            $this->log("Order Creation Failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    // --- PAYMENT MANAGEMENT ---
    public function fetchPayment($paymentId) { /* ... (same as before) ... */ }
    public function capturePayment($paymentId, $amount) { /* ... (same as before) ... */ }
    public function refundPayment($paymentId, $amount = null) { /* ... (same as before) ... */ }
    public function verifySignature($attributes) { /* ... (same as before) ... */ }
    public function verifyWebhookSignature($payload, $signature, $secret) { /* ... (same as before) ... */ }

    // --- NEW: SUBSCRIPTION / MANDATE ENGINE (FSD-PAY-103) ---

    /**
     * Create or Update a Plan on Razorpay's servers.
     */
    public function createOrUpdateRazorpayPlan(Plan $plan)
    {
        $this->log("Syncing Plan #{$plan->id} with Razorpay...");
        
        $planData = [
            'period' => 'monthly',
            'interval' => 1,
            'item' => [
                'name' => $plan->name,
                'amount' => $plan->monthly_amount * 100, // Paise
                'currency' => 'INR',
                'description' => $plan->description ?? 'Monthly SIP'
            ]
        ];

        try {
            if ($plan->razorpay_plan_id) {
                // We cannot update a plan, we must create a new one.
                // This is a complex V3.0 task. For V1/V2, we assume we don't.
                $this->log("Plan {$plan->razorpay_plan_id} already exists. Skipping update.");
                return $plan->razorpay_plan_id;
            }

            $razorpayPlan = $this->api->plan->create($planData);
            $plan->update(['razorpay_plan_id' => $razorpayPlan->id]);
            
            $this->log("Razorpay Plan created: {$razorpayPlan->id}");
            return $razorpayPlan->id;

        } catch (Exception $e) {
            $this->log("Plan Sync Failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Create a new Razorpay Subscription (Mandate) for a user.
     */
    public function createRazorpaySubscription(string $razorpayPlanId, string $customerEmail, int $durationMonths)
    {
        $this->log("Creating Subscription for Plan {$razorpayPlanId}");
        
        try {
            $subscription = $this->api->subscription->create([
                'plan_id' => $razorpayPlanId,
                'customer_notify' => 1, // Let Razorpay handle notifications
                'total_count' => $durationMonths,
                'notes' => [
                    'email' => $customerEmail
                ]
            ]);
            
            $this->log("Subscription created: {$subscription->id}");
            return $subscription;

        } catch (Exception $e) {
            $this->log("Subscription Creation Failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    // --- HELPER ---
    private function log($message, $level = 'info')
    {
        Log::$level("[RazorpayService] " . $message);
    }
}