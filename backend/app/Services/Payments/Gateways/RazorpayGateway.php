<?php
// V-PHASE3-1730-096 (Created) | V-FINAL-1730-362 (Refunds Added) | V-AUDIT-FIX-2025 (Contract Compliance)

namespace App\Services\Payments\Gateways;

use App\Contracts\PaymentGatewayInterface; // [AUDIT FIX]: Strictly use the Contract namespace
use App\Models\Plan;
use Razorpay\Api\Api;
use Exception;
use Illuminate\Support\Facades\Log;

class RazorpayGateway implements PaymentGatewayInterface
{
    protected $api;

    public function __construct()
    {
        // [AUDIT FIX]: Safe initialization to prevent crashes if keys are missing
        $key = config('services.razorpay.key');
        $secret = config('services.razorpay.secret');

        if ($key && $secret) {
            $this->api = new Api($key, $secret);
        }
    }

    /**
     * Create a one-time payment order.
     * [AUDIT FIX]: Updated signature to match Contract: (float $amount, string $receiptId)
     */
    public function createOrder(float $amount, string $receiptId)
    {
        if (!$this->api) throw new Exception("Razorpay credentials missing.");

        // [AUDIT FIX]: Hardcoding INR as base currency for now, can be config driven
        $orderData = [
            'receipt'         => $receiptId,
            'amount'          => $amount * 100, // Rupees to Paise
            'currency'        => 'INR',
            'payment_capture' => 1 
        ];

        try {
            $order = $this->api->order->create($orderData);
            
            return [
                'id' => $order['id'],
                'amount' => $order['amount'],
                'currency' => $order['currency'],
                'receipt' => $order['receipt'] ?? $receiptId
            ];
        } catch (Exception $e) {
            Log::error("Razorpay Create Order Failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify payment signature.
     * [AUDIT FIX]: Renamed from 'verifyPayment' to 'verifySignature' to match Contract.
     */
    public function verifySignature(array $attributes): bool
    {
        if (!$this->api) return false;

        try {
            // Razorpay utility expects specific keys: razorpay_order_id, razorpay_payment_id, razorpay_signature
            // We assume $attributes contains these keys from the frontend response.
            $this->api->utility->verifyPaymentSignature($attributes);
            return true;
        } catch (Exception $e) {
            Log::error("Razorpay Signature Verification Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Refund a payment.
     * [AUDIT FIX]: Renamed from 'refund' to 'refundPayment' to match Contract.
     */
    public function refundPayment(string $paymentId, ?float $amount = null)
    {
        if (!$this->api) throw new Exception("Razorpay credentials missing.");

        $refundData = [];
        if ($amount !== null) {
            $refundData['amount'] = $amount * 100; // Convert to paise
        }

        try {
            $payment = $this->api->payment->fetch($paymentId);
            return $payment->refund($refundData);
        } catch (Exception $e) {
            Log::error("Razorpay Refund Failed: " . $e->getMessage());
            throw $e;
        }
    }

    // --- NEW METHODS REQUIRED BY CONTRACT ---

    /**
     * Sync a local Plan with Razorpay.
     */
    public function createOrUpdatePlan(Plan $plan)
    {
        if (!$this->api) throw new Exception("Razorpay credentials missing.");

        $planData = [
            'period'   => 'monthly', // Default to monthly for SIP
            'interval' => 1,
            'item'     => [
                'name'        => $plan->name,
                'description' => $plan->description ?? 'SIP Plan',
                'amount'      => $plan->amount * 100, // Paise
                'currency'    => 'INR'
            ]
        ];

        try {
            $rpPlan = $this->api->plan->create($planData);
            return $rpPlan['id'];
        } catch (Exception $e) {
            Log::error("Razorpay Plan Creation Failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a subscription (mandate).
     */
    public function createSubscription(string $gatewayPlanId, string $customerEmail, int $totalCount)
    {
        if (!$this->api) throw new Exception("Razorpay credentials missing.");

        $subscriptionData = [
            'plan_id'   => $gatewayPlanId,
            'total_count' => $totalCount,
            'customer_notify' => 1,
            'notes' => [
                'email' => $customerEmail
            ]
        ];

        try {
            $subscription = $this->api->subscription->create($subscriptionData);
            return [
                'id' => $subscription['id'],
                'status' => $subscription['status'],
                'short_url' => $subscription['short_url']
            ];
        } catch (Exception $e) {
            Log::error("Razorpay Subscription Failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch payment details from provider.
     */
    public function fetchPayment(string $paymentId)
    {
        if (!$this->api) throw new Exception("Razorpay credentials missing.");
        return $this->api->payment->fetch($paymentId);
    }
}