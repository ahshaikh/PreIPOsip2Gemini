<?php

namespace App\Traits;

use App\Services\DisclosureVisibilityGuard;
use Illuminate\Database\Eloquent\Builder;

/**
 * P0 FIX (GAP 25-27): Visibility Scope Trait
 *
 * Apply to models that need investor visibility enforcement.
 * Provides convenient scope methods for common visibility patterns.
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
     */
    public function scopeApprovedOnly(Builder $query): Builder
    {
        return $query->where('status', 'approved');
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
     */
    public function toInvestorArray($user = null): array
    {
        $guard = app(DisclosureVisibilityGuard::class);
        return $guard->sanitizeForInvestor($this, $user);
    }
}
