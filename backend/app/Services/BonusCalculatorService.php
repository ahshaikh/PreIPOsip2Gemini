<?php
// V-FINAL-1730-343 (Advanced Progressive Logic) | V-FINAL-1730-496 (Created) | V-FINAL-1730-586 (Notifications Added)

namespace App\Services;

use App\Models\Payment;
use App\Models\BonusTransaction;
use App\Models\PlanConfig;
use App\Notifications\BonusCredited; // <-- IMPORT
use Illuminate\Support\Facades\Log;

class BonusCalculatorService
{
    public function calculateAndAwardBonuses(Payment $payment): float
    {
        $subscription = $payment->subscription->load('plan.configs');
        $user = $payment->user;
        $plan = $subscription->plan;
        
        $totalBonus = 0;
        $multiplier = (float) $subscription->bonus_multiplier;

        // 1. Progressive Monthly Bonus
        if (setting('progressive_bonus_enabled', true)) {
            $progressiveBonus = $this->calculateProgressive($payment, $plan, $multiplier);
            if ($progressiveBonus > 0) {
                $totalBonus += $progressiveBonus;
                $this->createBonusTransaction($payment, 'progressive', $progressiveBonus, $multiplier, 'Progressive Monthly Bonus');
            }
        }
        
        // 2. Milestone Bonus
        if (setting('milestone_bonus_enabled', true)) {
            $milestoneBonus = $this->calculateMilestone($payment, $plan, $multiplier);
            if ($milestoneBonus > 0) {
                $totalBonus += $milestoneBonus;
                $this->createBonusTransaction($payment, 'milestone', $milestoneBonus, $multiplier, 'Milestone Bonus');
            }
        }

        // 3. Consistency Bonus
        // testLatePaymentSkipsConsistencyBonus: This check is correct.
        if (setting('consistency_bonus_enabled', true) && $payment->is_on_time) {
            $consistencyBonus = $this->calculateConsistency($payment, $plan);
            if ($consistencyBonus > 0) {
                $totalBonus += $consistencyBonus;
                $this->createBonusTransaction($payment, 'consistency', $consistencyBonus, 1.0, 'On-Time Payment Bonus');
            }
        }
        
        // --- NEW: Send Notification (Gap 3 Fix) ---
        if ($totalBonus > 0) {
            $user->notify(new BonusCredited($totalBonus, 'SIP'));
        }
        // ------------------------------------------
        
        Log::info("Total bonus calculated for Payment {$payment->id}: {$totalBonus}");
        return $totalBonus;
    }

    private function getPlanConfig($plan, $key, $default = null)
    {
        $config = $plan->configs->where('config_key', $key)->first();
        return $config ? $config->value : $default;
    }

    /**
     * 1. Calculates Progressive Bonus with Advanced Rules
     */
    private function calculateProgressive(Payment $payment, $plan, $multiplier): float
    {
        $config = $this->getPlanConfig($plan, 'progressive_config', [
            'rate' => 0.5, 'start_month' => 4, 'max_percentage' => 20, 'overrides' => []
        ]);
        
        $month = $payment->subscription->payments()->where('status', 'paid')->count();
        $startMonth = (int) $config['start_month'];
        
        if ($month < $startMonth) return 0;
        
        $overrides = $config['overrides'] ?? [];
        $baseRate = 0;

        if (isset($overrides[$month])) {
            $baseRate = (float) $overrides[$month];
        } else {
            $growthFactor = $month - $startMonth + 1;
            $baseRate = $growthFactor * ((float) $config['rate']);
        }

        $maxPercent = $config['max_percentage'] ?? 100;
        if ($baseRate > $maxPercent) $baseRate = $maxPercent;

        $base = (float) $payment->amount;
        $bonus = ($baseRate / 100) * $base * $multiplier;
        
        return round($bonus, 2);
    }
    
    /**
     * 2. Calculates Milestone Bonus
     */
    private function calculateMilestone(Payment $payment, $plan, $multiplier): float
    {
        $config = $this->getPlanConfig($plan, 'milestone_config', []);
        $month = $payment->subscription->payments()->where('status', 'paid')->count();
        
        foreach ($config as $milestone) {
            if ($month === (int)$milestone['month']) {
                if ($payment->subscription->consecutive_payments_count >= $month) {
                    return ((float)$milestone['amount']) * $multiplier;
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
        $config = $this->getPlanConfig($plan, 'consistency_config', ['amount_per_payment' => 0]);
        $bonus = (float) $config['amount_per_payment'];
        $streak = $payment->subscription->consecutive_payments_count;

        if (isset($config['streaks']) && is_array($config['streaks'])) {
            foreach ($config['streaks'] as $streakRule) {
                if ($streak === (int)$streakRule['months']) {
                    $bonus *= (float)$streakRule['multiplier'];
                    break;
                }
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