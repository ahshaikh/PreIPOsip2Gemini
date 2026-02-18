<?php

/**
 * V-DISPUTE-RISK-2026-005: Update User Risk Profile Listener
 *
 * Handles ChargebackConfirmed event to recalculate user risk score
 * and auto-block if threshold exceeded.
 *
 * CRITICAL: This listener is SYNCHRONOUS (does not implement ShouldQueue)
 * because ChargebackConfirmed is dispatched INSIDE a DB::transaction.
 * Making this async would break atomicity guarantees.
 *
 * BEHAVIOR:
 * - Recalculates risk score on chargeback
 * - Auto-blocks user if score >= blocking threshold
 * - Writes AuditLog entry for all changes
 * - Updates last_risk_update_at timestamp
 */

namespace App\Listeners;

use App\Events\ChargebackConfirmed;
use App\Services\AuditLogger;
use App\Services\RiskScoringService;
use Illuminate\Support\Facades\Log;

class UpdateUserRiskProfile
{
    protected RiskScoringService $riskService;

    /**
     * Create the listener.
     */
    public function __construct(RiskScoringService $riskService)
    {
        $this->riskService = $riskService;
    }

    /**
     * Handle the ChargebackConfirmed event.
     *
     * @param ChargebackConfirmed $event
     * @return void
     */
    public function handle(ChargebackConfirmed $event): void
    {
        $user = $event->user;
        $payment = $event->payment;

        // Capture old values for audit trail
        $oldValues = [
            'risk_score' => $user->risk_score,
            'is_blocked' => $user->is_blocked,
            'blocked_reason' => $user->blocked_reason,
        ];

        // Recalculate risk score
        $scoreResult = $this->riskService->calculateScore($user);
        $newScore = $scoreResult['score'];

        // Determine if user should be blocked
        $shouldBlock = $this->riskService->shouldBlock($newScore);
        $wasBlocked = $user->is_blocked;
        $becameBlocked = $shouldBlock && !$wasBlocked;

        // Update user risk profile
        $user->risk_score = $newScore;
        $user->last_risk_update_at = now();

        if ($becameBlocked) {
            $user->is_blocked = true;
            $user->blocked_reason = $this->generateBlockReason($scoreResult, $event);
        }

        $user->save();

        // Capture new values for audit
        $newValues = [
            'risk_score' => $user->risk_score,
            'is_blocked' => $user->is_blocked,
            'blocked_reason' => $user->blocked_reason,
            'score_factors' => $scoreResult['factors'],
            'score_breakdown' => $scoreResult['breakdown'],
        ];

        // Log to financial_contract channel
        Log::channel(config('risk.audit.log_channel', 'financial_contract'))->info('USER RISK PROFILE UPDATED', [
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'old_score' => $oldValues['risk_score'],
            'new_score' => $newScore,
            'became_blocked' => $becameBlocked,
            'factors' => $scoreResult['factors'],
            'chargeback_amount_paise' => $event->chargebackAmountPaise,
            'chargeback_reason' => $event->reason,
        ]);

        // Create audit log entry
        $this->createAuditLog($user, $payment, $oldValues, $newValues, $becameBlocked);

        // If user became blocked, log critical event
        if ($becameBlocked) {
            Log::channel(config('risk.audit.log_channel', 'financial_contract'))->critical('USER AUTO-BLOCKED DUE TO RISK SCORE', [
                'user_id' => $user->id,
                'email' => $user->email,
                'risk_score' => $newScore,
                'blocking_threshold' => config('risk.thresholds.blocking', 70),
                'trigger_payment_id' => $payment->id,
                'chargeback_amount_paise' => $event->chargebackAmountPaise,
            ]);
        }
    }

    /**
     * Generate a human-readable block reason.
     *
     * @param array $scoreResult The risk score calculation result
     * @param ChargebackConfirmed $event The triggering event
     * @return string
     */
    private function generateBlockReason(array $scoreResult, ChargebackConfirmed $event): string
    {
        $factors = $scoreResult['factors'];
        $threshold = config('risk.thresholds.blocking', 70);

        $reasonParts = [
            "Auto-blocked: Risk score ({$scoreResult['score']}) exceeded threshold ({$threshold}).",
            "Triggered by chargeback on Payment #{$event->payment->id}.",
        ];

        if (!empty($factors)) {
            $reasonParts[] = "Risk factors: " . implode(', ', $factors) . ".";
        }

        return implode(' ', $reasonParts);
    }

    /**
     * Create audit log entry for risk profile change.
     *
     * @param mixed $user
     * @param mixed $payment
     * @param array $oldValues
     * @param array $newValues
     * @param bool $becameBlocked
     */
    private function createAuditLog($user, $payment, array $oldValues, array $newValues, bool $becameBlocked): void
    {
        $action = $becameBlocked ? 'user_blocked' : 'risk_updated';
        $riskLevel = $becameBlocked ? 'critical' : 'high';

        $description = $becameBlocked
            ? "User auto-blocked due to risk score ({$newValues['risk_score']}) after chargeback on Payment #{$payment->id}"
            : "User risk score updated from {$oldValues['risk_score']} to {$newValues['risk_score']} after chargeback on Payment #{$payment->id}";

        AuditLogger::log(
            $action,
            'risk_management',
            $description,
            [
                'target_type' => get_class($user),
                'target_id' => $user->id,
                'target_name' => $user->email,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'metadata' => [
                    'trigger_payment_id' => $payment->id,
                    'trigger_event' => 'chargeback_confirmed',
                    'chargeback_amount_paise' => $payment->chargeback_amount_paise,
                    'blocking_threshold' => config('risk.thresholds.blocking', 70),
                ],
                'risk_level' => $riskLevel,
                'requires_review' => $becameBlocked,
            ]
        );
    }

    /**
     * Handle a job failure (for future queueable version).
     */
    public function failed(ChargebackConfirmed $event, \Throwable $exception): void
    {
        Log::channel(config('risk.audit.log_channel', 'financial_contract'))->error('UpdateUserRiskProfile FAILED', [
            'user_id' => $event->user->id,
            'payment_id' => $event->payment->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
