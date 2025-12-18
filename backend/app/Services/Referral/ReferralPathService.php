<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-PATH-ENUMERATION | V-PERFORMANCE-SCALE
 * * ARCHITECTURAL FIX: 
 * Replaces recursive queries with flat path lookups.
 * Allows fetching 10-level deep trees in a single indexed query.
 */

namespace App\Services\Referral;

use App\Models\User;

class ReferralPathService
{
    /**
     * Generate path for a new user: /grandparent/parent/
     * [PERFORMANCE FIX]: Enables lightning-fast ancestor lookups.
     */
    public function generatePath(User $referrer): string
    {
        $parentPath = $referrer->referral_path ?? '/';
        return $parentPath . $referrer->id . '/';
    }

    /**
     * Get all ancestors for multi-level commission payouts.
     * [ANTI-PATTERN FIX]: No recursion, just string parsing.
     */
    public function getAncestors(User $user): array
    {
        $path = $user->referral_path; // e.g., "/1/45/89/"
        if (!$path || $path === '/') return [];

        $ids = array_filter(explode('/', $path));
        return User::whereIn('id', $ids)
                   ->orderByRaw("FIELD(id, " . implode(',', $ids) . ") DESC")
                   ->get()
                   ->toArray();
    }
}