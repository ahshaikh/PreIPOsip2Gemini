<?php
/**
 * STORY 3.2: Disclosure Tier Requirements
 *
 * SINGLE SOURCE OF TRUTH for tier promotion requirements.
 * Maps each tier to the disclosure modules required to advance to the NEXT tier.
 *
 * GOVERNANCE INVARIANT:
 * A company can ONLY be promoted to the next tier when ALL required
 * disclosures for that tier are in 'approved' status.
 */

namespace App\Enums;

class DisclosureTierRequirements
{
    /**
     * Disclosure module codes required for each tier promotion.
     *
     * Key = current tier
     * Value = array of module codes required to promote to NEXT tier
     */
    private const REQUIREMENTS = [
        // tier_0_pending → tier_1_upcoming: Basic company info
        'tier_0_pending' => [
            'company_overview',
            'business_model',
        ],

        // tier_1_upcoming → tier_2_live: Full disclosure package for public investment
        'tier_1_upcoming' => [
            'company_overview',
            'business_model',
            'financials',
            'risks',
            'governance',
            'legal_compliance',
        ],

        // tier_2_live → tier_3_featured: Editorial/premium (no additional disclosures)
        'tier_2_live' => [],
    ];

    /**
     * Get required disclosure module codes for promoting FROM a given tier.
     *
     * @param DisclosureTier $currentTier
     * @return array<string> Module codes required for next tier
     */
    public static function getRequirementsForPromotion(DisclosureTier $currentTier): array
    {
        return self::REQUIREMENTS[$currentTier->value] ?? [];
    }

    /**
     * Get required module codes for a specific target tier.
     *
     * @param DisclosureTier $targetTier
     * @return array<string> Module codes required
     */
    public static function getRequirementsForTier(DisclosureTier $targetTier): array
    {
        // Get the tier BEFORE the target to find what's needed
        $previousTier = self::getPreviousTier($targetTier);
        if ($previousTier === null) {
            return [];
        }
        return self::REQUIREMENTS[$previousTier->value] ?? [];
    }

    /**
     * Check if a company has all required disclosures approved for promotion.
     *
     * @param \App\Models\Company $company
     * @param DisclosureTier $targetTier
     * @return array{eligible: bool, approved: array, missing: array, pending: array}
     */
    public static function checkEligibility($company, DisclosureTier $targetTier): array
    {
        $requiredCodes = self::getRequirementsForTier($targetTier);

        if (empty($requiredCodes)) {
            // No disclosure requirements (e.g., tier_2 → tier_3 is editorial)
            return [
                'eligible' => true,
                'approved' => [],
                'missing' => [],
                'pending' => [],
            ];
        }

        $approved = [];
        $missing = [];
        $pending = [];

        foreach ($requiredCodes as $moduleCode) {
            $disclosure = $company->disclosures()
                ->whereHas('disclosureModule', function ($q) use ($moduleCode) {
                    $q->where('code', $moduleCode);
                })
                ->first();

            if (!$disclosure) {
                $missing[] = $moduleCode;
            } elseif ($disclosure->status === 'approved') {
                $approved[] = $moduleCode;
            } else {
                $pending[] = [
                    'code' => $moduleCode,
                    'status' => $disclosure->status,
                ];
            }
        }

        return [
            'eligible' => empty($missing) && empty($pending),
            'approved' => $approved,
            'missing' => $missing,
            'pending' => $pending,
        ];
    }

    /**
     * Get the tier before a given tier.
     */
    private static function getPreviousTier(DisclosureTier $tier): ?DisclosureTier
    {
        return match ($tier) {
            DisclosureTier::TIER_0_PENDING => null,
            DisclosureTier::TIER_1_UPCOMING => DisclosureTier::TIER_0_PENDING,
            DisclosureTier::TIER_2_LIVE => DisclosureTier::TIER_1_UPCOMING,
            DisclosureTier::TIER_3_FEATURED => DisclosureTier::TIER_2_LIVE,
        };
    }

    /**
     * Get all tier requirements as structured data.
     *
     * @return array
     */
    public static function all(): array
    {
        $result = [];
        foreach (DisclosureTier::cases() as $tier) {
            $nextTier = $tier->nextTier();
            $result[$tier->value] = [
                'current_tier' => $tier->value,
                'next_tier' => $nextTier?->value,
                'required_modules' => self::REQUIREMENTS[$tier->value] ?? [],
                'can_advance' => $nextTier !== null,
            ];
        }
        return $result;
    }
}
