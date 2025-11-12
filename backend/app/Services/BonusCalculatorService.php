<?php
// V-PHASE3-1730-083 (REVISED)

namespace App\Services;

use App\Models\Payment;
use App\Models\BonusTransaction;
use App\Models\PlanConfig;
use Illuminate\Support\Facades\Log;

class BonusCalculatorService
{
    /**
     * Main orchestrator to calculate all bonus types.
     * This is the new, configurable engine.
     * Returns the total bonus amount.
     */
    public function calculateAndAwardBonuses(Payment $payment): float
    {
        $subscription = $payment->subscription->load('plan.configs');
        $user = $payment->user;
        $plan = $subscription->plan;
        
        $totalBonus = 0;
        $multiplier = $subscription->bonus_multiplier; // Get the user's referral multiplier (e.g., 1.5)

	// ---
        // REMEDIATION (SEC-2): Add checks for each bonus toggle
        // ---

        // 1. Progressive Monthly Bonus
        if (setting('progressive_bonus_enabled', true)) { // <-- CHECK
            $progressiveBonus = $this->calculateProgressive($payment, $plan, $multiplier);
            if ($progressiveBonus > 0) {
                $totalBonus += $progressiveBonus;
                $this->createBonusTransaction($payment, 'progressive', $progressiveBonus, $multiplier, 'Progressive Monthly Bonus');
            }
        }
        
        // 2. Milestone Bonus
        if (setting('milestone_bonus_enabled', true)) { // <-- CHECK
            $milestoneBonus = $this->calculateMilestone($payment, $plan, $multiplier);
            if ($milestoneBonus > 0) {
                $totalBonus += $milestoneBonus;
                $this->createBonusTransaction($payment, 'milestone', $milestoneBonus, $multiplier, 'Milestone Bonus');
            }
        }

        // 3. Consistency Bonus
        if (setting('consistency_bonus_enabled', true) && $payment->is_on_time) { // <-- CHECK
            $consistencyBonus = $this->calculateConsistency($payment, $plan);
            if ($consistencyBonus > 0) {
                $totalBonus += $consistencyBonus;
                $this->createBonusTransaction($payment, 'consistency', $consistencyBonus, 1.0, 'On-Time Payment Bonus');
            }
        }
        
        Log::info("Total bonus calculated for Payment {$payment->id}: {$totalBonus}");
        return $totalBonus;
    	}

	// ... (rest of the file is unchanged) ...

        // ---
        // DEVIATION FIX: Removed the hardcoded 10% stub.
        // $totalBonus = $payment->amount * 0.10; // [DELETED]
        // ---

        // 1. Progressive Monthly Bonus [cite: 417-418]
        if (setting('progressive_bonus_enabled', true)) {
            $progressiveBonus = $this->calculateProgressive($payment, $plan, $multiplier);
            if ($progressiveBonus > 0) {
                $totalBonus += $progressiveBonus;
                $this->createBonusTransaction($payment, 'progressive', $progressiveBonus, $multiplier, 'Progressive Monthly Bonus');
            }
        }
        
        // 2. Milestone Bonus [cite: 417-418]
        if (setting('milestone_bonus_enabled', true)) {
            $milestoneBonus = $this->calculateMilestone($payment, $plan, $multiplier);
            if ($milestoneBonus > 0) {
                $totalBonus += $milestoneBonus;
                $this->createBonusTransaction($payment, 'milestone', $milestoneBonus, $multiplier, 'Milestone Bonus');
            }
        }

        // 3. Consistency Bonus [cite: 417-418]
        if (setting('consistency_bonus_enabled', true) && $payment->is_on_time) {
            $consistencyBonus = $this->calculateConsistency($payment, $plan);
            if ($consistencyBonus > 0) {
                $totalBonus += $consistencyBonus;
                $this->createBonusTransaction($payment, 'consistency', $consistencyBonus, 1.0, 'On-Time Payment Bonus');
            }
        }
        
        Log::info("Total bonus calculated for Payment {$payment->id}: {$totalBonus}");
        return $totalBonus;
    }

    /**
     * Helper to load the plan's JSON config.
     */
    private function getPlanConfig($plan, $key, $default = null)
    {
        $config = $plan->configs->where('config_key', $key)->first();
        return $config ? $config->value : $default;
    }

    /**
     * 1. Calculates Progressive Bonus
     */
    private function calculateProgressive(Payment $payment, $plan, $multiplier): float
    {
        $config = $this->getPlanConfig($plan, 'progressive_config', [
            'rate' => 0.5, 'start_month' => 4
        ]);
        
        $month = $payment->subscription->payments()->where('status', 'paid')->count();
        
        if ($month < $config['start_month']) {
            return 0;
        }
        
        $base = $payment->amount;
        $rate = $config['rate'] / 100; // Convert 0.5 to 0.005
        
        // Formula: (month - start_month + 1) * rate * base_amount * multiplier
        $bonus = ($month - $config['start_month'] + 1) * $rate * $base * $multiplier;
        
        return round($bonus, 2);
    }
    
    /**
     * 2. Calculates Milestone Bonus
     */
    private function calculateMilestone(Payment $payment, $plan, $multiplier): float
    {
        $config = $this->getPlanConfig($plan, 'milestone_config', [
            ['month' => 12, 'amount' => 500],
            ['month' => 24, 'amount' => 1000],
            ['month' => 36, 'amount' => 2000]
        ]);
        
        $month = $payment->subscription->payments()->where('status', 'paid')->count();
        
        foreach ($config as $milestone) {
            if ($month === $milestone['month']) {
                // Check for consecutive payments
                if ($payment->subscription->consecutive_payments_count >= $month) {
                    return $milestone['amount'] * $multiplier;
                }
            }
        }
        
        return 0;
    }

    /**
     * 3. Calculates Consistency Bonus
     */
    private function calculateConsistency(Payment $payment, $plan): float
    {
        $config = $this->getPlanConfig($plan, 'consistency_config', [
            'amount_per_payment' => 10, // Default 10 INR
            'streaks' => [
                ['months' => 6, 'multiplier' => 3],
                ['months' => 12, 'multiplier' => 5]
            ]
        ]);
        
        $bonus = $config['amount_per_payment'];
        $streak = $payment->subscription->consecutive_payments_count;

        foreach ($config['streaks'] as $streakRule) {
            if ($streak === $streakRule['months']) {
                $bonus *= $streakRule['multiplier'];
                break; // Apply highest matching streak
            }
        }
        
        return $bonus;
    }

    /**
     * Helper to write to the database.
     */
    private function createBonusTransaction(Payment $payment, string $type, float $amount, float $multiplier, string $description): void
    {
        BonusTransaction::create([
            'user_id' => $payment->user_id,
            'subscription_id' => $payment->subscription_id,
            'payment_id' => $payment->id,
            'type' => $type,
            'amount' => $amount,
            'multiplier_applied' => $multiplier,
            'base_amount' => $payment->amount,
            'description' => $description
        ]);
    }
}