<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-LEAD-SCORING | V-INVESTOR-PRIORITY
 * Refactored to address Phase 17 Audit Gaps:
 * 1. Behavioral Scoring: Assigns points for KYC completion and investment history.
 * 2. Dynamic Categorization: Labels users as 'Cold', 'Warm', or 'Hot' leads.
 * 3. CRM Integration: Provides data for the IR team to prioritize outreach.
 */

namespace App\Services\CRM;

use App\Models\User;

class LeadScoringService
{
    /**
     * Calculate a lead score for a user.
     * [AUDIT FIX]: Quantitative scoring for investor engagement.
     */
    public function calculateScore(User $user): int
    {
        $score = 0;

        // +40 points for completed KYC
        if ($user->kyc_status === 'verified') $score += 40;

        // +10 points for every active investment
        $score += ($user->investments()->count() * 10);

        // +20 points if wallet balance > 10,000 Paise (₹100)
        if ($user->wallet?->balance_paise > 10000) $score += 20;

        return min($score, 100); // Cap at 100
    }

    public function getCategory(int $score): string
    {
        if ($score >= 80) return 'Hot';
        if ($score >= 40) return 'Warm';
        return 'Cold';
    }
}