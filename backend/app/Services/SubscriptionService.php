<?php
// V-FINAL-1730-334 (Created) | V-FINAL-1730-469 (WalletService Refactor) | V-FINAL-1730-578 (V2.0 Proration)

namespace App\Services;

use App\Models\Subscription;
use App\Models\Plan;
use App\Models\User;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubscriptionService
{
    protected $walletService;
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Create a new subscription with validation.
     */
    public function createSubscription(User $user, Plan $plan, ?float $customAmount = null): Subscription
    {
        // 1. Validations
        if (!$plan->is_active) {
            throw new \Exception("Plan '{$plan->name}' is not currently available.");
        }
        if (setting('kyc_required_for_investment', true) && $user->kyc->status !== 'verified') {
            throw new \Exception("KYC must be verified to start a subscription.");
        }
        $activeSubCount = $user->subscriptions()->whereIn('status', ['active', 'paused'])->count();
        if ($activeSubCount >= $plan->max_subscriptions_per_user) {
            throw new \Exception("You have reached the maximum of {$plan->max_subscriptions_per_user} active subscriptions.");
        }

        $finalAmount = $plan->monthly_amount;

        // 2. Custom Amount Logic
        if ($customAmount) {
            if (!$plan->getConfig('allow_custom_amount', false)) {
                throw new \Exception("This plan does not allow custom amounts.");
            }
            if ($customAmount < $plan->monthly_amount) {
                throw new \Exception("Amount must be at least ₹{$plan->monthly_amount}.");
            }
            $finalAmount = $customAmount;
        }

        // 3. Create Records
        return DB::transaction(function () use ($user, $plan, $finalAmount) {
            $sub = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $finalAmount,
                'subscription_code' => 'SUB-' . uniqid(),
                'status' => 'active',
                'start_date' => now(),
                'end_date' => now()->addMonths($plan->duration_months),
                'next_payment_date' => now(),
            ]);

            $sub->payments()->create([
                'user_id' => $user->id,
                'amount' => $finalAmount,
                'status' => 'pending',
                'payment_type' => 'sip_installment',
            ]);

            return $sub;
        });
    }

    /**
     * FSD-PLAN-017: Upgrade a plan with pro-rata calculation.
     */
    public function upgradePlan(Subscription $subscription, Plan $newPlan): float
    {
        if ($newPlan->monthly_amount <= $subscription->amount) {
            throw new \Exception("New plan amount must be higher than your current amount of ₹{$subscription->amount}.");
        }

        return DB::transaction(function () use ($subscription, $newPlan) {
            $sub = Subscription::where('id', $subscription->id)->lockForUpdate()->first();
            if ($sub->status !== 'active') {
                throw new \Exception("Only active subscriptions can be upgraded.");
            }

            $oldAmount = $sub->amount;
            $newAmount = $newPlan->monthly_amount; // Upgrades reset to plan default

            // --- V2.0 PRO-RATA LOGIC ---
            $cycleEndDate = $sub->next_payment_date;
            $daysRemaining = now()->diffInDays($cycleEndDate, false);
            
            // If it's the last day or past due, no proration, just upgrade
            if ($daysRemaining <= 0) {
                $proratedAmount = 0;
            } else {
                $cycleStartDate = $cycleEndDate->copy()->subMonth();
                $daysInCycle = $cycleStartDate->diffInDays($cycleEndDate);
                if ($daysInCycle == 0) $daysInCycle = 30; // Failsafe
                
                $dailyRateDiff = ($newAmount - $oldAmount) / $daysInCycle;
                $proratedAmount = $dailyRateDiff * $daysRemaining;
            }
            // --------------------------

            $sub->update([
                'plan_id' => $newPlan->id,
                'amount' => $newAmount // Set new amount
            ]);
            
            if ($proratedAmount > 1) {
                $proratedAmount = round($proratedAmount, 2);
                Payment::create([
                    'user_id' => $sub->user_id,
                    'subscription_id' => $sub->id,
                    'amount' => $proratedAmount,
                    'status' => 'pending',
                    'payment_type' => 'upgrade_charge',
                    'description' => "Pro-rata charge for {$newPlan->name}"
                ]);
            }
            
            return $proratedAmount;
        });
    }

    /**
     * FSD-PLAN-018: Downgrade plan (No refund, just switch).
     */
    public function downgradePlan(Subscription $subscription, Plan $newPlan): float
    {
        if ($newPlan->monthly_amount >= $subscription->amount) {
            throw new \Exception("New plan amount must be lower.");
        }

        $subscription->update([
            'plan_id' => $newPlan->id,
            'amount' => $newPlan->monthly_amount // Set new amount
        ]);
        
        // FSD Rule: No refund for current month on downgrade.
        return 0;
    }

    /**
     * FSD-PLAN-018: Cancel subscription and process potential refund.
     */
    public function cancelSubscription(Subscription $subscription, string $reason): float
    {
        return DB::transaction(function () use ($subscription, $reason) {
            $sub = Subscription::where('id', $subscription->id)->lockForUpdate()->first();
            if ($sub->status === 'cancelled') {
                throw new \Exception("Subscription is already cancelled.");
            }

            $sub->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'is_auto_debit' => false
            ]);

            // 1. Cancel all pending (unpaid) payments
            $sub->payments()->where('status', 'pending')->update(['status' => 'failed', 'failure_reason' => 'Subscription cancelled']);

            // 2. FSD-LEGAL-005: Calculate pro-rata refund if eligible
            $refundAmount = 0;
            $refundPolicyDays = (int) setting('refund_policy_days', 7);
            
            // Check if *first* payment was within the refund window
            $firstPayment = $sub->payments()->where('status', 'paid')->orderBy('paid_at', 'asc')->first();

            if ($firstPayment && $firstPayment->paid_at->diffInDays(now()) <= $refundPolicyDays) {
                // User is eligible for a pro-rata refund of their first payment
                $daysInMonth = $firstPayment->paid_at->daysInMonth;
                $daysUsed = $firstPayment->paid_at->diffInDays(now());
                $daysRemaining = $daysInMonth - $daysUsed;

                if ($daysRemaining > 0) {
                    $dailyRate = $firstPayment->amount / $daysInMonth;
                    $refundAmount = round($dailyRate * $daysRemaining, 2);
                    
                    if ($refundAmount > 0) {
                        $bonus = $this->walletService->deposit(
                            $sub->user,
                            $refundAmount,
                            'refund',
                            "Pro-rata refund for cancellation",
                            $firstPayment
                        );
                        // Link refund to original payment
                        $bonus->payment->update(['refunds_payment_id' => $firstPayment->id]);
                    }
                }
            }
            
            return $refundAmount;
        });
    }
    
    // ... (pauseSubscription, resumeSubscription remain the same) ...
    public function pauseSubscription(Subscription $subscription, int $months) { /* ... */ }
    public function resumeSubscription(Subscription $subscription) { /* ... */ }
}