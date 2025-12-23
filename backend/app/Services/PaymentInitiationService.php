<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;
use App\Contracts\PaymentGatewayInterface; // [AUDIT FIX]

class PaymentInitiationService
{
    // [AUDIT FIX] Depend on Abstraction (Interface), not Concretion
    public function __construct(
        protected PaymentGatewayInterface $gateway
    ) {}

    /**
     * Handle the complete payment initiation flow.
     */
    public function initiate(User $user, Payment $payment, bool $isAutoDebit = false): array
    {
        // 1. Dynamic Limits Check
        $this->validateLimits($payment);

        // 2. Route to appropriate flow
        if ($isAutoDebit) {
            return $this->handleAutoDebitFlow($user, $payment);
        }

        return $this->handleOneTimeFlow($user, $payment);
    }

    /**
     * Validate payment amount against system settings.
     */
    protected function validateLimits(Payment $payment): void
    {
        $min = setting('min_payment_amount', 1);
        $max = setting('max_payment_amount', 1000000);

        if ($payment->amount < $min || $payment->amount > $max) {
            throw new InvalidArgumentException("Payment amount must be between ₹$min and ₹$max.");
        }
    }

    /**
     * Process recurring subscription (Mandate) setup.
     */
    protected function handleAutoDebitFlow(User $user, Payment $payment): array
    {
        $plan = $payment->subscription->plan;

        // Ensure Plan exists on Gateway
        if (!$plan->razorpay_plan_id) {
            try {
                // [AUDIT FIX] Create plan and capture the ID
                $planId = $this->gateway->createOrUpdatePlan($plan);
                
                // [IMPORTANT] Save the external Plan ID so we don't create it again
                $plan->update(['razorpay_plan_id' => $planId]);
            } catch (Exception $e) {
                Log::error("Gateway Plan Creation Failed: " . $e->getMessage());
                throw new Exception('Payment provider plan setup failed. Please try again.');
            }
        }

        // Create Gateway Subscription
        try {
            $gatewaySub = $this->gateway->createSubscription(
                $plan->razorpay_plan_id,
                $user->email,
                $plan->duration_months
            );
        } catch (Exception $e) {
            Log::error("Gateway Subscription Failed: " . $e->getMessage());
            throw new Exception('Mandate creation failed. Please try again.');
        }

        // [AUDIT FIX]: Use Array Access (Gateway returns array)
        $subId = $gatewaySub['id'];

        // Save Mandate ID locally
        $payment->subscription->update([
            'is_auto_debit' => true,
            'razorpay_subscription_id' => $subId
        ]);
        
        // Unified Order ID logic
        $payment->update(['gateway_order_id' => $subId]); 

        return [
            'type' => 'subscription',
            'subscription_id' => $subId,
            'razorpay_key' => setting('razorpay_key_id', env('RAZORPAY_KEY')),
            'name' => $plan->name . ' (Auto-Debit)',
            'description' => 'Setup recurring monthly payment',
            'prefill' => $this->getPrefillData($user)
        ];
    }

    /**
     * Process standard one-time payment order.
     */
    protected function handleOneTimeFlow(User $user, Payment $payment): array
    {
        try {
            // [AUDIT FIX] Use interface method signature: createOrder(amount, receiptId)
            $order = $this->gateway->createOrder(
                $payment->amount, 
                'payment_' . $payment->id
            );
        } catch (Exception $e) {
            Log::error("Gateway Order Failed: " . $e->getMessage());
            throw new Exception('Payment gateway failed. Please try again.');
        }
        
        // [AUDIT FIX]: Use Array Access (Gateway returns array)
        $orderId = $order['id'];

        $payment->update(['gateway_order_id' => $orderId]);

        return [
            'type' => 'order',
            'order_id' => $orderId,
            'razorpay_key' => setting('razorpay_key_id', env('RAZORPAY_KEY')),
            'amount' => $payment->amount * 100,
            'name' => 'PreIPO SIP Payment',
            'description' => 'One-time payment for ' . $payment->subscription->plan->name,
            'prefill' => $this->getPrefillData($user)
        ];
    }

    protected function getPrefillData(User $user): array
    {
        return [
            'name' => $user->profile->first_name . ' ' . $user->profile->last_name,
            'email' => $user->email,
            'contact' => $user->mobile,
        ];
    }
}