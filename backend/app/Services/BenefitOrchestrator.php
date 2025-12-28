<?php
/**
 * Benefit Orchestrator - Single Authority for All Campaign Benefits
 *
 * [D.11-D.15 FIX]: Campaign system coherence and liability tracking
 *
 * PROTOCOL ENFORCEMENT:
 * - D.11: One authority decides benefit eligibility (not independent checks)
 * - D.12: Benefits applied in causal order (precedence rules)
 * - D.13: Illegal stacking prevented (exclusivity constraints)
 * - D.14: Full audit trail (replayable decisions)
 * - D.15: Costs recorded as admin liabilities (in AdminLedger)
 *
 * DESIGN PRINCIPLE:
 * - Single source of truth for benefit calculations
 * - Explicit precedence rules (no ambiguity)
 * - Every decision logged with full provenance
 * - Benefits are admin EXPENSES (not free money)
 */

namespace App\Services;

use App\Models\User;
use App\Models\Investment;
use App\Models\Campaign;
use App\Models\Referral;
use App\Services\Accounting\AdminLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BenefitOrchestrator
{
    private AdminLedger $adminLedger;

    public function __construct(AdminLedger $adminLedger)
    {
        $this->adminLedger = $adminLedger;
    }

    /**
     * Calculate applicable benefit for investment
     *
     * SINGLE AUTHORITY: This method is the ONLY place benefit decisions are made
     *
     * PRECEDENCE RULES (enforced in order):
     * 1. Promotional campaigns (time-limited, higher value)
     * 2. Referral bonuses (ongoing, standard rates)
     * 3. Default: No benefit
     *
     * STACKING RULES:
     * - Promotional campaigns are EXCLUSIVE (only one applies)
     * - Referral bonus CANNOT stack with promotional campaign
     * - Maximum benefit cap: 20% of investment amount (configurable)
     *
     * @param User $user
     * @param Investment $investment
     * @return BenefitCalculationResult
     */
    public function calculateApplicableBenefit(User $user, Investment $investment): BenefitCalculationResult
    {
        Log::info("BENEFIT ORCHESTRATOR: Calculating applicable benefit", [
            'user_id' => $user->id,
            'investment_id' => $investment->id,
            'investment_amount' => $investment->total_amount,
        ]);

        // STEP 1: Check promotional campaigns (highest precedence)
        $promotionalResult = $this->evaluatePromotionalCampaigns($user, $investment);

        if ($promotionalResult->hasApplicableBenefit()) {
            // Promotional campaign found and applies
            $this->logBenefitDecision($user, $investment, $promotionalResult, 'promotional_campaign_applied');
            return $promotionalResult;
        }

        // STEP 2: Check referral bonus (second precedence)
        $referralResult = $this->evaluateReferralBonus($user, $investment);

        if ($referralResult->hasApplicableBenefit()) {
            // Referral bonus applies
            $this->logBenefitDecision($user, $investment, $referralResult, 'referral_bonus_applied');
            return $referralResult;
        }

        // STEP 3: No benefit applies
        $noBenefitResult = BenefitCalculationResult::noBenefit($investment->total_amount);
        $this->logBenefitDecision($user, $investment, $noBenefitResult, 'no_benefit_applicable');

        return $noBenefitResult;
    }

    /**
     * Evaluate promotional campaigns
     *
     * @param User $user
     * @param Investment $investment
     * @return BenefitCalculationResult
     */
    private function evaluatePromotionalCampaigns(User $user, Investment $investment): BenefitCalculationResult
    {
        // Get all active promotional campaigns
        $activeCampaigns = Campaign::where('type', 'promotional')
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderBy('discount_percentage', 'desc') // Highest discount first
            ->get();

        if ($activeCampaigns->isEmpty()) {
            return BenefitCalculationResult::noBenefit($investment->total_amount);
        }

        // Evaluate each campaign for eligibility
        foreach ($activeCampaigns as $campaign) {
            $eligibility = $this->checkCampaignEligibility($user, $investment, $campaign);

            if ($eligibility['eligible']) {
                // Calculate benefit
                $benefitAmount = $this->calculateCampaignBenefit($investment->total_amount, $campaign);
                $finalAmount = $investment->total_amount - $benefitAmount;

                // Apply maximum benefit cap
                // [CRITICAL FIX]: Use BOTH configuration AND invariant bounds
                // Configuration sets policy limit (e.g., 20%)
                // Invariant sets system-level safety limit (25% HARD MAXIMUM)
                // This prevents misconfigured settings from allowing excessive discounts
                $configuredMaxPercent = (float) setting('max_benefit_percentage', 20);
                $invariantMaxPercent = 25; // HARD UPPER LIMIT - cannot be bypassed by configuration

                $maxBenefitPercent = min($configuredMaxPercent, $invariantMaxPercent);
                $maxBenefitAmount = $investment->total_amount * ($maxBenefitPercent / 100);

                if ($benefitAmount > $maxBenefitAmount) {
                    Log::warning("BENEFIT CAPPED: Campaign benefit exceeds maximum", [
                        'campaign_id' => $campaign->id,
                        'calculated_benefit' => $benefitAmount,
                        'configured_max_percent' => $configuredMaxPercent,
                        'invariant_max_percent' => $invariantMaxPercent,
                        'effective_max_percent' => $maxBenefitPercent,
                        'max_allowed_amount' => $maxBenefitAmount,
                        'capped_by' => $configuredMaxPercent > $invariantMaxPercent ? 'invariant' : 'configuration',
                    ]);
                    $benefitAmount = $maxBenefitAmount;
                    $finalAmount = $investment->total_amount - $benefitAmount;
                }

                return BenefitCalculationResult::fromCampaign(
                    campaign: $campaign,
                    originalAmount: $investment->total_amount,
                    benefitAmount: $benefitAmount,
                    finalAmount: $finalAmount,
                    eligibilityReason: $eligibility['reason'],
                    metadata: $eligibility['metadata']
                );
            }
        }

        // No eligible campaign found
        return BenefitCalculationResult::noBenefit($investment->total_amount);
    }

    /**
     * Evaluate referral bonus
     *
     * @param User $user
     * @param Investment $investment
     * @return BenefitCalculationResult
     */
    private function evaluateReferralBonus(User $user, Investment $investment): BenefitCalculationResult
    {
        // Check if user was referred
        $referral = Referral::where('referred_user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$referral) {
            return BenefitCalculationResult::noBenefit($investment->total_amount);
        }

        // Check if this is the user's first investment (typical referral bonus condition)
        $isFirstInvestment = Investment::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count() === 0;

        if (!$isFirstInvestment) {
            // Referral bonus only on first investment
            return BenefitCalculationResult::noBenefit($investment->total_amount);
        }

        // Get referral bonus percentage from settings
        $referralBonusPercent = (float) setting('referral_bonus_percentage', 5);
        $benefitAmount = $investment->total_amount * ($referralBonusPercent / 100);
        $finalAmount = $investment->total_amount - $benefitAmount;

        // Apply maximum benefit cap
        // [CRITICAL FIX]: Use BOTH configuration AND invariant bounds
        $configuredMaxPercent = (float) setting('max_benefit_percentage', 20);
        $invariantMaxPercent = 25; // HARD UPPER LIMIT - cannot be bypassed by configuration

        $maxBenefitPercent = min($configuredMaxPercent, $invariantMaxPercent);
        $maxBenefitAmount = $investment->total_amount * ($maxBenefitPercent / 100);

        if ($benefitAmount > $maxBenefitAmount) {
            Log::warning("REFERRAL BENEFIT CAPPED", [
                'configured_max_percent' => $configuredMaxPercent,
                'invariant_max_percent' => $invariantMaxPercent,
                'effective_max_percent' => $maxBenefitPercent,
                'capped_by' => $configuredMaxPercent > $invariantMaxPercent ? 'invariant' : 'configuration',
            ]);
            $benefitAmount = $maxBenefitAmount;
            $finalAmount = $investment->total_amount - $benefitAmount;
        }

        return BenefitCalculationResult::fromReferral(
            referral: $referral,
            originalAmount: $investment->total_amount,
            benefitAmount: $benefitAmount,
            finalAmount: $finalAmount,
            eligibilityReason: 'First investment via referral link',
            metadata: [
                'referrer_id' => $referral->referrer_user_id,
                'referral_code' => $referral->referral_code,
                'is_first_investment' => true,
            ]
        );
    }

    /**
     * Check campaign eligibility
     *
     * @param User $user
     * @param Investment $investment
     * @param Campaign $campaign
     * @return array ['eligible' => bool, 'reason' => string, 'metadata' => array]
     */
    private function checkCampaignEligibility(User $user, Investment $investment, Campaign $campaign): array
    {
        // Check 1: User must have approved KYC (compliance requirement)
        if ($user->kyc_status !== 'approved') {
            return [
                'eligible' => false,
                'reason' => 'KYC not approved',
                'metadata' => ['kyc_status' => $user->kyc_status],
            ];
        }

        // Check 2: Investment amount must meet campaign minimum
        if ($campaign->min_investment_amount && $investment->total_amount < $campaign->min_investment_amount) {
            return [
                'eligible' => false,
                'reason' => 'Investment amount below campaign minimum',
                'metadata' => [
                    'investment_amount' => $investment->total_amount,
                    'campaign_minimum' => $campaign->min_investment_amount,
                ],
            ];
        }

        // Check 3: Campaign usage limit (if applicable)
        // [D.13]: Count only non-reversed usages (reversed = saga compensation)
        if ($campaign->max_uses_per_user) {
            $usageCount = DB::table('campaign_usages')
                ->where('campaign_id', $campaign->id)
                ->where('user_id', $user->id)
                ->where('is_reversed', false) // Exclude compensated usages
                ->count();

            if ($usageCount >= $campaign->max_uses_per_user) {
                return [
                    'eligible' => false,
                    'reason' => 'Campaign usage limit reached',
                    'metadata' => [
                        'usage_count' => $usageCount,
                        'max_uses' => $campaign->max_uses_per_user,
                    ],
                ];
            }
        }

        // Check 4: Campaign global limit (if applicable)
        // [D.13]: Count only non-reversed usages
        if ($campaign->max_total_uses) {
            $totalUsageCount = DB::table('campaign_usages')
                ->where('campaign_id', $campaign->id)
                ->where('is_reversed', false) // Exclude compensated usages
                ->count();

            if ($totalUsageCount >= $campaign->max_total_uses) {
                return [
                    'eligible' => false,
                    'reason' => 'Campaign global usage limit reached',
                    'metadata' => [
                        'total_usage' => $totalUsageCount,
                        'max_total' => $campaign->max_total_uses,
                    ],
                ];
            }
        }

        // Check 5: User eligibility criteria (if campaign has specific rules)
        if ($campaign->eligibility_criteria) {
            $criteriaCheck = $this->evaluateEligibilityCriteria($user, $campaign->eligibility_criteria);
            if (!$criteriaCheck['met']) {
                return [
                    'eligible' => false,
                    'reason' => $criteriaCheck['reason'],
                    'metadata' => $criteriaCheck['metadata'],
                ];
            }
        }

        // All checks passed
        return [
            'eligible' => true,
            'reason' => 'All campaign eligibility criteria met',
            'metadata' => [
                'campaign_type' => $campaign->type,
                'campaign_name' => $campaign->name,
                'discount_type' => $campaign->discount_type ?? 'percentage',
            ],
        ];
    }

    /**
     * Calculate campaign benefit amount
     *
     * @param float $investmentAmount
     * @param Campaign $campaign
     * @return float Benefit amount
     */
    private function calculateCampaignBenefit(float $investmentAmount, Campaign $campaign): float
    {
        if ($campaign->discount_type === 'percentage') {
            return $investmentAmount * ($campaign->discount_percentage / 100);
        } elseif ($campaign->discount_type === 'fixed') {
            return min($campaign->discount_fixed_amount, $investmentAmount);
        }

        return 0;
    }

    /**
     * Evaluate custom eligibility criteria
     *
     * @param User $user
     * @param mixed $criteria JSON criteria object
     * @return array
     */
    private function evaluateEligibilityCriteria(User $user, $criteria): array
    {
        // Placeholder for custom criteria evaluation
        // In production: implement complex rule engine

        // Example criteria:
        // - First-time investor
        // - Investment amount range
        // - User registration date range
        // - Specific user segments

        return [
            'met' => true,
            'reason' => 'Custom criteria met',
            'metadata' => [],
        ];
    }

    /**
     * Log benefit decision for audit trail
     *
     * [D.14]: Make benefits auditable and replayable
     *
     * @param User $user
     * @param Investment $investment
     * @param BenefitCalculationResult $result
     * @param string $decision
     * @return void
     */
    private function logBenefitDecision(
        User $user,
        Investment $investment,
        BenefitCalculationResult $result,
        string $decision
    ): void {
        $logData = [
            'user_id' => $user->id,
            'investment_id' => $investment->id,
            'decision' => $decision,
            'benefit_type' => $result->getBenefitType(),
            'original_amount' => $result->getOriginalAmount(),
            'benefit_amount' => $result->getBenefitAmount(),
            'final_amount' => $result->getFinalAmount(),
            'eligibility_reason' => $result->getEligibilityReason(),
            'metadata' => $result->getMetadata(),
            'timestamp' => now()->toDateTimeString(),
        ];

        // Log to application logs
        Log::info("BENEFIT DECISION: {$decision}", $logData);

        // Store in database for auditing and replay
        DB::table('benefit_audit_log')->insert([
            'user_id' => $user->id,
            'investment_id' => $investment->id,
            'benefit_type' => $result->getBenefitType(),
            'decision' => $decision,
            'original_amount' => $result->getOriginalAmount(),
            'benefit_amount' => $result->getBenefitAmount(),
            'final_amount' => $result->getFinalAmount(),
            'eligibility_reason' => $result->getEligibilityReason(),
            'metadata' => json_encode($logData),
            'created_at' => now(),
        ]);
    }

    /**
     * Record campaign usage and cost as admin liability
     *
     * [D.15]: Account for campaign costs as admin liabilities
     *
     * @param BenefitCalculationResult $result
     * @param Investment $investment
     * @return void
     */
    public function recordCampaignUsageAndCost(
        BenefitCalculationResult $result,
        Investment $investment
    ): void {
        if (!$result->hasApplicableBenefit()) {
            // No benefit to record
            return;
        }

        DB::transaction(function () use ($result, $investment) {
            // Record campaign usage
            $usageId = DB::table('campaign_usages')->insertGetId([
                'campaign_id' => $result->getCampaignId(),
                'referral_id' => $result->getReferralId(),
                'user_id' => $investment->user_id,
                'investment_id' => $investment->id,
                'benefit_type' => $result->getBenefitType(),
                'benefit_amount' => $result->getBenefitAmount(),
                'eligibility_reason' => $result->getEligibilityReason(),
                'metadata' => json_encode($result->getMetadata()),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // [D.15]: Record in AdminLedger as EXPENSE and LIABILITY
            // Campaign discount is admin COST (foregone revenue)
            $this->adminLedger->recordCampaignDiscount(
                discountAmount: $result->getBenefitAmount(),
                campaignUsageId: $usageId,
                investmentId: $investment->id,
                description: "Campaign benefit: {$result->getBenefitType()} for investment #{$investment->id}"
            );

            Log::info("CAMPAIGN COST RECORDED", [
                'usage_id' => $usageId,
                'benefit_type' => $result->getBenefitType(),
                'benefit_amount' => $result->getBenefitAmount(),
                'investment_id' => $investment->id,
                'admin_expense' => true,
            ]);
        });
    }
}
