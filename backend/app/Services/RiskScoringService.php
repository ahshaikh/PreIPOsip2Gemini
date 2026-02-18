<?php

/**
 * V-DISPUTE-RISK-2026-004: Risk Scoring Service
 *
 * Deterministic risk scoring for users based on chargeback and dispute history.
 *
 * DESIGN PRINCIPLES:
 * - Deterministic: Same inputs always produce same score
 * - Config-driven: All thresholds from config/risk.php
 * - Capped at 100: Maximum possible risk score
 * - No decay: Score never decreases automatically
 * - Auditable: All calculations logged for review
 *
 * SCORING FACTORS:
 * 1. Chargeback count (base + repeat escalation)
 * 2. Chargeback-to-payment ratio
 * 3. Account age at time of chargeback
 * 4. Open dispute count and severity
 */

namespace App\Services;

use App\Models\User;
use App\Models\Payment;
use App\Models\Dispute;
use Illuminate\Support\Facades\Log;

class RiskScoringService
{
    /**
     * Calculate the risk score for a user.
     *
     * @param User $user The user to score
     * @return array{score: int, factors: array, breakdown: array}
     */
    public function calculateScore(User $user): array
    {
        $breakdown = [];
        $factors = [];
        $totalScore = 0;

        // 1. Chargeback count scoring
        $chargebackResult = $this->scoreChargebackCount($user);
        $totalScore += $chargebackResult['points'];
        if ($chargebackResult['points'] > 0) {
            $breakdown['chargeback_count'] = $chargebackResult;
            $factors[] = 'chargeback_history';
        }

        // 2. Chargeback ratio scoring
        $ratioResult = $this->scoreChargebackRatio($user);
        $totalScore += $ratioResult['points'];
        if ($ratioResult['points'] > 0) {
            $breakdown['chargeback_ratio'] = $ratioResult;
            $factors[] = 'high_chargeback_ratio';
        }

        // 3. Account age scoring (if has chargebacks)
        $ageResult = $this->scoreAccountAge($user);
        $totalScore += $ageResult['points'];
        if ($ageResult['points'] > 0) {
            $breakdown['account_age'] = $ageResult;
            $factors[] = 'new_account_risk';
        }

        // 4. Open dispute scoring
        $disputeResult = $this->scoreOpenDisputes($user);
        $totalScore += $disputeResult['points'];
        if ($disputeResult['points'] > 0) {
            $breakdown['open_disputes'] = $disputeResult;
            $factors[] = 'pending_disputes';
        }

        // Cap at maximum score
        $maxScore = config('risk.max_score', 100);
        $finalScore = min($totalScore, $maxScore);

        $result = [
            'score' => $finalScore,
            'uncapped_score' => $totalScore,
            'factors' => $factors,
            'breakdown' => $breakdown,
            'calculated_at' => now()->toIso8601String(),
        ];

        // Log calculation if enabled
        if (config('risk.audit.log_calculations', true)) {
            Log::channel(config('risk.audit.log_channel', 'financial_contract'))->info('RISK SCORE CALCULATED', [
                'user_id' => $user->id,
                'final_score' => $finalScore,
                'uncapped_score' => $totalScore,
                'factors' => $factors,
                'breakdown' => $breakdown,
            ]);
        }

        return $result;
    }

    /**
     * Check if a user should be blocked based on their risk score.
     *
     * @param int $score The risk score to check
     * @return bool True if user should be blocked
     */
    public function shouldBlock(int $score): bool
    {
        return $score >= config('risk.thresholds.blocking', 70);
    }

    /**
     * Check if a user is high-risk (for reporting purposes).
     *
     * @param int $score The risk score to check
     * @return bool True if user is high-risk
     */
    public function isHighRisk(int $score): bool
    {
        return $score >= config('risk.thresholds.high_risk', 50);
    }

    /**
     * Check if a user should be flagged for review.
     *
     * @param int $score The risk score to check
     * @return bool True if user should be reviewed
     */
    public function shouldReview(int $score): bool
    {
        return $score >= config('risk.thresholds.review', 30);
    }

    /**
     * Get the risk category for a score.
     *
     * @param int $score The risk score
     * @return string 'low', 'review', 'high', or 'blocked'
     */
    public function getRiskCategory(int $score): string
    {
        if ($this->shouldBlock($score)) {
            return 'blocked';
        }
        if ($this->isHighRisk($score)) {
            return 'high';
        }
        if ($this->shouldReview($score)) {
            return 'review';
        }
        return 'low';
    }

    /**
     * Score based on chargeback count.
     *
     * @param User $user
     * @return array{points: int, count: int, description: string}
     */
    private function scoreChargebackCount(User $user): array
    {
        $chargebackCount = Payment::where('user_id', $user->id)
            ->where('status', Payment::STATUS_CHARGEBACK_CONFIRMED)
            ->count();

        if ($chargebackCount === 0) {
            return ['points' => 0, 'count' => 0, 'description' => 'No chargebacks'];
        }

        $baseWeight = config('risk.weights.chargeback_base', 25);
        $repeatWeight = config('risk.weights.chargeback_repeat', 15);

        // First chargeback gets base weight, additional get repeat weight
        $points = $baseWeight + (($chargebackCount - 1) * $repeatWeight);

        return [
            'points' => $points,
            'count' => $chargebackCount,
            'description' => "{$chargebackCount} confirmed chargeback(s)",
        ];
    }

    /**
     * Score based on chargeback-to-payment ratio.
     *
     * @param User $user
     * @return array{points: int, ratio: float, description: string}
     */
    private function scoreChargebackRatio(User $user): array
    {
        $totalPayments = Payment::where('user_id', $user->id)
            ->whereIn('status', [
                Payment::STATUS_PAID,
                Payment::STATUS_SETTLED,
                Payment::STATUS_REFUNDED,
                Payment::STATUS_CHARGEBACK_CONFIRMED,
            ])
            ->count();

        $minPayments = config('risk.ratios.min_payments_for_ratio', 3);

        if ($totalPayments < $minPayments) {
            return [
                'points' => 0,
                'ratio' => 0.0,
                'description' => "Insufficient payment history ({$totalPayments} < {$minPayments})",
            ];
        }

        $chargebackCount = Payment::where('user_id', $user->id)
            ->where('status', Payment::STATUS_CHARGEBACK_CONFIRMED)
            ->count();

        $ratio = $chargebackCount / $totalPayments;
        $veryHighRatio = config('risk.ratios.very_high', 0.40);
        $highRatio = config('risk.ratios.high', 0.20);

        $points = 0;
        $description = '';

        if ($ratio >= $veryHighRatio) {
            $points = config('risk.weights.very_high_chargeback_ratio', 30);
            $description = sprintf('Very high chargeback ratio: %.1f%%', $ratio * 100);
        } elseif ($ratio >= $highRatio) {
            $points = config('risk.weights.high_chargeback_ratio', 20);
            $description = sprintf('High chargeback ratio: %.1f%%', $ratio * 100);
        } else {
            $description = sprintf('Normal chargeback ratio: %.1f%%', $ratio * 100);
        }

        return [
            'points' => $points,
            'ratio' => round($ratio, 4),
            'total_payments' => $totalPayments,
            'chargeback_count' => $chargebackCount,
            'description' => $description,
        ];
    }

    /**
     * Score based on account age if user has chargebacks.
     *
     * @param User $user
     * @return array{points: int, account_age_days: int, description: string}
     */
    private function scoreAccountAge(User $user): array
    {
        // Only applies if user has chargebacks
        $hasChargebacks = Payment::where('user_id', $user->id)
            ->where('status', Payment::STATUS_CHARGEBACK_CONFIRMED)
            ->exists();

        if (!$hasChargebacks) {
            return [
                'points' => 0,
                'account_age_days' => 0,
                'description' => 'No chargebacks - account age not factored',
            ];
        }

        $accountAgeDays = $user->created_at->diffInDays(now());
        $newAccountThreshold = config('risk.account_age.new_account_days', 30);

        if ($accountAgeDays < $newAccountThreshold) {
            $points = config('risk.weights.new_account_chargeback', 10);
            return [
                'points' => $points,
                'account_age_days' => $accountAgeDays,
                'description' => "New account ({$accountAgeDays} days) with chargeback",
            ];
        }

        return [
            'points' => 0,
            'account_age_days' => $accountAgeDays,
            'description' => "Established account ({$accountAgeDays} days)",
        ];
    }

    /**
     * Score based on open disputes.
     *
     * @param User $user
     * @return array{points: int, open_count: int, critical_count: int, description: string}
     */
    private function scoreOpenDisputes(User $user): array
    {
        $openDisputes = Dispute::where('user_id', $user->id)
            ->whereIn('status', ['open', 'under_investigation', 'escalated'])
            ->get();

        $openCount = $openDisputes->count();
        if ($openCount === 0) {
            return [
                'points' => 0,
                'open_count' => 0,
                'critical_count' => 0,
                'description' => 'No open disputes',
            ];
        }

        $openWeight = config('risk.weights.open_dispute', 5);
        $criticalWeight = config('risk.weights.critical_dispute', 15);

        $criticalCount = $openDisputes->whereIn('severity', ['critical', 'high'])->count();
        $normalCount = $openCount - $criticalCount;

        $points = ($normalCount * $openWeight) + ($criticalCount * $criticalWeight);

        return [
            'points' => $points,
            'open_count' => $openCount,
            'critical_count' => $criticalCount,
            'normal_count' => $normalCount,
            'description' => "{$openCount} open dispute(s), {$criticalCount} critical/high",
        ];
    }

    /**
     * Get all chargebacks for a user with details.
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getChargebackHistory(User $user)
    {
        return Payment::where('user_id', $user->id)
            ->where('status', Payment::STATUS_CHARGEBACK_CONFIRMED)
            ->orderBy('chargeback_confirmed_at', 'desc')
            ->get([
                'id',
                'amount_paise',
                'chargeback_amount_paise',
                'chargeback_reason',
                'chargeback_confirmed_at',
                'created_at',
            ]);
    }

    /**
     * Get risk summary for a user.
     *
     * @param User $user
     * @return array
     */
    public function getRiskSummary(User $user): array
    {
        $scoreResult = $this->calculateScore($user);

        return [
            'user_id' => $user->id,
            'current_score' => $scoreResult['score'],
            'risk_category' => $this->getRiskCategory($scoreResult['score']),
            'is_blocked' => $user->is_blocked,
            'blocked_reason' => $user->blocked_reason,
            'last_risk_update_at' => $user->last_risk_update_at?->toIso8601String(),
            'factors' => $scoreResult['factors'],
            'thresholds' => [
                'blocking' => config('risk.thresholds.blocking', 70),
                'high_risk' => config('risk.thresholds.high_risk', 50),
                'review' => config('risk.thresholds.review', 30),
            ],
        ];
    }
}
