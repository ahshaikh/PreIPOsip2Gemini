<?php
/**
 * Benefit Calculation Result - Immutable Value Object
 *
 * [D.14]: Auditable and replayable benefit decisions
 *
 * DESIGN:
 * - Immutable (all properties readonly)
 * - Self-documenting (getters explain what they return)
 * - Serializable (can be stored and replayed)
 */

namespace App\Services;

use App\Models\Campaign;
use App\Models\Referral;

class BenefitCalculationResult
{
    private function __construct(
        private readonly string $benefitType,
        private readonly ?int $campaignId,
        private readonly ?int $referralId,
        private readonly float $originalAmount,
        private readonly float $benefitAmount,
        private readonly float $finalAmount,
        private readonly string $eligibilityReason,
        private readonly array $metadata
    ) {}

    /**
     * Create result from promotional campaign
     */
    public static function fromCampaign(
        Campaign $campaign,
        float $originalAmount,
        float $benefitAmount,
        float $finalAmount,
        string $eligibilityReason,
        array $metadata = []
    ): self {
        return new self(
            benefitType: 'promotional_campaign',
            campaignId: $campaign->id,
            referralId: null,
            originalAmount: $originalAmount,
            benefitAmount: $benefitAmount,
            finalAmount: $finalAmount,
            eligibilityReason: $eligibilityReason,
            metadata: array_merge($metadata, [
                'campaign_name' => $campaign->name,
                'campaign_type' => $campaign->type,
                'discount_percentage' => $campaign->discount_percentage ?? null,
            ])
        );
    }

    /**
     * Create result from referral bonus
     */
    public static function fromReferral(
        Referral $referral,
        float $originalAmount,
        float $benefitAmount,
        float $finalAmount,
        string $eligibilityReason,
        array $metadata = []
    ): self {
        return new self(
            benefitType: 'referral_bonus',
            campaignId: null,
            referralId: $referral->id,
            originalAmount: $originalAmount,
            benefitAmount: $benefitAmount,
            finalAmount: $finalAmount,
            eligibilityReason: $eligibilityReason,
            metadata: $metadata
        );
    }

    /**
     * Create result when no benefit applies
     */
    public static function noBenefit(float $originalAmount): self
    {
        return new self(
            benefitType: 'none',
            campaignId: null,
            referralId: null,
            originalAmount: $originalAmount,
            benefitAmount: 0,
            finalAmount: $originalAmount,
            eligibilityReason: 'No applicable benefit',
            metadata: []
        );
    }

    /**
     * Check if this result has an applicable benefit
     */
    public function hasApplicableBenefit(): bool
    {
        return $this->benefitType !== 'none' && $this->benefitAmount > 0;
    }

    /**
     * Get benefit type
     */
    public function getBenefitType(): string
    {
        return $this->benefitType;
    }

    /**
     * Get campaign ID (null if not a campaign benefit)
     */
    public function getCampaignId(): ?int
    {
        return $this->campaignId;
    }

    /**
     * Get referral ID (null if not a referral benefit)
     */
    public function getReferralId(): ?int
    {
        return $this->referralId;
    }

    /**
     * Get original investment amount (before benefit)
     */
    public function getOriginalAmount(): float
    {
        return $this->originalAmount;
    }

    /**
     * Get benefit amount (discount/bonus)
     */
    public function getBenefitAmount(): float
    {
        return $this->benefitAmount;
    }

    /**
     * Get final amount (after benefit applied)
     */
    public function getFinalAmount(): float
    {
        return $this->finalAmount;
    }

    /**
     * Get eligibility reason (why this benefit was/wasn't granted)
     */
    public function getEligibilityReason(): string
    {
        return $this->eligibilityReason;
    }

    /**
     * Get metadata (additional context about the decision)
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Serialize to array for storage/logging
     */
    public function toArray(): array
    {
        return [
            'benefit_type' => $this->benefitType,
            'campaign_id' => $this->campaignId,
            'referral_id' => $this->referralId,
            'original_amount' => $this->originalAmount,
            'benefit_amount' => $this->benefitAmount,
            'final_amount' => $this->finalAmount,
            'eligibility_reason' => $this->eligibilityReason,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Serialize to JSON for API responses
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Create human-readable explanation of benefit decision
     *
     * [D.14]: System must explain exactly why a benefit was granted
     */
    public function explain(): string
    {
        if (!$this->hasApplicableBenefit()) {
            return "No benefit applied. Reason: {$this->eligibilityReason}";
        }

        $benefitPercent = ($this->benefitAmount / $this->originalAmount) * 100;

        $explanation = sprintf(
            "Benefit: %s\n" .
            "Original Amount: ₹%.2f\n" .
            "Benefit Amount: ₹%.2f (%.1f%%)\n" .
            "Final Amount: ₹%.2f\n" .
            "Reason: %s",
            $this->benefitType,
            $this->originalAmount,
            $this->benefitAmount,
            $benefitPercent,
            $this->finalAmount,
            $this->eligibilityReason
        );

        if (!empty($this->metadata)) {
            $explanation .= "\n\nAdditional Details:\n";
            foreach ($this->metadata as $key => $value) {
                $explanation .= "- {$key}: " . json_encode($value) . "\n";
            }
        }

        return $explanation;
    }
}
