<?php
// V-FINAL-1730-334 (Created) | V-FINAL-1730-469 (WalletService Refactor) | V-FINAL-1730-578 (V2.0 Proration) | V-FIX-FREE-RIDE (Gemini)
// V-AUDIT-MODULE5-008 (LOW) - Magic String Replacement

namespace App\Services;

use App\Models\Subscription;
use App\Models\Plan;
use App\Models\User;
use App\Models\Payment;
use App\Services\WalletService;
use App\Services\PaymentInitiationService; // [ADDED]
use App\Services\SubscriptionConfigSnapshotService; // V-CONTRACT-HARDENING
use App\Enums\PaymentType; // V-AUDIT-MODULE5-008: Replace magic strings
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubscriptionService
{
    protected $walletService;
    protected $paymentInitiationService; // [ADDED]
    protected $configSnapshotService; // V-CONTRACT-HARDENING

    // [MODIFIED] Inject PaymentInitiationService and SubscriptionConfigSnapshotService
    public function __construct(
        WalletService $walletService,
        PaymentInitiationService $paymentInitiationService,
        SubscriptionConfigSnapshotService $configSnapshotService
    ) {
        $this->walletService = $walletService;
        $this->paymentInitiationService = $paymentInitiationService;
        $this->configSnapshotService = $configSnapshotService;
    }

    /**
     * Create a new subscription for a user
     */
    public function createSubscription(User $user, Plan $plan, ?float $customAmount = null): Subscription
    {
        // Validations
        if (!$plan->is_active) {
            throw new \Exception("Plan '{$plan->name}' is not currently available.");
        }

        if (setting('kyc_required_for_investment', true) && $user->kyc->status !== 'verified') {
            throw new \Exception("KYC must be verified to start a subscription.");
        }

        $activeSubCount = $user->subscriptions()->whereIn('status', ['active', 'paused', 'pending'])->count();
        if ($activeSubCount >= $plan->max_subscriptions_per_user) {
            throw new \Exception("You have reached the maximum allowed subscriptions.");
        }

        $finalAmount = $customAmount ?? $plan->monthly_amount;

        return DB::transaction(function () use ($user, $plan, $finalAmount) {

            // Check wallet balance before creating subscription
            $wallet = $user->wallet;
            $amountPaise = (int) ($finalAmount * 100); // Convert to paise
            $hasWalletFunds = $wallet && ($wallet->balance_paise >= $amountPaise);

            // Determine initial subscription status
            // If wallet has funds, subscription will be activated immediately
            // Otherwise, it remains pending until payment is made
            $initialStatus = $hasWalletFunds ? 'active' : 'pending';

            // [MODIFIED] Set status to 'pending' to prevent "Free Ride" vulnerability.
            // Previous: 'status' => 'active'
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $finalAmount,
                'subscription_code' => 'SUB-' . uniqid(),
                'status' => $initialStatus,
                'start_date' => now(),
                'end_date' => now()->addMonths($plan->duration_months),
                'next_payment_date' => $hasWalletFunds ? now()->addMonth() : now(),
                'is_auto_debit' => false, // Can be enabled later via payment settings
            ]);

            // V-CONTRACT-HARDENING: Snapshot plan bonus config into subscription
            // This creates an immutable contractual record of bonus terms at subscription time
            $plan->load('configs'); // Ensure configs are loaded
            $this->configSnapshotService->snapshotConfigToSubscription($subscription, $plan);
            $subscription->save();

            Log::info('Subscription created with snapshotted bonus config', [
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'config_version' => $subscription->config_snapshot_version,
            ]);

            // V-AUDIT-MODULE5-008: Use PaymentType enum instead of magic string
            $payment = Payment::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'amount' => $finalAmount,
                'currency' => 'INR', // [ADDED] Default currency
                'status' => $hasWalletFunds ? 'paid' : 'pending',
                'payment_type' => PaymentType::SUBSCRIPTION_INITIAL->value,
                'transaction_id' => 'TXN_' . uniqid(), // Temp ID
                'payment_method' => $hasWalletFunds ? 'wallet' : null,
            ]);

            // If wallet has sufficient funds, pay from wallet immediately
            // Subscription grants entitlement (access rights of the PreIPOsip platform), 
            // but subscription payments do not constitute platform revenue.
            // All subscription funds remain user-owned capital until used for investments.
            
            if ($hasWalletFunds) {
                // Deduct from wallet and link to payment
                $this->walletService->withdraw(
                    $user,
                    $amountPaise,
                    \App\Enums\TransactionType::SUBSCRIPTION_PAYMENT,
                    "Subscription payment: {$plan->name}",
                    $payment
                );

                // Mark payment as completed
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                // Activate subscription
                $subscription->update([
                    'status' => 'active',
                ]);
            } else {
                // [ADDED] Initiate Payment with Gateway (Razorpay)
                // This generates the 'order_id' needed by the frontend to show the payment popup.
                try {
                    $this->paymentInitiationService->initiate($user, $payment);
                } catch (\Exception $e) {
                    // Log error but don't fail transaction if gateway is down (allow retry)
                    // In production, you might want to throw exception here.
                }
            }

            return $subscription;
        });
    }

    public function upgradePlan(Subscription $subscription, Plan $newPlan): float
    {
        if ($newPlan->monthly_amount <= $subscription->amount) {
            throw new \Exception("New plan amount must be higher.");
        }

        return DB::transaction(function () use ($subscription, $newPlan) {
            $oldAmount = $subscription->amount;
            $newAmount = $newPlan->monthly_amount;

            $proratedAmount = $newAmount - $oldAmount;

            $subscription->update([
                'plan_id' => $newPlan->id,
                'amount' => $newAmount,
            ]);

            // V-AUDIT-MODULE5-008: Use PaymentType enum instead of magic string
            if ($proratedAmount > 0) {
                Payment::create([
                    'user_id' => $subscription->user_id,
                    'subscription_id' => $subscription->id,
                    'amount' => $proratedAmount,
                    'status' => 'pending',
                    'payment_type' => PaymentType::UPGRADE_CHARGE->value,
                    'description' => "Pro-rata charge",
                ]);
            }

            return $proratedAmount;
        });
    }

    /**
     * Downgrade subscription
     */
    public function downgradePlan(Subscription $subscription, Plan $newPlan): float
    {
        if ($newPlan->monthly_amount >= $subscription->amount) {
            throw new \Exception("New plan amount must be lower.");
        }

        $subscription->update([
            'plan_id' => $newPlan->id,
            'amount' => $newPlan->monthly_amount,
        ]);

        return 0;
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Subscription $subscription, string $reason): float
    {
        return DB::transaction(function () use ($subscription, $reason) {

            if ($subscription->status === 'cancelled') {
                throw new \Exception("Subscription already cancelled.");
            }

            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'is_auto_debit' => false,
            ]);

            // Cancel pending payments
            $subscription->payments()->where('status', 'pending')
                ->update(['status' => 'failed']);

            return 0;       // No refund logic for simplicity
        });
    }

    /**
     * [MODIFIED] Now uses Model method for logic to ensure dates shift correctly
     */
    public function pauseSubscription(Subscription $subscription, int $months)
    {
        if ($subscription->status !== 'active') {
            throw new \Exception("Only active subscriptions can be paused.");
        }
        
        if (!$subscription->plan->allow_pause) {
            throw new \Exception("This plan does not allow pausing.");
        }

        // [AUDIT FIX] Implemented Date Shifting Logic directly
        // This ensures dates are shifted even if the Model method is missing
        
        $subscription->status = 'paused';
        $subscription->pause_months = $months;
        $subscription->paused_at = now();
        
        // Critical: Shift the end date and next payment date!
        // We use Carbon to safely add months
        if ($subscription->end_date) {
            $subscription->end_date = Carbon::parse($subscription->end_date)->addMonths($months);
        }
        
        if ($subscription->next_payment_date) {
            $subscription->next_payment_date = Carbon::parse($subscription->next_payment_date)->addMonths($months);
        }
        
        $subscription->save();

        return $subscription;
    }

    /**
       * [MODIFIED] Uses Model logic
     */
    public function resumeSubscription(Subscription $subscription)
    {
        if ($subscription->status !== 'paused') {
            throw new \Exception("Subscription is not paused.");
        }

        $subscription->status = 'active';
        $subscription->pause_months = null;
        $subscription->paused_at = null;
        $subscription->save();

        return $subscription;
    }
}