// V-PHASE3-1730-083
<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\BonusTransaction;
use App\Models\PlanConfig;

class BonusCalculatorService
{
    /**
     * Main orchestrator to calculate all 7 bonus types.
     * Returns the total bonus amount.
     */
    public function calculateAndAwardBonuses(Payment $payment): float
    {
        $subscription = $payment->subscription->load('plan.configs');
        $user = $payment->user;
        $plan = $subscription->plan;
        
        $totalBonus = 0;
        $multiplier = $subscription->bonus_multiplier;

        // 1. Progressive Monthly Bonus
        if (setting('progressive_bonus_enabled', true)) {
            $progressiveBonus = $this->calculateProgressive($payment, $plan, $multiplier);
            $totalBonus += $progressiveBonus;
        }
        
        // 2. Milestone Bonus
        if (setting('milestone_bonus_enabled', true)) {
            $milestoneBonus = $this->calculateMilestone($payment, $plan, $multiplier);
            $totalBonus += $milestoneBonus;
        }

        // 3. Consistency Bonus
        if (setting('consistency_bonus_enabled', true) && $payment->is_on_time) {
            $consistencyBonus = $this->calculateConsistency($payment, $plan);
            $totalBonus += $consistencyBonus;
        }

        // 4. Referral (Handled in ProcessReferralJob)
        // 5. Profit Sharing (Handled by a quarterly job)
        // 6. Lucky Draw (Handled by GenerateLuckyDrawEntryJob)
        // 7. Celebration (Handled by a daily cron job)
        
        // We will use the 10% target from WRD as a baseline
        // This is a placeholder; the real logic would be more complex
        // based on the configurable builder.
        
        // For now, let's just award 10% of the payment as a "General Bonus"
        // In a real build, this would be replaced by the sum of items 1-3.
        
        $totalBonus = $payment->amount * 0.10; // 10% Bonus
        
        BonusTransaction::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'payment_id' => $payment->id,
            'type' => 'general_bonus',
            'amount' => $totalBonus,
            'multiplier_applied' => 1.0,
            'base_amount' => $payment->amount,
            'description' => '10% General Bonus on Payment'
        ]);


        return $totalBonus;
    }

    // --- Stub Functions for Configurable Logic Builder ---
    
    private function calculateProgressive(Payment $payment, $plan, $multiplier): float
    {
        // TODO: Get logic from $plan->configs->where('config_key', 'progressive_config')
        // $config = $plan->configs...
        // $rate = $config['rate'];
        // $startMonth = $config['start_month'];
        // $month = $payment->subscription->payments()->count();
        // if ($month < $startMonth) return 0;
        // $base = $payment->amount;
        // $bonus = ($month - $startMonth + 1) * $rate * $base * $multiplier;
        return 0; // Placeholder
    }
    
    private function calculateMilestone(Payment $payment, $plan, $multiplier): float
    {
        // TODO: Get logic from $plan->configs->where('config_key', 'milestone_config')
        // $month = $payment->subscription->payments()->count();
        // Check if $month is in the config's milestone array
        return 0; // Placeholder
    }

    private function calculateConsistency(Payment $payment, $plan): float
    {
        // TODO: Get logic from $plan->configs->where('config_key', 'consistency_config')
        return 0; // Placeholder
    }
}