<?php

namespace App\Traits;

use App\Exceptions\DisclosureAuthorityViolationException;
use App\Services\DisclosureVisibilityGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * P0 FIX (GAP 25-27): Visibility Scope Trait
 * PHASE 1 AUDIT FIX: Enhanced with authority enforcement
 *
 * Apply to models that need investor visibility enforcement.
 * Provides convenient scope methods for common visibility patterns.
 *
 * PHASE 1 AUDIT GUARANTEES:
 * - approvedOnly() scope STRICTLY filters to approved status
 * - toInvestorArray() uses IMMUTABLE version data, not mutable disclosure data
 * - Hard failure if approved disclosure lacks valid version
 *
 * Usage:
 *   CompanyDisclosure::visibleToInvestor($user)->get();
 *   CompanyDisclosure::visibleToPublic()->get();
 *   CompanyDisclosure::approvedOnly()->get();
 */
trait HasVisibilityScope
{
    /**
     * GAP 25 & 26: Scope to approved disclosures only
     *
     * CRITICAL: Use this for ALL investor-facing queries.
     * This is the MINIMUM scope for investor visibility.
     */
    public function scopeApprovedOnly(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * PHASE 1 AUDIT FIX: Scope to approved disclosures with their current version
     *
     * This scope ensures the version relationship is eager-loaded,
     * which is required for using investor_visible_data accessor.
     */
    public function scopeApprovedWithVersion(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->where('is_visible', true)
            ->whereNotNull('current_version_id')
            ->with('currentVersion');
    }

    /**
     * GAP 25: Scope to investor-visible disclosures
     *
     * Includes status, visibility level, and is_visible checks.
     */
    public function scopeVisibleToInvestor(Builder $query, $user = null): Builder
    {
        $guard = app(DisclosureVisibilityGuard::class);
        return $guard->scopeForInvestor($query, $user);
    }

    /**
     * GAP 27: Scope to public disclosures only
     *
     * No authentication required.
     */
    public function scopeVisibleToPublic(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->where('is_visible', true)
            ->where('visibility', 'public');
    }

    /**
     * GAP 27: Scope to subscriber-only disclosures
     *
     * Requires user to be investor in the company.
     */
    public function scopeSubscriberOnly(Builder $query): Builder
    {
        return $query->where('visibility', 'subscriber');
    }

    /**
     * Scope to exclude draft and rejected
     *
     * Additional safety check.
     */
    public function scopeExcludeDraftsAndRejected(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['draft', 'rejected']);
    }

    /**
     * Check if this disclosure is visible to a specific user
     */
    public function isVisibleTo($user = null): bool
    {
        $guard = app(DisclosureVisibilityGuard::class);
        $result = $guard->canInvestorViewDisclosure($this->id, $user);
        return $result['visible'];
    }

    /**
     * Get sanitized data for investor response
     *
     * PHASE 1 AUDIT FIX: Uses IMMUTABLE version data for approved disclosures
     *
     * @param mixed $user
     * @return array
     * @throws DisclosureAuthorityViolationException If approved disclosure lacks valid version
     */
    public function toInvestorArray($user = null): array
    {
        $guard = app(DisclosureVisibilityGuard::class);
        $sanitized = $guard->sanitizeForInvestor($this, $user);

        // PHASE 1 AUDIT FIX: For approved disclosures, ensure we use immutable version data
        if ($this->status === 'approved') {
            // Verify disclosure has valid version
            if (!$this->current_version_id) {
                throw DisclosureAuthorityViolationException::missingVersion(
                    $this->id,
                    $this->company_id
                );
            }

            // Load version if not already loaded
            if (!$this->relationLoaded('currentVersion')) {
                $this->load('currentVersion');
            }

            $version = $this->currentVersion;

            if (!$version) {
                throw DisclosureAuthorityViolationException::versionNotFound(
                    $this->id,
                    $this->current_version_id,
                    $this->company_id
                );
            }

            if (!$version->is_locked) {
                throw DisclosureAuthorityViolationException::unlockedVersion(
                    $this->id,
                    $version->id,
                    $this->company_id
                );
            }

            // CRITICAL: Override disclosure_data with IMMUTABLE version data
            $sanitized['disclosure_data'] = $version->disclosure_data;
            $sanitized['attachments'] = $version->attachments;

            // Add version metadata
            $sanitized['_version'] = [
                'version_id' => $version->id,
                'version_number' => $version->version_number,
                'version_hash' => $version->version_hash,
                'approved_at' => $version->approved_at?->toIso8601String(),
                'is_immutable' => true,
                'locked_at' => $version->locked_at?->toIso8601String(),
            ];

            Log::debug('HasVisibilityScope: Using immutable version data for investor', [
                'disclosure_id' => $this->id,
                'version_id' => $version->id,
                'version_number' => $version->version_number,
            ]);
        }

        return $sanitized;
    }

    /**
     * PHASE 1 AUDIT FIX: Get immutable version data only
     *
     * For approved disclosures, returns the LOCKED version data.
     * For non-approved disclosures, returns null (they shouldn't be visible).
     *
     * @return array|null
     * @throws DisclosureAuthorityViolationException
     */
    public function getImmutableDisclosureData(): ?array
    {
        if ($this->status !== 'approved') {
            return null;
        }

        // This accessor enforces all invariants
        return $this->investor_visible_data;
    }
}
