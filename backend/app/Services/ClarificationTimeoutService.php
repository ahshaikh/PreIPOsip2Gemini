<?php

namespace App\Services;

use App\Models\DisclosureClarification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 3 HARDENING - Issue 3: Clarification Time Semantics
 *
 * PURPOSE:
 * Add platform-owned timeout rules for clarification responses.
 * Prevents disclosures from being stuck indefinitely in clarification status.
 *
 * SLA RULES:
 * - Issuer response: 5 business days from clarification asked
 * - Admin review: 3 business days from answer submitted
 * - Escalation: Auto-escalate if overdue beyond threshold
 * - Expiry: Mark as expired if no response after escalation
 *
 * BUSINESS DAYS:
 * Excludes weekends and holidays (configurable).
 */
class ClarificationTimeoutService
{
    /**
     * SLA thresholds (in business days)
     */
    public const ISSUER_RESPONSE_SLA_DAYS = 5;
    public const ADMIN_REVIEW_SLA_DAYS = 3;
    public const ESCALATION_THRESHOLD_DAYS = 2; // Days overdue before escalation
    public const EXPIRY_THRESHOLD_DAYS = 5; // Days after escalation before expiry

    /**
     * Set issuer response deadline when clarification is asked
     *
     * @param DisclosureClarification $clarification
     * @return void
     */
    public function setIssuerResponseDeadline(DisclosureClarification $clarification): void
    {
        if (!$clarification->asked_at) {
            Log::warning('Cannot set issuer deadline - clarification not yet asked', [
                'clarification_id' => $clarification->id,
            ]);
            return;
        }

        $dueDate = $this->addBusinessDays(
            Carbon::parse($clarification->asked_at),
            self::ISSUER_RESPONSE_SLA_DAYS
        );

        $clarification->issuer_response_due_at = $dueDate;
        $clarification->save();

        Log::info('Issuer response deadline set', [
            'clarification_id' => $clarification->id,
            'asked_at' => $clarification->asked_at,
            'due_at' => $dueDate,
            'sla_days' => self::ISSUER_RESPONSE_SLA_DAYS,
        ]);
    }

    /**
     * Set admin review deadline when issuer answers
     *
     * @param DisclosureClarification $clarification
     * @return void
     */
    public function setAdminReviewDeadline(DisclosureClarification $clarification): void
    {
        if (!$clarification->answered_at) {
            Log::warning('Cannot set admin deadline - clarification not yet answered', [
                'clarification_id' => $clarification->id,
            ]);
            return;
        }

        $dueDate = $this->addBusinessDays(
            Carbon::parse($clarification->answered_at),
            self::ADMIN_REVIEW_SLA_DAYS
        );

        $clarification->admin_review_due_at = $dueDate;
        $clarification->save();

        Log::info('Admin review deadline set', [
            'clarification_id' => $clarification->id,
            'answered_at' => $clarification->answered_at,
            'due_at' => $dueDate,
            'sla_days' => self::ADMIN_REVIEW_SLA_DAYS,
        ]);
    }

    /**
     * Check and update overdue clarifications
     *
     * Runs periodically (cron job) to mark overdue clarifications.
     *
     * @return array Statistics of checks performed
     */
    public function checkOverdueClarifications(): array
    {
        $now = now();
        $stats = [
            'issuer_overdue_count' => 0,
            'admin_overdue_count' => 0,
            'escalated_count' => 0,
            'expired_count' => 0,
        ];

        // Check issuer response overdue
        $issuerOverdue = DisclosureClarification::whereIn('status', ['open', 'disputed'])
            ->whereNotNull('issuer_response_due_at')
            ->where('issuer_response_due_at', '<', $now)
            ->where('issuer_response_overdue', false)
            ->whereNull('answered_at')
            ->get();

        foreach ($issuerOverdue as $clarification) {
            $clarification->issuer_response_overdue = true;
            $clarification->save();
            $stats['issuer_overdue_count']++;

            // Check if should escalate
            $daysOverdue = Carbon::parse($clarification->issuer_response_due_at)->diffInDays($now);
            if ($daysOverdue >= self::ESCALATION_THRESHOLD_DAYS) {
                $this->escalateIssuerNonResponse($clarification);
                $stats['escalated_count']++;
            }
        }

        // Check admin review overdue
        $adminOverdue = DisclosureClarification::where('status', 'answered')
            ->whereNotNull('admin_review_due_at')
            ->where('admin_review_due_at', '<', $now)
            ->where('admin_review_overdue', false)
            ->get();

        foreach ($adminOverdue as $clarification) {
            $clarification->admin_review_overdue = true;
            $clarification->save();
            $stats['admin_overdue_count']++;

            // Check if should escalate
            $daysOverdue = Carbon::parse($clarification->admin_review_due_at)->diffInDays($now);
            if ($daysOverdue >= self::ESCALATION_THRESHOLD_DAYS) {
                $this->escalateAdminNonReview($clarification);
                $stats['escalated_count']++;
            }
        }

        // Check for expired escalations (no response after escalation threshold)
        $expiredEscalations = DisclosureClarification::whereNotNull('escalated_at')
            ->where('is_expired', false)
            ->where('escalated_at', '<', $now->copy()->subDays(self::EXPIRY_THRESHOLD_DAYS))
            ->get();

        foreach ($expiredEscalations as $clarification) {
            $this->expireClarification($clarification);
            $stats['expired_count']++;
        }

        Log::info('Clarification timeout check completed', $stats);

        return $stats;
    }

    /**
     * Escalate clarification due to issuer non-response
     *
     * @param DisclosureClarification $clarification
     * @return void
     */
    protected function escalateIssuerNonResponse(DisclosureClarification $clarification): void
    {
        $daysOverdue = Carbon::parse($clarification->issuer_response_due_at)->diffInDays(now());

        $clarification->escalated_at = now();
        $clarification->escalation_reason = "Issuer has not responded for {$daysOverdue} days (SLA: " . self::ISSUER_RESPONSE_SLA_DAYS . " business days)";
        $clarification->priority = 'critical'; // Escalate priority
        $clarification->save();

        // Notify admin and company
        Log::warning('CLARIFICATION ESCALATED: Issuer non-response', [
            'clarification_id' => $clarification->id,
            'company_id' => $clarification->company_id,
            'disclosure_id' => $clarification->company_disclosure_id,
            'days_overdue' => $daysOverdue,
            'asked_at' => $clarification->asked_at,
            'due_at' => $clarification->issuer_response_due_at,
        ]);

        // TODO: Send notification to admin and company
    }

    /**
     * Escalate clarification due to admin non-review
     *
     * @param DisclosureClarification $clarification
     * @return void
     */
    protected function escalateAdminNonReview(DisclosureClarification $clarification): void
    {
        $daysOverdue = Carbon::parse($clarification->admin_review_due_at)->diffInDays(now());

        $clarification->escalated_at = now();
        $clarification->escalation_reason = "Admin has not reviewed answer for {$daysOverdue} days (SLA: " . self::ADMIN_REVIEW_SLA_DAYS . " business days)";
        $clarification->priority = 'critical'; // Escalate priority
        $clarification->save();

        // Notify admin supervisor
        Log::warning('CLARIFICATION ESCALATED: Admin non-review', [
            'clarification_id' => $clarification->id,
            'company_id' => $clarification->company_id,
            'disclosure_id' => $clarification->company_disclosure_id,
            'days_overdue' => $daysOverdue,
            'answered_at' => $clarification->answered_at,
            'due_at' => $clarification->admin_review_due_at,
        ]);

        // TODO: Send notification to admin supervisor
    }

    /**
     * Expire clarification after prolonged inactivity
     *
     * @param DisclosureClarification $clarification
     * @return void
     */
    protected function expireClarification(DisclosureClarification $clarification): void
    {
        $daysStale = Carbon::parse($clarification->escalated_at)->diffInDays(now());

        $clarification->is_expired = true;
        $clarification->expired_at = now();
        $clarification->expiry_reason = "No response {$daysStale} days after escalation";
        $clarification->status = 'expired'; // New status
        $clarification->save();

        Log::critical('CLARIFICATION EXPIRED: Prolonged inactivity', [
            'clarification_id' => $clarification->id,
            'company_id' => $clarification->company_id,
            'disclosure_id' => $clarification->company_disclosure_id,
            'escalated_at' => $clarification->escalated_at,
            'expired_at' => $clarification->expired_at,
            'days_stale' => $daysStale,
        ]);

        // Auto-reject disclosure if it has expired clarifications
        $this->handleExpiredClarificationForDisclosure($clarification);
    }

    /**
     * Handle disclosure with expired clarifications
     *
     * OPTIONS:
     * 1. Auto-reject disclosure (clarification was blocking)
     * 2. Mark as stale, require fresh submission
     * 3. Allow admin to manually review and decide
     *
     * @param DisclosureClarification $clarification
     * @return void
     */
    protected function handleExpiredClarificationForDisclosure(DisclosureClarification $clarification): void
    {
        $disclosure = $clarification->companyDisclosure;

        if (!$disclosure) {
            return;
        }

        // If clarification was blocking, auto-reject disclosure
        if ($clarification->is_blocking) {
            Log::warning('Auto-rejecting disclosure due to expired blocking clarification', [
                'disclosure_id' => $disclosure->id,
                'clarification_id' => $clarification->id,
                'company_id' => $disclosure->company_id,
            ]);

            // TODO: Auto-reject disclosure
            // This would call DisclosureReviewService::reject() with automated reason
        }
    }

    /**
     * Get clarification timeout status
     *
     * @param DisclosureClarification $clarification
     * @return array Timeout status details
     */
    public function getTimeoutStatus(DisclosureClarification $clarification): array
    {
        $now = now();

        // Issuer response timeout
        $issuerStatus = null;
        if ($clarification->issuer_response_due_at) {
            $dueDate = Carbon::parse($clarification->issuer_response_due_at);
            $issuerStatus = [
                'due_at' => $dueDate,
                'is_overdue' => $clarification->issuer_response_overdue,
                'days_remaining' => $dueDate->diffInDays($now, false), // Negative if future
                'hours_remaining' => $dueDate->diffInHours($now, false),
                'urgency' => $this->calculateUrgency($dueDate, $now),
            ];
        }

        // Admin review timeout
        $adminStatus = null;
        if ($clarification->admin_review_due_at) {
            $dueDate = Carbon::parse($clarification->admin_review_due_at);
            $adminStatus = [
                'due_at' => $dueDate,
                'is_overdue' => $clarification->admin_review_overdue,
                'days_remaining' => $dueDate->diffInDays($now, false),
                'hours_remaining' => $dueDate->diffInHours($now, false),
                'urgency' => $this->calculateUrgency($dueDate, $now),
            ];
        }

        // Escalation status
        $escalationStatus = null;
        if ($clarification->escalated_at) {
            $escalationStatus = [
                'escalated_at' => $clarification->escalated_at,
                'escalation_reason' => $clarification->escalation_reason,
                'days_since_escalation' => Carbon::parse($clarification->escalated_at)->diffInDays($now),
                'will_expire_at' => Carbon::parse($clarification->escalated_at)->addDays(self::EXPIRY_THRESHOLD_DAYS),
            ];
        }

        return [
            'issuer_timeout' => $issuerStatus,
            'admin_timeout' => $adminStatus,
            'escalation' => $escalationStatus,
            'is_expired' => $clarification->is_expired,
            'expired_at' => $clarification->expired_at,
            'expiry_reason' => $clarification->expiry_reason,
        ];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Add business days to a date (excludes weekends)
     *
     * TODO: Add holiday calendar support
     *
     * @param Carbon $startDate
     * @param int $businessDays
     * @return Carbon
     */
    protected function addBusinessDays(Carbon $startDate, int $businessDays): Carbon
    {
        $date = $startDate->copy();
        $addedDays = 0;

        while ($addedDays < $businessDays) {
            $date->addDay();
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($date->dayOfWeek !== Carbon::SATURDAY && $date->dayOfWeek !== Carbon::SUNDAY) {
                $addedDays++;
            }
        }

        return $date;
    }

    /**
     * Calculate urgency level based on time remaining
     *
     * @param Carbon $dueDate
     * @param Carbon $now
     * @return string Urgency level: low, medium, high, critical
     */
    protected function calculateUrgency(Carbon $dueDate, Carbon $now): string
    {
        $hoursRemaining = $dueDate->diffInHours($now, false);

        if ($hoursRemaining < 0) {
            return 'overdue';
        } elseif ($hoursRemaining <= 24) {
            return 'critical';
        } elseif ($hoursRemaining <= 48) {
            return 'high';
        } elseif ($hoursRemaining <= 72) {
            return 'medium';
        } else {
            return 'low';
        }
    }
}
