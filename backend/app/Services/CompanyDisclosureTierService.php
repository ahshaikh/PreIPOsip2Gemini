<?php
/**
 * STORY 3.1: Company Disclosure Tier Service
 *
 * GOVERNANCE INVARIANT:
 * This service is the SOLE AUTHORITY for changing a company's disclosure_tier.
 * All other modification paths are BLOCKED at the model level.
 *
 * RULES:
 * 1. Promotions are MONOTONIC (no downgrade, no skipping)
 * 2. All promotions are AUDITED with full context
 * 3. Actor MUST have explicit authorization
 * 4. Each promotion is a DISCRETE TRANSACTION
 *
 * COMPLIANCE NOTE:
 * This service is designed for forensic/audit review.
 * Every promotion is logged with actor, timestamp, justification, and context.
 */

namespace App\Services;

use App\Enums\DisclosureTier;
use App\Exceptions\DisclosureTierImmutabilityException;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanyDisclosureTierService
{
    /**
     * Promote a company to the next disclosure tier.
     *
     * This is the ONLY authorized method to change disclosure_tier.
     *
     * @param Company $company The company to promote
     * @param DisclosureTier $targetTier The target tier (must be exactly one tier higher)
     * @param mixed $actor The actor performing the promotion (User or CompanyUser)
     * @param string $justification Reason for promotion (required for audit)
     * @param array $metadata Additional context for audit trail
     *
     * @throws DisclosureTierImmutabilityException
     * @return Company The promoted company
     */
    public function promote(
        Company $company,
        DisclosureTier $targetTier,
        $actor,
        string $justification,
        array $metadata = []
    ): Company {
        // Get current tier as enum
        $currentTierValue = $company->disclosure_tier ?? DisclosureTier::TIER_0_PENDING->value;
        $currentTier = DisclosureTier::tryFrom($currentTierValue);

        if ($currentTier === null) {
            throw DisclosureTierImmutabilityException::invalidTier(
                (string) $company->id,
                $currentTierValue
            );
        }

        // Validate: Must be exactly one tier higher (no skip, no downgrade)
        if (!$currentTier->canPromoteTo($targetTier)) {
            $targetRank = $targetTier->rank();
            $currentRank = $currentTier->rank();

            if ($targetRank <= $currentRank) {
                throw DisclosureTierImmutabilityException::downgradeAttempt(
                    (string) $company->id,
                    $currentTier->value,
                    $targetTier->value
                );
            } else {
                throw DisclosureTierImmutabilityException::tierSkip(
                    (string) $company->id,
                    $currentTier->value,
                    $targetTier->value
                );
            }
        }

        // Validate: Actor must have authorization
        if (!$this->actorCanPromote($actor, $company, $targetTier)) {
            throw DisclosureTierImmutabilityException::unauthorizedPromotion(
                (string) $company->id,
                $currentTier->value,
                $targetTier->value,
                $actor?->id
            );
        }

        // Execute promotion within transaction
        return DB::transaction(function () use ($company, $currentTier, $targetTier, $actor, $justification, $metadata) {
            $promotedAt = now();

            // Use raw query to bypass model guards
            // This is the ONLY place where direct DB update is authorized
            DB::table('companies')
                ->where('id', $company->id)
                ->update([
                    'disclosure_tier' => $targetTier->value,
                    'updated_at' => $promotedAt,
                ]);

            // Refresh model to get updated value
            $company->refresh();

            // Create audit record
            $this->logPromotion(
                $company,
                $currentTier,
                $targetTier,
                $actor,
                $justification,
                $metadata,
                $promotedAt
            );

            Log::info('Company disclosure tier promoted', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'from_tier' => $currentTier->value,
                'to_tier' => $targetTier->value,
                'actor_type' => $actor ? get_class($actor) : 'system',
                'actor_id' => $actor?->id,
                'justification' => $justification,
            ]);

            return $company;
        });
    }

    /**
     * Promote a company to the next tier (convenience method).
     *
     * @param Company $company The company to promote
     * @param mixed $actor The actor performing the promotion
     * @param string $justification Reason for promotion
     * @param array $metadata Additional context
     *
     * @throws DisclosureTierImmutabilityException
     * @return Company The promoted company
     */
    public function promoteToNextTier(
        Company $company,
        $actor,
        string $justification,
        array $metadata = []
    ): Company {
        $currentTierValue = $company->disclosure_tier ?? DisclosureTier::TIER_0_PENDING->value;
        $currentTier = DisclosureTier::tryFrom($currentTierValue);

        if ($currentTier === null) {
            throw DisclosureTierImmutabilityException::invalidTier(
                (string) $company->id,
                $currentTierValue
            );
        }

        $nextTier = $currentTier->nextTier();

        if ($nextTier === null) {
            throw new \RuntimeException(
                "Company '{$company->name}' (ID: {$company->id}) is already at maximum tier: {$currentTier->value}. " .
                "No further promotion is possible."
            );
        }

        return $this->promote($company, $nextTier, $actor, $justification, $metadata);
    }

    /**
     * Get the current tier of a company as an enum.
     */
    public function getCurrentTier(Company $company): DisclosureTier
    {
        $value = $company->disclosure_tier ?? DisclosureTier::TIER_0_PENDING->value;
        $tier = DisclosureTier::tryFrom($value);

        if ($tier === null) {
            Log::warning('Company has invalid disclosure_tier value', [
                'company_id' => $company->id,
                'invalid_value' => $value,
            ]);
            return DisclosureTier::TIER_0_PENDING;
        }

        return $tier;
    }

    /**
     * Check if a company is publicly visible based on disclosure tier.
     */
    public function isPubliclyVisible(Company $company): bool
    {
        return $this->getCurrentTier($company)->isPubliclyVisible();
    }

    /**
     * Check if a company is investable based on disclosure tier.
     */
    public function isInvestable(Company $company): bool
    {
        return $this->getCurrentTier($company)->isInvestable();
    }

    /**
     * Get the next available tier for a company (or null if at max).
     */
    public function getNextAvailableTier(Company $company): ?DisclosureTier
    {
        return $this->getCurrentTier($company)->nextTier();
    }

    /**
     * Check if an actor can promote a company to a specific tier.
     *
     * AUTHORIZATION RULES:
     * - To tier_1_upcoming: System or Admin
     * - To tier_2_live: Admin only (requires disclosure approval)
     * - To tier_3_featured: Admin only (editorial decision)
     */
    protected function actorCanPromote($actor, Company $company, DisclosureTier $targetTier): bool
    {
        // System promotions (e.g., automated workflows)
        if ($actor === null) {
            // Only allow system promotion to tier_1_upcoming
            return $targetTier === DisclosureTier::TIER_1_UPCOMING;
        }

        // Check if actor is admin
        $isAdmin = $this->isAdminUser($actor);

        // Tier-specific authorization
        return match ($targetTier) {
            DisclosureTier::TIER_0_PENDING => false, // Cannot promote to tier_0 (it's the default)
            DisclosureTier::TIER_1_UPCOMING => $isAdmin,
            DisclosureTier::TIER_2_LIVE => $isAdmin,
            DisclosureTier::TIER_3_FEATURED => $isAdmin,
        };
    }

    /**
     * Check if the actor is an admin user.
     */
    protected function isAdminUser($actor): bool
    {
        if ($actor === null) {
            return false;
        }

        // Check if it's a User model with admin role
        if ($actor instanceof \App\Models\User) {
            // Check for admin role - adjust based on your role system
            return $actor->hasRole('admin') ||
                   $actor->hasRole('super_admin') ||
                   $actor->is_admin;
        }

        return false;
    }

    /**
     * Log promotion to audit trail.
     */
    protected function logPromotion(
        Company $company,
        DisclosureTier $fromTier,
        DisclosureTier $toTier,
        $actor,
        string $justification,
        array $metadata,
        $promotedAt
    ): void {
        AuditLogger::log(
            'disclosure_tier_promoted',
            'companies',
            "Company '{$company->name}' promoted from {$fromTier->label()} to {$toTier->label()}",
            [
                'target_type' => Company::class,
                'target_id' => $company->id,
                'target_name' => $company->name,
                'old_values' => [
                    'disclosure_tier' => $fromTier->value,
                ],
                'new_values' => [
                    'disclosure_tier' => $toTier->value,
                ],
                'metadata' => array_merge($metadata, [
                    'justification' => $justification,
                    'promoted_at' => $promotedAt->toIso8601String(),
                    'from_tier_rank' => $fromTier->rank(),
                    'to_tier_rank' => $toTier->rank(),
                    'is_now_publicly_visible' => $toTier->isPubliclyVisible(),
                    'is_now_investable' => $toTier->isInvestable(),
                ]),
                'risk_level' => $toTier->isPubliclyVisible() ? 'high' : 'medium',
                'requires_review' => $toTier === DisclosureTier::TIER_2_LIVE,
            ]
        );
    }

    /**
     * Validate that a company meets requirements for promotion to a specific tier.
     *
     * @param Company $company The company to validate
     * @param DisclosureTier $targetTier The tier to validate against
     *
     * @return array{valid: bool, errors: array<string>}
     */
    public function validatePromotionRequirements(Company $company, DisclosureTier $targetTier): array
    {
        $errors = [];

        // Check current tier allows promotion
        $currentTier = $this->getCurrentTier($company);
        if (!$currentTier->canPromoteTo($targetTier)) {
            $errors[] = "Cannot promote from {$currentTier->label()} to {$targetTier->label()}. Must promote one tier at a time.";
        }

        // Tier-specific requirements
        switch ($targetTier) {
            case DisclosureTier::TIER_1_UPCOMING:
                // Requires basic profile completion
                if (!$company->profile_completed) {
                    $errors[] = "Company profile must be complete before promotion to Upcoming.";
                }
                break;

            case DisclosureTier::TIER_2_LIVE:
                // Requires approved disclosures
                $approvedDisclosures = $company->disclosures()
                    ->where('status', 'approved')
                    ->count();

                if ($approvedDisclosures === 0) {
                    $errors[] = "Company must have at least one approved disclosure before going Live.";
                }

                // Requires verification
                if (!$company->is_verified) {
                    $errors[] = "Company must be verified before going Live.";
                }
                break;

            case DisclosureTier::TIER_3_FEATURED:
                // Must already be live
                if ($currentTier !== DisclosureTier::TIER_2_LIVE) {
                    $errors[] = "Company must be Live before being Featured.";
                }
                break;

            default:
                break;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
