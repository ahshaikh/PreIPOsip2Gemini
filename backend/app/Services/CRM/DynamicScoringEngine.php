<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-LEAD-DECAY-LOGIC | V-CRM-INTELLIGENCE
 * * ARCHITECTURAL FIX: 
 * Introduces 'Recency' as a primary scoring factor.
 * Ensures the IR team prioritizes active engagement over historical data.
 */

namespace App\Services\CRM;

use App\Models\User;
use Carbon\Carbon;

class DynamicScoringEngine
{
    /**
     * Calculate score with time-based decay.
     * [ANTI-PATTERN FIX]: Prevents stale leads from cluttering 'Hot' lists.
     */
    public function calculateActiveScore(User $user): int
    {
        $baseScore = 0;

        // 1. Transactional Value (50 pts)
        $invested = $user->investments()->where('status', 'active')->sum('amount_paise');
        if ($invested > 10000000) $baseScore += 50; // ₹1 Lakh
        elseif ($invested > 0) $baseScore += 25;

        // 2. Activity Recency (50 pts)
        $lastActive = $user->last_login_at ? Carbon::parse($user->last_login_at) : null;
        
        if ($lastActive) {
            $daysSinceActive = $lastActive->diffInDays(now());
            
            // Linear decay: Lose 1 point per day of inactivity
            $activityScore = max(0, 50 - $daysSinceActive);
            $baseScore += $activityScore;
        }

        return min($baseScore, 100);
    }
}