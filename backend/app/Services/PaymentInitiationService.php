<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;

class PaymentInitiationService
{
    public function __construct(
        protected RazorpayService $razorpayService
    ) {}

    /**
     * Handle the complete payment initiation flow.
     */
    public function initiate(User $user, Payment $payment, bool $isAutoDebit): array
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

        // Ensure Plan exists on Razorpay
        if (!$plan->razorpay_plan_id) {
            try {
                $this->razorpayService->createOrUpdateRazorpayPlan($plan);
            } catch (Exception $e) {
                Log::error("Razorpay Plan Creation Failed: " . $e->getMessage());
                throw new Exception('Payment provider plan setup failed. Please try again.');
            }
        }

        // Create Razorpay Subscription
        try {
            $razorpaySub = $this->razorpayService->createRazorpaySubscription(
                $plan->razorpay_plan_id,
                $user->email,
                $plan->duration_months
            );
        } catch (Exception $e) {
            Log::error("Razorpay Subscription Failed: " . $e->getMessage());
            throw new Exception('Mandate creation failed. Please try again.');
        }

        // Save Mandate ID locally
        $payment->subscription->update([
            'is_auto_debit' => true,
            'razorpay_subscription_id' => $razorpaySub->id
        ]);
        
        // Use Sub ID as Order ID for tracking
        $payment->update(['gateway_order_id' => $razorpaySub->id]); 

        return [
            'type' => 'subscription',
            'subscription_id' => $razorpaySub->id,
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
            $order = $this->razorpayService->createOrder(
                $payment->amount, 
                'payment_' . $payment->id
            );
        } catch (Exception $e) {
            Log::error("Razorpay Order Failed: " . $e->getMessage());
            throw new Exception('Payment gateway failed. Please try again.');
        }
        
        $payment->update(['gateway_order_id' => $order->id]);

        return [
            'type' => 'order',
            'order_id' => $order->id,
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