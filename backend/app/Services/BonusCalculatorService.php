<?php
// V-PHASE3-1730-083 (Created) | V-FINAL-1730-343 (Advanced Progressive Logic) | V-FINAL-1730-496 | V-FINAL-1730-586 (Notifications Added) | V-FIX-PHANTOM-MONEY (Gemini)

namespace App\Services;

use App\Models\Payment;
use App\Models\BonusTransaction;
use App\Models\PlanConfig;
use App\Notifications\BonusCredited;
use Illuminate\Support\Facades\Log;
// FIX: Import WalletService
use App\Services\WalletService;
// [P1.3 FIX]: Import TDS service for centralized tax calculations
use App\Services\TdsCalculationService;

/**
 * BonusCalculatorService
 *
 * [P1.1 FIX]: This is the CANONICAL production bonus calculator.
 * Do NOT confuse with App\Services\Bonuses\BonusSimulatorService (renamed from duplicate class name).
 *
 * This service handles the calculation and awarding of all bonus types in the platform.
 * It supports 7 different bonus types:
 *
 * 1. **Progressive Bonus**: Increases over time based on subscription tenure
 * - Configured via `progressive_config` on Plan
 * - Starts after `start_month` and grows by `rate`% per month
 * - Supports monthly overrides and max percentage cap
 *
 * 2. **Milestone Bonus**: One-time bonus at specific payment milestones
 * - Configured via `milestone_config` on Plan
 * - Requires consecutive payments to qualify
 *
 * 3. **Consistency Bonus**: Rewards on-time payments
 * - Configured via `consistency_config` on Plan
 * - Supports streak multipliers for longer streaks
 *
 * 4. **Referral Bonus**: (Handled by ReferralService)
 * 5. **Celebration Bonus**: (Handled by CelebrationService)
 * 6. **Jackpot/Lucky Draw**: (Handled by LuckyDrawService)
 * 7. **Profit Share**: (Handled by ProfitShareService)
 *
 * @package App\Services
 * @see \App\Models\BonusTransaction
 * @see \App\Models\PlanConfig
 */
class BonusCalculatorService
{
    /**
     * Maximum allowed bonus multiplier to prevent fraud.
     * This cap prevents malicious manipulation of bonus payouts.
     * Can be overridden via settings: `max_bonus_multiplier`
     */
    private const MAX_MULTIPLIER_CAP = 10.0;

    protected $walletService;
    protected $tdsService; // [P1.3 FIX]: TDS calculation service

    // FIX: Inject WalletService and TdsCalculationService
    public function __construct(WalletService $walletService, TdsCalculationService $tdsService)
    {
        $this->walletService = $walletService;
        $this->tdsService = $tdsService;
    }

    /**
     * Calculate and award all eligible bonuses for a payment.
     *
     * This is the main orchestrator method that triggers bonus calculations
     * after a successful payment. It checks eligibility for each bonus type
     * and creates BonusTransaction records for awarded bonuses.
     *
     * **Flow:**
     * 1. Load subscription and plan configuration
     * 2. Apply multiplier cap for fraud prevention
     * 3. Calculate Progressive Bonus (if enabled and eligible)
     * 4. Calculate Milestone Bonus (if enabled and eligible)
     * 5. Calculate Consistency Bonus (if enabled and payment is on-time)
     * 6. Send notification to user if any bonus awarded
     *
     * @param Payment $payment The payment that triggered bonus calculation
     * @return float Total bonus amount awarded (sum of all bonus types)
     *
     * @example
     * ```php
     * $bonusService = new BonusCalculatorService();
     * $totalBonus = $bonusService->calculateAndAwardBonuses($payment);
     * // Returns: 175.00 (e.g., 50 consistency + 25 progressive + 100 milestone)
     * ```
     */
    public function calculateAndAwardBonuses(Payment $payment): float
    {
        $subscription = $payment->subscription->load('plan.configs');
        $user = $payment->user;
        $plan = $subscription->plan;

        $totalBonus = 0;

        // --- SECURITY: Cap the multiplier to prevent fraud ---
        // This prevents malicious actors from setting unreasonably high multipliers
        $maxMultiplier = (float) setting('max_bonus_multiplier', self::MAX_MULTIPLIER_CAP);
        $rawMultiplier = (float) $subscription->bonus_multiplier;
        $multiplier = min($rawMultiplier, $maxMultiplier);

        if ($rawMultiplier > $maxMultiplier) {
            Log::warning("Bonus multiplier capped for Subscription {$subscription->id}: {$rawMultiplier} -> {$multiplier}");
        }

        // 0. Welcome Bonus (First Payment Only)
        $paidCount = $subscription->payments()->where('status', 'paid')->count();
        if ($paidCount === 1 && setting('welcome_bonus_enabled', true)) {
            $welcomeBonus = $this->calculateWelcomeBonus($payment, $plan);
            if ($welcomeBonus > 0) {
                $totalBonus += $welcomeBonus;
                $this->createBonusTransaction($payment, 'welcome_bonus', $welcomeBonus, 1.0, 'Welcome Bonus - First Investment');
            }

            // Also award referral bonus to referrer if this user was referred
            if (setting('referral_enabled', true) && setting('referral_bonus_enabled', true)) {
                $referralBonus = $this->awardReferralBonus($payment);
                // Note: Referral bonus is awarded to referrer, not counted in this user's total
                if ($referralBonus > 0) {
                    Log::info("Referral bonus of ₹{$referralBonus} awarded for Payment {$payment->id}");
                }
            }
        }

        // 1. Progressive Monthly Bonus (mapped as loyalty_bonus for frontend)
        if (setting('progressive_bonus_enabled', true)) {
            $progressiveBonus = $this->calculateProgressive($payment, $plan, $multiplier);
            if ($progressiveBonus > 0) {
                $totalBonus += $progressiveBonus;
                $this->createBonusTransaction($payment, 'loyalty_bonus', $progressiveBonus, $multiplier, 'Loyalty Bonus - Month ' . $paidCount);
            }
        }

        // 2. Milestone Bonus
        if (setting('milestone_bonus_enabled', true)) {
            $milestoneBonus = $this->calculateMilestone($payment, $plan, $multiplier);
            if ($milestoneBonus > 0) {
                $totalBonus += $milestoneBonus;
                $this->createBonusTransaction($payment, 'milestone_bonus', $milestoneBonus, $multiplier, 'Milestone Bonus - Payment #' . $paidCount);
            }
        }

        // 3. Consistency Bonus (mapped as cashback for frontend)
        // testLatePaymentSkipsConsistencyBonus: This check is correct.
        if (setting('consistency_bonus_enabled', true) && $payment->is_on_time) {
            $consistencyBonus = $this->calculateConsistency($payment, $plan);
            if ($consistencyBonus > 0) {
                $totalBonus += $consistencyBonus;
                $this->createBonusTransaction($payment, 'cashback', $consistencyBonus, 1.0, 'Cashback - On-Time Payment');
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
     * Apply rounding based on settings
     */
    private function applyRounding(float $amount): float
    {
        $decimals = (int) setting('bonus_rounding_decimals', 2);
        $mode = setting('bonus_rounding_mode', 'round');

        return match ($mode) {
            'floor' => floor($amount * pow(10, $decimals)) / pow(10, $decimals),
            'ceil' => ceil($amount * pow(10, $decimals)) / pow(10, $decimals),
            default => round($amount, $decimals),
        };
    }

    /**
     * V-AUDIT-MODULE8-004 (HIGH): Calculate bonuses for testing without persisting.
     *
     * This method is used by AdminBonusController::calculateTest to preview bonuses
     * without creating database records. Eliminates code duplication.
     *
     * @param array $params Test parameters
     * @return array Breakdown of bonus calculations
     */
    public function calculateTestBonuses(array $params): array
    {
        $plan = \App\Models\Plan::with('configs')->findOrFail($params['plan_id']);
        $amount = (float) $params['payment_amount'];
        $month = (int) $params['payment_month'];
        $multiplier = (float) ($params['bonus_multiplier'] ?? 1.0);
        $consecutivePayments = (int) ($params['consecutive_payments'] ?? $month);
        $isOnTime = (bool) $params['is_on_time'];

        $bonuses = [];
        $totalBonus = 0;

        // Apply multiplier cap
        $maxMultiplier = (float) setting('max_bonus_multiplier', 10.0);
        $multiplier = min($multiplier, $maxMultiplier);

        // 1. Progressive Bonus
        if (setting('progressive_bonus_enabled', true)) {
            $config = $plan->getConfig('progressive_config', [
                'rate' => 0.5, 'start_month' => 4, 'max_percentage' => 20, 'overrides' => []
            ]);

            $startMonth = (int) $config['start_month'];

            if ($month >= $startMonth) {
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

                $progressiveBonus = ($baseRate / 100) * $amount * $multiplier;
                $progressiveBonus = $this->applyRounding($progressiveBonus);

                if ($progressiveBonus > 0) {
                    $bonuses[] = [
                        'type' => 'progressive',
                        'amount' => $progressiveBonus,
                        'calculation' => "{$baseRate}% × ₹{$amount} × {$multiplier}x = ₹{$progressiveBonus}",
                    ];
                    $totalBonus += $progressiveBonus;
                }
            }
        }

        // 2. Milestone Bonus
        if (setting('milestone_bonus_enabled', true)) {
            $config = $plan->getConfig('milestone_config', []);

            foreach ($config as $milestone) {
                if ($month === (int)$milestone['month']) {
                    if ($consecutivePayments >= $month) {
                        $milestoneBonus = ((float)$milestone['amount']) * $multiplier;
                        $milestoneBonus = $this->applyRounding($milestoneBonus);

                        $bonuses[] = [
                            'type' => 'milestone',
                            'amount' => $milestoneBonus,
                            'calculation' => "₹{$milestone['amount']} × {$multiplier}x = ₹{$milestoneBonus}",
                        ];
                        $totalBonus += $milestoneBonus;
                    }
                }
            }
        }

        // 3. Consistency Bonus
        if (setting('consistency_bonus_enabled', true) && $isOnTime) {
            $config = $plan->getConfig('consistency_config', ['amount_per_payment' => 0]);
            $consistencyBonus = (float) $config['amount_per_payment'];

            if (isset($config['streaks']) && is_array($config['streaks'])) {
                foreach ($config['streaks'] as $streakRule) {
                    if ($consecutivePayments === (int)$streakRule['months']) {
                        $consistencyBonus *= (float)$streakRule['multiplier'];
                        break;
                    }
                }
            }

            $consistencyBonus = $this->applyRounding($consistencyBonus);

            if ($consistencyBonus > 0) {
                $bonuses[] = [
                    'type' => 'consistency',
                    'amount' => $consistencyBonus,
                    'calculation' => "₹{$config['amount_per_payment']} (with streak multiplier) = ₹{$consistencyBonus}",
                ];
                $totalBonus += $consistencyBonus;
            }
        }

        return [
            'total_bonus' => $totalBonus,
            'bonuses' => $bonuses,
            'settings' => [
                'multiplier_applied' => $multiplier,
                'max_multiplier_cap' => $maxMultiplier,
                'rounding_decimals' => setting('bonus_rounding_decimals', 2),
                'rounding_mode' => setting('bonus_rounding_mode', 'round'),
            ],
        ];
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

        return $this->applyRounding($bonus);
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
                    $bonus = ((float)$milestone['amount']) * $multiplier;
                    return $this->applyRounding($bonus);
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
        return $this->applyRounding($bonus);
    }

    /**
     * 0. Calculates Welcome Bonus (First Payment)
     */
    private function calculateWelcomeBonus(Payment $payment, $plan): float
    {
        // Get welcome bonus configuration
        $config = $this->getPlanConfig($plan, 'welcome_bonus_config', ['amount' => 500]);

        // Default to 500 if not configured
        $welcomeAmount = (float) ($config['amount'] ?? 500);

        return $this->applyRounding($welcomeAmount);
    }

    /**
     * Award Referral Bonus to Referrer
     * Called when a referred user makes a successful payment
     * Supports configurable completion criteria
     *
     * @param Payment $payment The payment made by the referred user
     * @return float The bonus amount awarded to referrer
     */
    public function awardReferralBonus(Payment $payment): float
    {
        $referredUser = $payment->user;
        $subscription = $payment->subscription;

        // Find if this user was referred by someone
        $referral = \App\Models\Referral::where('referred_id', $referredUser->id)
            ->where('status', 'pending')
            ->first();

        if (!$referral) {
            return 0; // No referral found or already completed
        }

        // Check if completion criteria is met
        $criteria = setting('referral_completion_criteria', 'first_payment');
        $threshold = (int) setting('referral_completion_threshold', 1);

        $criteriaMetCondition = match ($criteria) {
            'nth_payment' => $subscription->payments()->where('status', 'paid')->count() >= $threshold,
            'total_amount' => $subscription->payments()->where('status', 'paid')->sum('amount') >= $threshold,
            default => $subscription->payments()->where('status', 'paid')->count() === 1, // first_payment
        };

        if (!$criteriaMetCondition) {
            return 0; // Criteria not met yet
        }

        // Mark referral as completed
        $referral->complete();

        $referrer = $referral->referrer;

        // Get referral bonus configuration from settings or default
        $referralBonusAmount = (float) setting('referral_bonus_amount', 1000);

        // Check if there's an active campaign with higher bonus
        $activeCampaign = \App\Models\ReferralCampaign::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if ($activeCampaign) {
            $referralBonusAmount = max($referralBonusAmount, (float) $activeCampaign->bonus_amount);
            $referralBonusAmount *= (float) $activeCampaign->multiplier;
        }

        // Apply tier-based multiplier from plan config
        $plan = $payment->subscription->plan;
        $referralConfig = $plan->getConfig('referral_config', []);
        if (!empty($referralConfig['tiers'])) {
            // Count total successful referrals for the referrer
            $successfulReferrals = \App\Models\Referral::where('referrer_id', $referrer->id)
                ->where('status', 'completed')
                ->count();

            // Find applicable tier (highest tier where min_referrals <= total)
            $applicableTier = null;
            foreach ($referralConfig['tiers'] as $tier) {
                if ($successfulReferrals >= $tier['min_referrals']) {
                    if (!$applicableTier || $tier['min_referrals'] > $applicableTier['min_referrals']) {
                        $applicableTier = $tier;
                    }
                }
            }

            if ($applicableTier && isset($applicableTier['multiplier'])) {
                $tierMultiplier = (float) $applicableTier['multiplier'];
                $referralBonusAmount *= $tierMultiplier;
                $referralBonusAmount = $this->applyRounding($referralBonusAmount); // Rounding
                Log::info("Applied referral tier '{$applicableTier['name']}' ({$tierMultiplier}x) for {$successfulReferrals} successful referrals");
            }
        }

        // Apply rounding to referral bonus before creating transaction
        $referralBonusAmount = $this->applyRounding($referralBonusAmount);

        // [P1.3 FIX]: Use TdsCalculationService instead of hardcoded calculation
        $tdsCalculation = $this->tdsService->calculate($referralBonusAmount, 'referral');
        $tdsAmount = $tdsCalculation['tds'];
        $netAmount = $tdsCalculation['net'];

        // Create bonus transaction for referrer
        $bonusTxn = \App\Models\BonusTransaction::create([
            'user_id' => $referrer->id,
            'subscription_id' => $payment->subscription_id,
            'payment_id' => $payment->id,
            'type' => 'referral_bonus',
            'amount' => $referralBonusAmount, // V-AUDIT-MODULE8-001: Gross amount
            'tds_deducted' => $tdsAmount, // V-AUDIT-MODULE8-001: TDS for compliance
            'multiplier_applied' => 1.0,
            'base_amount' => $payment->amount,
            'description' => "Referral Bonus - {$referredUser->username} met completion criteria: {$criteria}"
        ]);

        // V-AUDIT-MODULE8-001: Deposit only NET amount to wallet (after TDS)
        $this->walletService->deposit(
            $referrer,
            $netAmount, // V-AUDIT-MODULE8-001: Credit net amount only
            'bonus_credit',
            "Referral Bonus - Invited {$referredUser->username}" . ($tdsAmount > 0 ? " (TDS ₹{$tdsAmount} deducted)" : ""),
            $bonusTxn
        );

        // Send notification to referrer
        $referrer->notify(new \App\Notifications\BonusCredited($referralBonusAmount, 'Referral'));

        Log::info("Referral bonus awarded: ₹{$referralBonusAmount} to User {$referrer->id} for referring User {$referredUser->id}. Criteria: {$criteria}");

        return $referralBonusAmount;
    }

    /**
     * V-AUDIT-MODULE8-001 (CRITICAL): Helper to write to the database with TDS deduction.
     *
     * TDS (Tax Deducted at Source) Compliance:
     * - In India, commissions and bonuses typically attract 5-10% TDS
     * - System must deduct TDS before crediting wallet (legal requirement)
     * - TDS is stored in tds_deducted field for audit and tax reporting
     *
     * Flow:
     * 1. Calculate TDS based on bonus_tds_percentage setting
     * 2. Store gross amount and TDS in BonusTransaction
     * 3. Deposit only net_amount (amount - TDS) to wallet
     *
     * Example:
     * - Gross Bonus: ₹1000
     * - TDS (10%): ₹100
     * - Net Credit: ₹900
     */
    private function createBonusTransaction(Payment $payment, string $type, float $amount, float $multiplier, string $description): void
    {
        // [P1.3 FIX]: Use TdsCalculationService instead of hardcoded calculation
        $tdsCalculation = $this->tdsService->calculate($amount, 'bonus');
        $tdsAmount = $tdsCalculation['tds'];
        $netAmount = $tdsCalculation['net'];

        $bonusTxn = BonusTransaction::create([
            'user_id' => $payment->user_id,
            'subscription_id' => $payment->subscription_id,
            'payment_id' => $payment->id,
            'type' => $type,
            'amount' => $amount, // V-AUDIT-MODULE8-001: Gross amount (before TDS)
            'tds_deducted' => $tdsAmount, // V-AUDIT-MODULE8-001: TDS amount for compliance
            'multiplier_applied' => $multiplier,
            'base_amount' => $payment->amount,
            'description' => $description
        ]);

        // V-AUDIT-MODULE8-001: Deposit only NET amount to wallet (after TDS deduction)
        // This ensures legal compliance - TDS is withheld and will be remitted to tax authorities
        // FIX: This also fixes the "Phantom Money" bug where bonuses were logged but not usable
        $this->walletService->deposit(
            $payment->user,
            $netAmount, // V-AUDIT-MODULE8-001: Credit net amount only
            'bonus_credit',
            $description . ($tdsAmount > 0 ? " (TDS ₹{$tdsAmount} deducted)" : ""),
            $bonusTxn
        );

        Log::info("Bonus created: Gross=₹{$amount}, TDS=₹{$tdsAmount} ({$tdsPercentage}%), Net=₹{$netAmount}", [
            'user_id' => $payment->user_id,
            'type' => $type,
            'bonus_id' => $bonusTxn->id
        ]);
    }
}