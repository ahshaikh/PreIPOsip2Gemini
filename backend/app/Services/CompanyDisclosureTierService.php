<?php
/**
 * STORY 3.1: Company Disclosure Tier Service
 * V-AUDIT-FIX-2026: Added CompanyTierChanged event dispatch
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
 * DOWNGRADE POLICY (V-AUDIT-FIX-2026):
 * Downgrades are EXPLICITLY FORBIDDEN. This is enforced by:
 * 1. DisclosureTier::canPromoteTo() returns false for lower tiers
 * 2. DisclosureTierImmutabilityException::downgradeAttempt() is thrown
 * 3. No alternative downgrade path exists in the system
 * Rationale: Once a company reaches a disclosure tier, that represents
 * a compliance milestone that cannot be revoked without regulatory action.
 *
 * COMPLIANCE NOTE:
 * This service is designed for forensic/audit review.
 * Every promotion is logged with actor, timestamp, justification, and context.
 */

namespace App\Services;

use App\Enums\DisclosureTier;
use App\Enums\DisclosureTierRequirements;
use App\Events\CompanyTierChanged;
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
        $currentTierValue = $company->disclosure_tier ?? DisclosureTier::TIER_0_PENDING;
        $currentTier = $currentTierValue instanceof DisclosureTier ? $currentTierValue : DisclosureTier::tryFrom($currentTierValue);

        if ($currentTier === null) {
            throw DisclosureTierImmutabilityException::invalidTier(
                (string) $company->id,
                (string) $currentTierValue
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
        $promotedAt = now();

        $company = DB::transaction(function () use ($company, $currentTier, $targetTier, $actor, $justification, $metadata, $promotedAt) {
            // Use raw query to bypass model guards
            // This is the ONLY place where direct DB update is authorized
            // V-AUDIT-FIX-2026: Raw DB is intentional - model guards block ALL other paths
            DB::table('companies')
                ->where('id', $company->id)
                ->update([
                    'disclosure_tier' => $targetTier->value,
                    'updated_at' => $promotedAt,
                ]);

            // Refresh model to get updated value
            $company->refresh();

            // Create audit record (WITHIN transaction for atomicity)
            // V-AUDIT-FIX-2026: Audit log is in same transaction as tier change
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

        // V-AUDIT-FIX-2026: Dispatch event AFTER transaction commits
        // This ensures downstream listeners see committed state
        CompanyTierChanged::dispatch(
            $company,
            $currentTier,
            $targetTier,
            $actor,
            $justification,
            $metadata,
            $promotedAt
        );

        return $company;
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
        $currentTierValue = $company->disclosure_tier ?? DisclosureTier::TIER_0_PENDING;
        $currentTier = $currentTierValue instanceof DisclosureTier ? $currentTierValue : DisclosureTier::tryFrom($currentTierValue);

        if ($currentTier === null) {
            throw DisclosureTierImmutabilityException::invalidTier(
                (string) $company->id,
                (string) $currentTierValue
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
        $value = $company->disclosure_tier ?? DisclosureTier::TIER_0_PENDING;
        $tier = $value instanceof DisclosureTier ? $value : DisclosureTier::tryFrom($value);

        if ($tier === null) {
            Log::warning('Company has invalid disclosure_tier value', [
                'company_id' => $company->id,
                'invalid_value' => (string) $value,
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
     * STORY 3.2: Validate that a company meets requirements for promotion to a specific tier.
     *
     * Uses DisclosureTierRequirements as single source of truth.
     *
     * @param Company $company The company to validate
     * @param DisclosureTier|null $targetTier The tier to validate against (defaults to next tier)
     *
     * @return array{valid: bool, missing: array, pending: array, approved: array, errors: array}
     */
    public function validatePromotionRequirements(Company $company, ?DisclosureTier $targetTier = null): array
    {
        $currentTier = $this->getCurrentTier($company);
        $targetTier = $targetTier ?? $currentTier->nextTier();

        $errors = [];
        $missing = [];
        $pending = [];
        $approved = [];

        // No next tier available
        if ($targetTier === null) {
            return [
                'valid' => false,
                'missing' => [],
                'pending' => [],
                'approved' => [],
                'errors' => ['Company is already at maximum tier.'],
            ];
        }

        // Check tier progression is valid
        if (!$currentTier->canPromoteTo($targetTier)) {
            $errors[] = "Cannot promote from {$currentTier->label()} to {$targetTier->label()}. Must promote one tier at a time.";
        }

        // Check disclosure requirements from authoritative source
        $eligibility = DisclosureTierRequirements::checkEligibility($company, $targetTier);
        $missing = $eligibility['missing'];
        $pending = $eligibility['pending'];
        $approved = $eligibility['approved'];

        if (!empty($missing)) {
            $errors[] = 'Missing required disclosures: ' . implode(', ', $missing);
        }

        if (!empty($pending)) {
            $pendingList = array_map(fn($p) => "{$p['code']} ({$p['status']})", $pending);
            $errors[] = 'Disclosures not yet approved: ' . implode(', ', $pendingList);
        }

        // Tier-specific additional requirements
        switch ($targetTier) {
            case DisclosureTier::TIER_1_UPCOMING:
                if (!$company->profile_completed) {
                    $errors[] = 'Company profile must be complete.';
                }
                break;

            case DisclosureTier::TIER_2_LIVE:
                if (!$company->is_verified) {
                    $errors[] = 'Company must be verified.';
                }
                break;

            case DisclosureTier::TIER_3_FEATURED:
                // Editorial decision - no additional disclosure requirements
                break;
        }

        return [
            'valid' => empty($errors),
            'missing' => $missing,
            'pending' => $pending,
            'approved' => $approved,
            'errors' => $errors,
        ];
    }

    /**
     * STORY 3.2: Attempt automatic promotion after disclosure approval.
     *
     * Called by event listener. Idempotent - does nothing if not eligible.
     *
     * @param Company $company
     * @param mixed $actor The actor (usually system or admin who approved disclosure)
     * @param int|null $sourceDisclosureId The disclosure that triggered this check
     * @return bool Whether promotion occurred
     */
    public function tryAutomaticPromotion(Company $company, $actor = null, ?int $sourceDisclosureId = null): bool
    {
        $currentTier = $this->getCurrentTier($company);
        $nextTier = $currentTier->nextTier();

        // Already at max tier
        if ($nextTier === null) {
            return false;
        }

        // Check eligibility
        $validation = $this->validatePromotionRequirements($company, $nextTier);

        if (!$validation['valid']) {
            Log::debug('Automatic promotion not eligible', [
                'company_id' => $company->id,
                'current_tier' => $currentTier->value,
                'target_tier' => $nextTier->value,
                'errors' => $validation['errors'],
            ]);
            return false;
        }

        // Perform promotion
        try {
            $this->promote(
                $company,
                $nextTier,
                $actor,
                'Automatic promotion: all required disclosures approved',
                [
                    'trigger' => 'disclosure_approval',
                    'source_disclosure_id' => $sourceDisclosureId,
                    'automatic' => true,
                ]
            );

            Log::info('Automatic tier promotion completed', [
                'company_id' => $company->id,
                'from_tier' => $currentTier->value,
                'to_tier' => $nextTier->value,
                'source_disclosure_id' => $sourceDisclosureId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Automatic tier promotion failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
