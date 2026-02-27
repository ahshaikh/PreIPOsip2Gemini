<?php
// V-FINAL-1730-336 (Full Features & Testable) | V-FINAL-1730-570 (Mandate Engine) | V-AUDIT-FIX-DECOUPLING

namespace App\Services;

use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\Plan;
use InvalidArgumentException;
use App\Contracts\PaymentGatewayInterface; // [AUDIT FIX]

/**
 * RazorpayService - Payment Gateway Integration
 * Implements the standard PaymentGatewayInterface.
 */
class RazorpayService implements PaymentGatewayInterface
{
    protected $api;
    protected $key;
    protected $secret;

    public function __construct()
    {
        // V-AUDIT-MODULE4-002 (HIGH) - Fixed Configuration Anti-Pattern
        // CRITICAL FIX: Use config() instead of env() to avoid null values after config:cache
        // In Laravel production, when 'php artisan config:cache' is run, all env() calls
        // outside of config files return null. This caused payment failures in production.
        // Now using config('services.razorpay.*') which properly loads cached values.
        $this->key = setting('razorpay_key_id', config('services.razorpay.key'));
        $this->secret = setting('razorpay_key_secret', config('services.razorpay.secret'));

        if ($this->key && $this->secret) {
            $this->api = new Api($this->key, $this->secret);
        }
    }

    public function setApi($api) { $this->api = $api; }
    public function getApi() { return $this->api; }

    // --- ORDER MANAGEMENT ---
    
    // [AUDIT FIX] Implements interface method
    public function createOrder(float $amount, string $receiptId)
    {
        // DEFENSIVE CHECK: Ensure Razorpay API is configured
        if (!$this->api) {
            throw new Exception("Razorpay API not configured. Please set razorpay_key_id and razorpay_key_secret in settings or .env file.");
        }

        $this->validateAmount($amount);

        $this->log("Creating Order: Amount={$amount}, Receipt={$receiptId}");
        try {
            // V-AUDIT-MODULE4-006 (MEDIUM) - Use config for currency instead of hardcoded 'INR'
            $order = $this->api->order->create([
                'receipt' => (string) $receiptId,
                'amount' => $amount * 100, // Paise
                'currency' => config('app.currency', 'INR'), // Configurable currency
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

    public function fetchPayment(string $paymentId)
    {
        $this->log("Fetching Payment: {$paymentId}");
        try {
            $payment = $this->api->payment->fetch($paymentId);
            $this->log("Payment Fetched: {$paymentId}");
            return $payment;
        } catch (Exception $e) {
            $this->log("Payment Fetch Failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    public function capturePayment($paymentId, $amount)
    {
        if ($amount) {
            $this->validateAmount($amount);
        }

        $this->log("Capturing Payment: {$paymentId}, Amount={$amount}");
        try {
            $payment = $this->api->payment->fetch($paymentId);
            $captured = $payment->capture(['amount' => $amount * 100]);
            $this->log("Payment Captured: {$paymentId}");
            return $captured;
        } catch (Exception $e) {
            $this->log("Payment Capture Failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    // [AUDIT FIX] Implements interface method
    public function refundPayment(string $paymentId, ?float $amount = null)
    {
        $this->log("Refunding Payment: {$paymentId}" . ($amount ? ", Amount={$amount}" : " (Full)"));
        try {
            $refundData = [];
            if ($amount !== null) {
                $refundData['amount'] = $amount * 100;
            }
            $refund = $this->api->payment->fetch($paymentId)->refund($refundData);
            $this->log("Refund Processed: {$refund->id}");
            return $refund;
        } catch (Exception $e) {
            $this->log("Refund Failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    // [AUDIT FIX] Implements interface method
    public function verifySignature(array $attributes): bool
    {
        $this->log("Verifying Payment Signature");
        try {
            $this->api->utility->verifyPaymentSignature($attributes);
            $this->log("Signature Verified Successfully");
            return true;
        } catch (\Throwable $e) {
            $this->log("Signature Verification Failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Verify webhook signature from Razorpay.
     *
     * @param string $payload Raw webhook payload
     * @param string $signature X-Razorpay-Signature header value
     * @param string $secret Webhook secret key
     * @return bool True if signature is valid
     * @throws Exception If signature verification fails
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $this->log("Verifying Webhook Signature");
        try {
            $this->api->utility->verifyWebhookSignature($payload, $signature, $secret);
            $this->log("Webhook Signature Verified Successfully");
            return true;
        } catch (\Throwable $e) {
            $this->log("Webhook Signature Verification Failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    // --- SUBSCRIPTION / MANDATE ENGINE ---

    /**
     * [AUDIT FIX] Renamed to match interface: createOrUpdateRazorpayPlan -> createOrUpdatePlan
     *
     * DEFENSIVE: Gracefully handles missing Razorpay configuration
     */
    public function createOrUpdatePlan(Plan $plan)
    {
        // DEFENSIVE CHECK: If Razorpay is not configured, skip sync gracefully
        if (!$this->api) {
            $this->log("Razorpay API not configured. Skipping plan sync for Plan #{$plan->id}", 'warning');
            return null; // Return null to indicate no Razorpay plan ID
        }

        $this->log("Syncing Plan #{$plan->id} with Razorpay...");

        $this->validateAmount($plan->monthly_amount);

        // V-AUDIT-MODULE4-006 (MEDIUM) - Use config for currency instead of hardcoded 'INR'
        $planData = [
            'period' => 'monthly',
            'interval' => 1,
            'item' => [
                'name' => $plan->name,
                'amount' => $plan->monthly_amount * 100,
                'currency' => config('app.currency', 'INR'), // Configurable currency
                'description' => $plan->description ?? 'Monthly SIP'
            ]
        ];

        try {
            if ($plan->razorpay_plan_id) {
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
     * [AUDIT FIX] Renamed to match interface: createRazorpaySubscription -> createSubscription
     *
     * DEFENSIVE: Ensures Razorpay API is configured
     */
    public function createSubscription(string $gatewayPlanId, string $customerEmail, int $totalCount)
    {
        // DEFENSIVE CHECK: Ensure Razorpay API is configured
        if (!$this->api) {
            throw new Exception("Razorpay API not configured. Please set razorpay_key_id and razorpay_key_secret in settings or .env file.");
        }

        $this->log("Creating Subscription for Plan {$gatewayPlanId}");

        try {
            $subscription = $this->api->subscription->create([
                'plan_id' => $gatewayPlanId,
                'customer_notify' => 1,
                'total_count' => $totalCount,
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

    protected function validateAmount($amount)
    {
        if (!is_numeric($amount) || $amount <= 0) {
            throw new InvalidArgumentException("Payment amount must be a positive number.");
        }

        $min = (float) setting('min_payment_amount', 1);
        $max = (float) setting('max_payment_amount', 1000000);

        if ($amount < $min || $amount > $max) {
            throw new InvalidArgumentException("Payment amount must be between ₹{$min} and ₹{$max}.");
        }
    }

    private function log($message, $level = 'info')
    {
        Log::$level("[RazorpayService] " . $message);
    }
}
