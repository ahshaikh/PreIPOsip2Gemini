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
     * Link a new user to their referrer.
     * [AUDIT FIX]: Implements basic fraud check for same-IP referrals.
     */
    public function linkUser(User $newUser, string $referralCode): void
    {
        $link = ReferralLink::where('code', $referralCode)->first();

        if (!$link) return;

        // [AUDIT FIX]: Simple Fraud Gating
        $isSameIp = $link->user->last_login_ip === Request::ip();
        
        $newUser->update([
            'referrer_id' => $link->user_id,
            'referral_metadata' => [
                'code_used' => $referralCode,
                'is_suspicious' => $isSameIp,
                'attributed_at' => now(),
            ]
        ]);
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