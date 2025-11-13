<?php
// V-REMEDIATE-1730-188

namespace App\Services;

use Razorpay\Api\Api;
use App\Models\Plan;
use Illuminate\Support\Facades\Log;

class RazorpayService
{
    protected $api;

    public function __construct()
    {
        $this->api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
    }

    /**
     * Create a Plan on Razorpay to enable recurring billing.
     */
    public function createPlan(Plan $plan)
    {
        if ($plan->razorpay_plan_id) {
            return $plan->razorpay_plan_id;
        }

        try {
            $rpPlan = $this->api->plan->create([
                'period' => 'monthly',
                'interval' => 1,
                'item' => [
                    'name' => $plan->name,
                    'amount' => $plan->monthly_amount * 100, // in paise
                    'currency' => 'INR',
                    'description' => $plan->description ?? 'Monthly SIP'
                ],
                'notes' => [
                    'internal_plan_id' => $plan->id
                ]
            ]);

            $plan->update(['razorpay_plan_id' => $rpPlan->id]);
            return $rpPlan->id;

        } catch (\Exception $e) {
            Log::error("Razorpay Plan Creation Failed: " . $e->getMessage());
            // Return null so we don't break local plan creation, but log the error
            return null;
        }
    }

    /**
     * Create a Subscription (Mandate) for a user.
     */
    public function createSubscription(string $planId, int $totalCount)
    {
        try {
            $subscription = $this->api->subscription->create([
                'plan_id' => $planId,
                'total_count' => $totalCount,
                'quantity' => 1,
                'customer_notify' => 1,
            ]);

            return $subscription->id;

        } catch (\Exception $e) {
            Log::error("Razorpay Subscription Creation Failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Verify Payment Signature (Utility)
     */
    public function verifySignature($attributes)
    {
        return $this->api->utility->verifyPaymentSignature($attributes);
    }
}