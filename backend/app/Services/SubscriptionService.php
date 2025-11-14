<?php
// V-FINAL-1730-334 (Created) | V-FINAL-1730-450 (V2.0 Hardened)

namespace App\Services;

use App\Models\Subscription;
use App\Models\Plan;
use App\Models\User;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubscriptionService
{
    /**
     * Create a new subscription with advanced validation.
     */
    public function createSubscription(User $user, Plan $plan): Subscription
    {
        // Test: test_subscription_create_with_inactive_plan
        if (!$plan->is_active) {
            throw new \Exception("Plan '{$plan->name}' is not currently available.");
        }

        // Test: test_subscription_create_kyc_pending_if_required
        if (setting('kyc_required_for_investment', true) && $user->kyc->status !== 'verified') {
            throw new \Exception("KYC must be verified to start a subscription.");
        }

        // Test: test_subscription_create_max_subscriptions_per_user
        $activeSubCount = $user->subscriptions()->whereIn('status', ['active', 'paused'])->count();
        if ($activeSubCount >= $plan->max_subscriptions_per_user) {
            throw new \Exception("You have reached the maximum of {$plan->max_subscriptions_per_user} active subscriptions for this plan.");
        }

        return DB::transaction(function () use ($user, $plan) {
            $sub = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'subscription_code' => 'SUB-' . uniqid(),
                'status' => 'active',
                'start_date' => now(),
                'end_date' => now()->addMonths($plan->duration_months),
                'next_payment_date' => now(), // Due immediately
            ]);

            $sub->payments()->create([
                'user_id' => $user->id,
                'amount' => $plan->monthly_amount,
                'status' => 'pending',
            ]);

            return $sub;
        });
    }

    /**
     * Pause a subscription with advanced validation.
     */
    public function pauseSubscription(Subscription $subscription, int $months): Subscription
    {
        return DB::transaction(function () use ($subscription, $months) {
            // Lock the subscription row to prevent concurrent pause/cancel
            $sub = Subscription::where('id', $subscription->id)->lockForUpdate()->first();

            // Test: test_subscription_pause_already_paused
            if ($sub->status === 'paused') {
                throw new \Exception("Subscription is already paused.");
            }
            if ($sub->status !== 'active') {
                throw new \Exception("Only active subscriptions can be paused.");
            }
            
            // Test: test_subscription_pause_with_pending_payments
            if ($sub->payments()->where('status', 'pending')->exists()) {
                throw new \Exception("Cannot pause while a payment is pending.");
            }

            // Test: test_subscription_pause_max_pause_duration_exceeded
            if ($months > $sub->plan->max_pause_duration_months) {
                throw new \Exception("Pause duration cannot exceed {$sub->plan->max_pause_duration_months} months.");
            }
            
            // Test: test_subscription_pause_max_pause_count_exceeded
            if ($sub->pause_count >= $sub->plan->max_pause_count) {
                throw new \Exception("You have reached the maximum of {$sub->plan->max_pause_count} pause requests.");
            }

            // Shift all future dates
            $newNextPayment = $sub->next_payment_date->addMonths($months);
            $newEndDate = $sub->end_date->addMonths($months);

            $sub->update([
                'status' => 'paused',
                'pause_start_date' => now(),
                'pause_end_date' => now()->addMonths($months),
                'pause_count' => $sub->pause_count + 1,
                'next_payment_date' => $newNextPayment,
                'end_date' => $newEndDate
            ]);
            
            return $sub;
        });
    }

    /**
     * Upgrade a plan with pro-rata calculation.
     */
    public function upgradePlan(Subscription $subscription, Plan $newPlan): float
    {
        if ($newPlan->monthly_amount <= $subscription->plan->monthly_amount) {
            throw new \Exception("Use downgrade for lower value plans.");
        }

        return DB::transaction(function () use ($subscription, $newPlan) {
            // Lock the subscription row
            $sub = Subscription::where('id', $subscription->id)->lockForUpdate()->first();
            
            if ($sub->status !== 'active') {
                throw new \Exception("Only active subscriptions can be upgraded.");
            }

            $oldAmount = $sub->plan->monthly_amount;
            $newAmount = $newPlan->monthly_amount;

            // --- Pro-Rata Logic (test_upgrade_prorated_calculation_exact) ---
            $cycleEndDate = $sub->next_payment_date;
            $cycleStartDate = $cycleEndDate->copy()->subMonth();
            $daysInCycle = $cycleStartDate->diffInDays($cycleEndDate);
            $daysRemaining = now()->diffInDays($cycleEndDate, false); // false = not absolute
            
            if ($daysInCycle <= 0 || $daysRemaining <= 0) {
                $proratedAmount = 0; // Change happens at end of cycle
            } else {
                $dailyRateDiff = ($newAmount - $oldAmount) / $daysInCycle;
                $proratedAmount = $dailyRateDiff * $daysRemaining;
            }
            // -----------------------------------------------------------------

            $sub->update(['plan_id' => $newPlan->id]);
            
            if ($proratedAmount > 1) { // Charge if over 1 Rupee
                $proratedAmount = round($proratedAmount, 2);
                Payment::create([
                    'user_id' => $sub->user_id,
                    'subscription_id' => $sub->id,
                    'amount' => $proratedAmount,
                    'status' => 'pending',
                    'gateway' => 'upgrade_charge',
                    'description' => "Pro-rata upgrade charge to {$newPlan->name}"
                ]);
            }
            
            return $proratedAmount;
        });
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(Subscription $subscription, string $reason): bool
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
                'is_auto_debit' => false // Disable auto-debit
            ]);

            // Test: test_subscription_cancel_with_pending_payments
            // Cancel all pending payments for this subscription
            $sub->payments()
                ->where('status', 'pending')
                ->update(['status' => 'failed', 'failure_reason' => 'Subscription cancelled']);
                
            return true;
        });
    }
    
    // ... (resumeSubscription, downgradePlan, etc.)
    public function resumeSubscription(Subscription $subscription) { /* ... */ }
    public function downgradePlan(Subscription $subscription, Plan $newPlan) { /* ... */ }
}