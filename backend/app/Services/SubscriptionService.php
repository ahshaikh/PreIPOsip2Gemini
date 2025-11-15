<?php
// V-FINAL-1730-334 (Created) | V-FINAL-1730-469 (WalletService Refactor)

namespace App\Services;

use App\Models\Subscription;
use App\Models\Plan;
use App\Models\User;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\WalletService; // <-- IMPORT
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
     * Create a new subscription with advanced validation.
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
            throw new \Exception("You have reached the maximum of {$plan->max_subscriptions_per_user} active subscriptions for this plan.");
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
            $sub = Subscription::where('id', $subscription->id)->lockForUpdate()->first();

            if ($sub->status === 'paused') {
                throw new \Exception("Subscription is already paused.");
            }
            if ($sub->status !== 'active') {
                throw new \Exception("Only active subscriptions can be paused.");
            }
            if ($sub->payments()->where('status', 'pending')->exists()) {
                throw new \Exception("Cannot pause while a payment is pending.");
            }
            if ($months > $sub->plan->max_pause_duration_months) {
                throw new \Exception("Pause duration cannot exceed {$sub->plan->max_pause_duration_months} months.");
            }
            if ($sub->pause_count >= $sub->plan->max_pause_count) {
                throw new \Exception("You have reached the maximum of {$sub->plan->max_pause_count} pause requests.");
            }

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
     * Resume a paused subscription.
     */
    public function resumeSubscription(Subscription $subscription)
    {
        if ($subscription->status !== 'paused') {
            throw new \Exception("Subscription is not paused.");
        }

        // Logic: Recalculate next payment date if pause ended early
        $newNextPayment = $subscription->next_payment_date;
        $newEndDate = $subscription->end_date;
        
        if ($subscription->pause_end_date->isFuture()) {
            // User resumed early. Pull dates back.
            $daysPaused = $subscription->pause_start_date->diffInDays(now());
            $totalPauseDuration = $subscription->pause_start_date->diffInDays($subscription->pause_end_date);
            $daysRemaining = $totalPauseDuration - $daysPaused;

            $newNextPayment = $subscription->next_payment_date->subDays($daysRemaining);
            $newEndDate = $subscription->end_date->subDays($daysRemaining);
        }

        $subscription->update([
            'status' => 'active',
            'pause_start_date' => null,
            'pause_end_date' => null,
            'next_payment_date' => $newNextPayment,
            'end_date' => $newEndDate
        ]);

        return $subscription;
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
            $sub = Subscription::where('id', $subscription->id)->lockForUpdate()->first();
            
            if ($sub->status !== 'active') {
                throw new \Exception("Only active subscriptions can be upgraded.");
            }

            $oldAmount = $sub->amount; // Use sub amount, not plan
            $newAmount = $newPlan->monthly_amount;

            // Pro-Rata Logic
            $cycleEndDate = $sub->next_payment_date;
            $cycleStartDate = $cycleEndDate->copy()->subMonth();
            $daysInCycle = $cycleStartDate->diffInDays($cycleEndDate);
            $daysRemaining = now()->diffInDays($cycleEndDate, false);
            
            $proratedAmount = 0;
            if ($daysInCycle > 0 && $daysRemaining > 0) {
                $dailyRateDiff = ($newAmount - $oldAmount) / $daysInCycle;
                $proratedAmount = $dailyRateDiff * $daysRemaining;
            }

            // Update subscription to new plan and new amount
            $sub->update([
                'plan_id' => $newPlan->id,
                'amount' => $newAmount
            ]);
            
            if ($proratedAmount > 1) {
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
     * Downgrade plan (No refund, just switch).
     */
    public function downgradePlan(Subscription $subscription, Plan $newPlan): float
    {
        if ($newPlan->monthly_amount >= $subscription->plan->monthly_amount) {
            throw new \Exception("Use upgrade for higher value plans.");
        }

        $subscription->update([
            'plan_id' => $newPlan->id,
            'amount' => $newPlan->monthly_amount
        ]);
        
        // FSD Rule: No refund for current month on downgrade
        return 0;
    }

    /**
     * Cancel subscription.
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
                'is_auto_debit' => false
            ]);

            // Cancel all pending payments
            $sub->payments()
                ->where('status', 'pending')
                ->update(['status' => 'failed', 'failure_reason' => 'Subscription cancelled']);
                
            return true;
        });
    }
}