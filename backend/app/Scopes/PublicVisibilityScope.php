<?php
/**
 * STORY 3.1 GAP 2: Public Visibility Global Scope
 *
 * GOVERNANCE INVARIANT:
 * Public-facing queries CANNOT return companies with disclosure_tier < tier_2_live.
 *
 * This scope is automatically applied when:
 * - Request is NOT from admin routes
 * - Request is NOT from company portal routes
 * - Context is explicitly marked as public
 *
 * Bypass via: Company::withoutGlobalScope(PublicVisibilityScope::class)
 */

namespace App\Scopes;

use App\Enums\DisclosureTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PublicVisibilityScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (static::shouldEnforcePublicVisibility()) {
            $builder->whereIn('disclosure_tier', [
                DisclosureTier::TIER_2_LIVE->value,
                DisclosureTier::TIER_3_FEATURED->value,
            ]);
        }
    }

    /**
     * Determine if public visibility should be enforced.
     *
     * Returns TRUE (enforce) when:
     * - We are in a public HTTP context (not admin, not company portal)
     *
     * Returns FALSE (bypass) when:
     * - Admin routes
     * - Company portal routes
     * - CLI / artisan commands
     * - Queue jobs (no request context)
     * - Explicit bypass flag set
     */
    public static function shouldEnforcePublicVisibility(): bool
    {
        // Non-HTTP context: bypass (CLI, jobs, etc.)
        if (!app()->runningInConsole() === false && !request()) {
            return false;
        }

        // No request means non-HTTP context
        if (app()->runningInConsole()) {
            return false;
        }

        // Check for explicit bypass flag
        if (app()->bound('disclosure.bypass_visibility') && app('disclosure.bypass_visibility')) {
            return false;
        }

        $request = request();
        if (!$request) {
            return false;
        }

        $path = $request->path();

        // Admin routes: bypass
        if (str_starts_with($path, 'api/admin') || str_starts_with($path, 'admin')) {
            return false;
        }

        // Company portal routes: bypass
        if (str_starts_with($path, 'api/company') || str_starts_with($path, 'company')) {
            return false;
        }

        // Internal API routes (non-public): bypass
        if (str_starts_with($path, 'api/internal')) {
            return false;
        }

        // All other routes: enforce public visibility
        return true;
    }

    /**
     * Temporarily bypass visibility enforcement.
     *
     * Usage:
     *   PublicVisibilityScope::bypass(function() {
     *       return Company::all(); // Returns all, including non-public
     *   });
     */
    public static function bypass(callable $callback)
    {
        app()->instance('disclosure.bypass_visibility', true);
        try {
            return $callback();
        } finally {
            app()->instance('disclosure.bypass_visibility', false);
        }
    }
}
