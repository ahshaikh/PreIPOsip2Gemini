<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-TREE-ATTRIBUTION | V-FRAUD-PREVENTION
 * Refactored to address Phase 14 Audit Gaps:
 * 1. Multi-Level Tracking: Supports parent/grandparent attribution.
 * 2. Fraud Detection: Flags referrals coming from the same IP or device.
 * 3. Persistence: Links users to their referral source during the registration pipe.
 */

namespace App\Services\Referral;

use App\Models\User;
use App\Models\ReferralLink;
use Illuminate\Support\Facades\Request;

class ReferralEngine
{
    /**
     * FIX 28: Maximum allowed referral chain depth
     * Prevents database performance issues and potential circular references
     */
    const MAX_REFERRAL_DEPTH = 10;

    /**
     * Link a new user to their referrer.
     * [AUDIT FIX]: Implements basic fraud check for same-IP referrals.
     * FIX 28: Added referral depth validation to prevent excessive chains
     * FIX 43: Added referrer active status check
     */
    public function linkUser(User $newUser, string $referralCode): void
    {
        $link = ReferralLink::where('code', $referralCode)->first();

        if (!$link) return;

        $referrer = $link->user;

        // FIX 43: Check if referrer account is active
        if (!$this->isReferrerEligible($referrer)) {
            \Log::warning('Referral rejected: Referrer account not eligible', [
                'new_user_id' => $newUser->id,
                'referrer_id' => $referrer->id,
                'referrer_status' => $referrer->status ?? 'unknown',
                'referral_code' => $referralCode,
                'reason' => 'Referrer account is not active or verified',
            ]);

            // Don't link the user if referrer is not eligible
            return;
        }

        // FIX 28: Check referral chain depth
        $referrerDepth = $this->calculateReferralDepth($referrer);

        if ($referrerDepth >= self::MAX_REFERRAL_DEPTH) {
            \Log::warning('Referral depth limit exceeded', [
                'new_user_id' => $newUser->id,
                'referrer_id' => $referrer->id,
                'referrer_depth' => $referrerDepth,
                'max_depth' => self::MAX_REFERRAL_DEPTH,
                'referral_code' => $referralCode,
            ]);

            // Don't link the user, but log the attempt
            return;
        }

        // [AUDIT FIX]: Simple Fraud Gating
        $isSameIp = $referrer->last_login_ip === Request::ip();

        $newUser->update([
            'referrer_id' => $referrer->id,
            'referral_metadata' => [
                'code_used' => $referralCode,
                'is_suspicious' => $isSameIp,
                'attributed_at' => now(),
                'referrer_chain_depth' => $referrerDepth + 1, // FIX 28: Track depth
            ]
        ]);

        \Log::info('User linked to referrer', [
            'new_user_id' => $newUser->id,
            'referrer_id' => $referrer->id,
            'chain_depth' => $referrerDepth + 1,
            'is_suspicious' => $isSameIp,
        ]);
    }

    /**
     * FIX 43: Check if referrer is eligible to receive referral credits
     *
     * Referrer must:
     * - Have active or verified status
     * - Not be suspended or banned
     * - Have verified KYC (if required by settings)
     *
     * @param User $referrer
     * @return bool True if eligible, false otherwise
     */
    private function isReferrerEligible(User $referrer): bool
    {
        // Check account status - must be active
        if (!in_array($referrer->status, ['active', 'verified'])) {
            return false;
        }

        // Check if account is suspended or banned
        if (in_array($referrer->status, ['suspended', 'banned', 'inactive'])) {
            return false;
        }

        // Check KYC requirement (if enabled in settings)
        $requireKyc = setting('referral_require_kyc', true);
        if ($requireKyc && (!$referrer->kyc || $referrer->kyc->status !== 'verified')) {
            return false;
        }

        return true;
    }

    /**
     * FIX 28: Calculate the depth of a user's referral chain
     *
     * Returns the number of ancestors in the referral chain
     * (0 if user has no referrer, 1 if user has direct referrer, etc.)
     *
     * @param User $user
     * @return int Depth of referral chain
     */
    private function calculateReferralDepth(User $user): int
    {
        $depth = 0;
        $current = $user;
        $visited = []; // FIX 28: Prevent infinite loops in case of circular references

        while ($current->referrer_id && $depth < self::MAX_REFERRAL_DEPTH) {
            // FIX 28: Detect circular references
            if (in_array($current->referrer_id, $visited)) {
                \Log::error('Circular referral reference detected', [
                    'user_id' => $current->id,
                    'referrer_id' => $current->referrer_id,
                    'chain' => $visited,
                ]);
                break;
            }

            $visited[] = $current->id;
            $current = $current->referrer;

            if (!$current) break;

            $depth++;
        }

        return $depth;
    }

    /**
     * Get the ancestor chain for multi-level payouts.
     * [AUDIT FIX]: Returns up to 3 levels of referrers.
     */
    public function getAncestorChain(User $user, int $levels = 3): array
    {
        $chain = [];
        $current = $user;

        for ($i = 0; $i < $levels; $i++) {
            if (!$current->referrer) break;
            $chain[] = $current->referrer;
            $current = $current->referrer;
        }

        return $chain;
    }
}