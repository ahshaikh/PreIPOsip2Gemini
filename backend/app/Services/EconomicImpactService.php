<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * EconomicImpactService - UNIFIED AUTHORITY for Economic Impact Assessment
 *
 * META-FIX (I.28): Shift from "module correctness" to "system correctness"
 *
 * PROBLEM (Fragmentation):
 * - SystemHealthMonitoringService has assessEconomicImpact()
 * - StuckStateDetectorService has assessAlertEconomicImpact()
 * - AlertRootCauseAnalyzer has calculateSeverity()
 * - Each service duplicates logic → inconsistency, drift, no single truth
 *
 * SOLUTION (Unified Authority):
 * - This service is the ONLY place economic impact is assessed
 * - All other services DELEGATE to this service
 * - Single source of truth → consistency, no drift, clear ownership
 *
 * ARCHITECTURAL PRINCIPLE:
 * "A system is only as coherent as its authority structure"
 *
 * AUTHORITY DOMAINS:
 * 1. Economic Impact Assessment → THIS SERVICE (EconomicImpactService)
 * 2. Health Monitoring → SystemHealthMonitoringService (delegates here)
 * 3. Stuck State Detection → StuckStateDetectorService (delegates here)
 * 4. Root Cause Analysis → AlertRootCauseAnalyzer (delegates here)
 * 5. System Integrity Coordination → SystemIntegrityService (orchestrates all)
 *
 * USAGE:
 * ```php
 * $impact = app(EconomicImpactService::class);
 *
 * // Assess impact by values
 * $level = $impact->assessByValues(
 *     amount: 150000,
 *     hoursStuck: 30,
 *     usersAffected: 8
 * );
 *
 * // Assess impact by entity
 * $level = $impact->assessByEntity('payment', 12345);
 *
 * // Assess impact by alert
 * $level = $impact->assessByAlert($alert);
 *
 * // Get thresholds
 * $thresholds = $impact->getThresholds();
 * ```
 */
class EconomicImpactService
{
    /**
     * IMPACT ASSESSMENT MATRIX (SINGLE SOURCE OF TRUTH)
     *
     * CRITICAL: >₹500k OR >168h (7 days) OR >100 users
     * HIGH:     ₹100k-500k OR 48-168h OR 20-100 users
     * MEDIUM:   ₹10k-100k OR 12-48h OR 5-20 users
     * LOW:      <₹10k AND <12h AND <5 users
     *
     * NOTE: ANY ONE threshold triggers that level (OR logic, not AND)
     * EXCEPTION: LOW requires ALL conditions (AND logic)
     */
    private const THRESHOLDS = [
        'CRITICAL' => [
            'amount' => 500000,      // >₹5 lakh
            'hours' => 168,          // >7 days
            'users' => 100,          // >100 users
        ],
        'HIGH' => [
            'amount' => 100000,      // >₹1 lakh
            'hours' => 48,           // >2 days
            'users' => 20,           // >20 users
        ],
        'MEDIUM' => [
            'amount' => 10000,       // >₹10k
            'hours' => 12,           // >12 hours
            'users' => 5,            // >5 users
        ],
        // LOW: Everything that doesn't meet MEDIUM thresholds
    ];

    /**
     * Assess economic impact by direct values
     *
     * PRIMARY METHOD - All other methods delegate to this
     *
     * @param float $amount Monetary exposure (rupees)
     * @param float $hoursStuck Time-weighted risk (hours)
     * @param int $usersAffected User impact count
     * @return string Impact level (CRITICAL, HIGH, MEDIUM, LOW)
     */
    public function assessByValues(
        float $amount,
        float $hoursStuck,
        int $usersAffected
    ): string {
        // CRITICAL: ANY threshold exceeded
        if (
            $amount > self::THRESHOLDS['CRITICAL']['amount'] ||
            $hoursStuck > self::THRESHOLDS['CRITICAL']['hours'] ||
            $usersAffected > self::THRESHOLDS['CRITICAL']['users']
        ) {
            return 'CRITICAL';
        }

        // HIGH: ANY threshold exceeded
        if (
            $amount > self::THRESHOLDS['HIGH']['amount'] ||
            $hoursStuck > self::THRESHOLDS['HIGH']['hours'] ||
            $usersAffected > self::THRESHOLDS['HIGH']['users']
        ) {
            return 'HIGH';
        }

        // MEDIUM: ANY threshold exceeded
        if (
            $amount > self::THRESHOLDS['MEDIUM']['amount'] ||
            $hoursStuck > self::THRESHOLDS['MEDIUM']['hours'] ||
            $usersAffected > self::THRESHOLDS['MEDIUM']['users']
        ) {
            return 'MEDIUM';
        }

        // LOW: None of the above
        return 'LOW';
    }

    /**
     * Assess economic impact by entity (payment, investment, bonus, wallet)
     *
     * @param string $entityType (payment, investment, bonus, wallet)
     * @param int $entityId
     * @return string Impact level
     */
    public function assessByEntity(string $entityType, int $entityId): string
    {
        $metrics = $this->extractEntityMetrics($entityType, $entityId);

        return $this->assessByValues(
            $metrics['amount'],
            $metrics['hours_stuck'],
            $metrics['users_affected']
        );
    }

    /**
     * Assess economic impact by alert object
     *
     * @param object $alert Alert from stuck_state_alerts or reconciliation_alerts
     * @return string Impact level
     */
    public function assessByAlert(object $alert): string
    {
        // Extract metrics from alert
        $amount = 0;
        $hoursStuck = 0;
        $usersAffected = 1; // Default: at least 1 user

        // Get entity-specific metrics
        if (isset($alert->entity_type) && isset($alert->entity_id)) {
            $metrics = $this->extractEntityMetrics($alert->entity_type, $alert->entity_id);
            $amount = $metrics['amount'];
            $usersAffected = $metrics['users_affected'];
        }

        // Calculate time stuck
        if (isset($alert->stuck_since)) {
            $hoursStuck = Carbon::parse($alert->stuck_since)->diffInHours(now());
        } elseif (isset($alert->created_at)) {
            $hoursStuck = Carbon::parse($alert->created_at)->diffInHours(now());
        }

        return $this->assessByValues($amount, $hoursStuck, $usersAffected);
    }

    /**
     * Assess economic impact for multiple entities (aggregation)
     *
     * @param array $entities Array of ['type' => string, 'id' => int]
     * @return array ['level' => string, 'total_amount' => float, 'total_users' => int, 'max_hours' => float]
     */
    public function assessAggregateImpact(array $entities): array
    {
        $totalAmount = 0;
        $totalUsers = 0;
        $maxHoursStuck = 0;
        $uniqueUsers = [];

        foreach ($entities as $entity) {
            $metrics = $this->extractEntityMetrics($entity['type'], $entity['id']);
            $totalAmount += $metrics['amount'];
            $maxHoursStuck = max($maxHoursStuck, $metrics['hours_stuck']);

            // Track unique users
            if ($metrics['user_id']) {
                $uniqueUsers[$metrics['user_id']] = true;
            }
        }

        $totalUsers = count($uniqueUsers);

        return [
            'level' => $this->assessByValues($totalAmount, $maxHoursStuck, $totalUsers),
            'total_amount' => $totalAmount,
            'total_users' => $totalUsers,
            'max_hours_stuck' => $maxHoursStuck,
        ];
    }

    /**
     * Get human-readable impact description
     *
     * @param string $impactLevel (CRITICAL, HIGH, MEDIUM, LOW)
     * @return string Description with recommended action
     */
    public function getImpactDescription(string $impactLevel): string
    {
        return match ($impactLevel) {
            'CRITICAL' => 'CRITICAL - Immediate escalation required (On-call engineer + Finance lead, 15min SLA)',
            'HIGH' => 'HIGH - Manual intervention needed (Finance lead, 1h SLA)',
            'MEDIUM' => 'MEDIUM - Monitor closely (Finance admin, 4h SLA)',
            'LOW' => 'LOW - Auto-fix eligible (Any admin, 24h SLA)',
            default => 'UNKNOWN - Escalate for assessment',
        };
    }

    /**
     * Get SLA for impact level (in minutes)
     *
     * @param string $impactLevel
     * @return int SLA in minutes
     */
    public function getSLA(string $impactLevel): int
    {
        return match ($impactLevel) {
            'CRITICAL' => 15,    // 15 minutes
            'HIGH' => 60,        // 1 hour
            'MEDIUM' => 240,     // 4 hours
            'LOW' => 1440,       // 24 hours
            default => 60,       // Default: 1 hour
        };
    }

    /**
     * Check if impact level is auto-fix eligible
     *
     * @param string $impactLevel
     * @return bool
     */
    public function isAutoFixEligible(string $impactLevel): bool
    {
        // ONLY LOW impact is auto-fix eligible
        return $impactLevel === 'LOW';
    }

    /**
     * Get thresholds (for admin configuration UI)
     *
     * @return array
     */
    public function getThresholds(): array
    {
        return self::THRESHOLDS;
    }

    /**
     * Extract metrics from entity
     *
     * @param string $entityType
     * @param int $entityId
     * @return array ['amount' => float, 'hours_stuck' => float, 'users_affected' => int, 'user_id' => int|null]
     */
    private function extractEntityMetrics(string $entityType, int $entityId): array
    {
        return match ($entityType) {
            'payment' => $this->extractPaymentMetrics($entityId),
            'investment' => $this->extractInvestmentMetrics($entityId),
            'bonus' => $this->extractBonusMetrics($entityId),
            'wallet' => $this->extractWalletMetrics($entityId),
            default => ['amount' => 0, 'hours_stuck' => 0, 'users_affected' => 0, 'user_id' => null],
        };
    }

    /**
     * Extract payment metrics
     */
    private function extractPaymentMetrics(int $paymentId): array
    {
        $payment = DB::table('payments')->where('id', $paymentId)->first();

        if (!$payment) {
            return ['amount' => 0, 'hours_stuck' => 0, 'users_affected' => 0, 'user_id' => null];
        }

        $hoursStuck = Carbon::parse($payment->created_at)->diffInHours(now());

        return [
            'amount' => $payment->amount ?? 0,
            'hours_stuck' => $hoursStuck,
            'users_affected' => 1,
            'user_id' => $payment->user_id ?? null,
        ];
    }

    /**
     * Extract investment metrics
     */
    private function extractInvestmentMetrics(int $investmentId): array
    {
        $investment = DB::table('investments')->where('id', $investmentId)->first();

        if (!$investment) {
            return ['amount' => 0, 'hours_stuck' => 0, 'users_affected' => 0, 'user_id' => null];
        }

        $hoursStuck = Carbon::parse($investment->updated_at)->diffInHours(now());

        return [
            'amount' => $investment->amount ?? 0,
            'hours_stuck' => $hoursStuck,
            'users_affected' => 1,
            'user_id' => $investment->user_id ?? null,
        ];
    }

    /**
     * Extract bonus metrics
     */
    private function extractBonusMetrics(int $bonusId): array
    {
        $bonus = DB::table('bonus_transactions')->where('id', $bonusId)->first();

        if (!$bonus) {
            return ['amount' => 0, 'hours_stuck' => 0, 'users_affected' => 0, 'user_id' => null];
        }

        $hoursStuck = Carbon::parse($bonus->created_at)->diffInHours(now());

        return [
            'amount' => $bonus->amount ?? 0,
            'hours_stuck' => $hoursStuck,
            'users_affected' => 1,
            'user_id' => $bonus->user_id ?? null,
        ];
    }

    /**
     * Extract wallet metrics
     */
    private function extractWalletMetrics(int $walletId): array
    {
        // For wallet mismatches, time-weighted risk doesn't apply
        $wallet = DB::table('wallets')->where('id', $walletId)->first();

        if (!$wallet) {
            return ['amount' => 0, 'hours_stuck' => 0, 'users_affected' => 0, 'user_id' => null];
        }

        // Compute discrepancy
        $stored = $wallet->balance_paise / 100;
        $computed = $this->computeWalletBalance($walletId);
        $discrepancy = abs($stored - $computed);

        return [
            'amount' => $discrepancy,
            'hours_stuck' => 0, // Not applicable for balance mismatches
            'users_affected' => 1,
            'user_id' => $wallet->user_id ?? null,
        ];
    }

    /**
     * Compute wallet balance from transactions
     */
    private function computeWalletBalance(int $walletId): float
    {
        $credits = DB::table('transactions')
            ->where('wallet_id', $walletId)
            ->whereIn('type', ['deposit', 'credit', 'bonus', 'refund', 'referral_bonus'])
            ->where('is_reversed', false)
            ->sum('amount_paise');

        $debits = DB::table('transactions')
            ->where('wallet_id', $walletId)
            ->whereIn('type', ['debit', 'withdrawal', 'investment', 'fee', 'tds'])
            ->where('is_reversed', false)
            ->sum('amount_paise');

        return ($credits - $debits) / 100;
    }
}
