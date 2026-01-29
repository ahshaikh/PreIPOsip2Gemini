<?php
/**
 * STORY 4.1: Deal Tier Gate Exception
 *
 * GOVERNANCE INVARIANT:
 * - Deal creation requires company.disclosure_tier >= tier_1_upcoming
 * - Deal activation requires company.disclosure_tier >= tier_2_live
 * - Deal featured requires company.disclosure_tier >= tier_3_featured
 *
 * This exception is thrown when a deal operation violates tier gate requirements.
 * These are HARD FAILURES - the operation must not proceed.
 */

namespace App\Exceptions;

use App\Enums\DisclosureTier;
use DomainException;

class DealTierGateException extends DomainException
{
    public function __construct(
        string $message,
        public readonly int $companyId,
        public readonly DisclosureTier $currentTier,
        public readonly DisclosureTier $requiredTier,
        public readonly string $operation,
    ) {
        parent::__construct($message);
    }

    /**
     * Deal creation requires tier_1_upcoming or higher.
     */
    public static function creationRequiresTier1(int $companyId, DisclosureTier $currentTier): self
    {
        $requiredLabel = DisclosureTier::TIER_1_UPCOMING->label();

        return new self(
            message: "Deal creation blocked: Company (ID: {$companyId}) has disclosure tier '{$currentTier->label()}'. " .
                     "Deal creation requires at least '{$requiredLabel}'. " .
                     "Company must submit required disclosures before deals can be created.",
            companyId: $companyId,
            currentTier: $currentTier,
            requiredTier: DisclosureTier::TIER_1_UPCOMING,
            operation: 'create',
        );
    }

    /**
     * Deal activation requires tier_2_live or higher.
     */
    public static function activationRequiresTier2(int $companyId, DisclosureTier $currentTier): self
    {
        $requiredLabel = DisclosureTier::TIER_2_LIVE->label();

        return new self(
            message: "Deal activation blocked: Company (ID: {$companyId}) has disclosure tier '{$currentTier->label()}'. " .
                     "Activating deals for public investment requires at least '{$requiredLabel}'. " .
                     "Company must complete disclosure review before deals can be activated.",
            companyId: $companyId,
            currentTier: $currentTier,
            requiredTier: DisclosureTier::TIER_2_LIVE,
            operation: 'activate',
        );
    }

    /**
     * Deal featuring requires tier_3_featured.
     */
    public static function featuredRequiresTier3(int $companyId, DisclosureTier $currentTier): self
    {
        $requiredLabel = DisclosureTier::TIER_3_FEATURED->label();

        return new self(
            message: "Deal featuring blocked: Company (ID: {$companyId}) has disclosure tier '{$currentTier->label()}'. " .
                     "Featuring deals requires '{$requiredLabel}' tier. " .
                     "Company must be promoted to featured tier before deals can be featured.",
            companyId: $companyId,
            currentTier: $currentTier,
            requiredTier: DisclosureTier::TIER_3_FEATURED,
            operation: 'feature',
        );
    }

    /**
     * Get structured context for logging/auditing.
     */
    public function context(): array
    {
        return [
            'company_id' => $this->companyId,
            'current_tier' => $this->currentTier->value,
            'required_tier' => $this->requiredTier->value,
            'operation' => $this->operation,
            'current_tier_rank' => $this->currentTier->rank(),
            'required_tier_rank' => $this->requiredTier->rank(),
        ];
    }
}
