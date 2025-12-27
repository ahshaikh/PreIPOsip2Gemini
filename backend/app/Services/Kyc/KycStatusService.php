<?php

namespace App\Services\Kyc;

use App\Models\User;
use App\Models\UserKyc;
use App\Events\KycStatusUpdated;
use App\Enums\KycStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * KycStatusService
 *
 * [P1.2 FIX]: Implements State Machine to enforce valid transitions.
 * [AUDIT FIX]: Consolidates status transition logic to prevent "State Drift."
 * Handles audit logging and triggers secondary actions (bonuses, notifications).
 *
 * STATE MACHINE:
 * PENDING → SUBMITTED → PROCESSING → VERIFIED (final)
 *                    ↓              ↓
 *              RESUBMISSION_REQUIRED  REJECTED (final)
 *                    ↓
 *                SUBMITTED (resubmit cycle)
 */
class KycStatusService
{
    /**
     * [P1.2 FIX]: Allowed state transitions.
     *
     * WHY: Makes invalid transitions STRUCTURALLY IMPOSSIBLE.
     * - Admin cannot directly mark PENDING as VERIFIED (must go through SUBMITTED → PROCESSING)
     * - Cannot transition from finalized states (VERIFIED/REJECTED)
     * - Enforces workflow integrity at service level
     */
    private const ALLOWED_TRANSITIONS = [
        'pending' => ['submitted'],
        'submitted' => ['processing', 'rejected', 'resubmission_required'],
        'processing' => ['verified', 'rejected', 'resubmission_required'],
        'resubmission_required' => ['submitted'], // User resubmits
        'verified' => [], // Final state - no transitions allowed
        'rejected' => [], // Final state - no transitions allowed
    ];

    /**
     * [P1.2 FIX]: Transition KYC status with state machine validation.
     *
     * This is the ONLY way to change KYC status. Direct $kyc->update(['status' => ...])
     * bypasses validation and events, which is PROHIBITED.
     *
     * @param UserKyc $kyc
     * @param KycStatus $newStatus Use KycStatus enum (not string)
     * @param array $data Additional data (remarks, verified_by, etc.)
     * @return UserKyc
     * @throws InvalidArgumentException if transition is not allowed
     */
    public function transitionTo(UserKyc $kyc, KycStatus $newStatus, array $data = []): UserKyc
    {
        return DB::transaction(function () use ($kyc, $newStatus, $data) {
            $oldStatus = $kyc->status;

            // [P1.2 FIX]: Validate state transition
            if (!$this->canTransitionTo($oldStatus, $newStatus->value)) {
                throw new InvalidArgumentException(
                    "Invalid KYC transition: {$oldStatus} → {$newStatus->value}. " .
                    "Allowed transitions from {$oldStatus}: " .
                    implode(', ', self::ALLOWED_TRANSITIONS[$oldStatus] ?? [])
                );
            }

            // Prepare update data
            $updateData = array_merge([
                'status' => $newStatus->value,
            ], $data);

            // Set verified_at timestamp for VERIFIED status
            if ($newStatus === KycStatus::VERIFIED && !isset($updateData['verified_at'])) {
                $updateData['verified_at'] = now();
            }

            // Clear verified_at for non-verified statuses
            if ($newStatus !== KycStatus::VERIFIED && $oldStatus === KycStatus::VERIFIED->value) {
                $updateData['verified_at'] = null;
            }

            // 1. Update the KYC Record
            $kyc->update($updateData);

            // 2. Sync the status to the User model for fast lookups in AuthGating
            $kyc->user->update(['kyc_status' => $newStatus->value]);

            // 3. Log the transition for audit purposes
            Log::info("KYC Status Transition", [
                'user_id' => $kyc->user_id,
                'from' => $oldStatus,
                'to' => $newStatus->value,
                'admin_id' => auth()->id(),
                'data' => $data
            ]);

            // 4. [CRITICAL]: Trigger Events (e.g., Send Email, Process Referral Bonus)
            // This ensures all downstream effects happen (referral completion, etc.)
            event(new KycStatusUpdated($kyc, $oldStatus));

            return $kyc;
        });
    }

    /**
     * [P1.2 FIX]: Check if transition from current status to new status is allowed.
     *
     * @param string $currentStatus
     * @param string $newStatus
     * @return bool
     */
    public function canTransitionTo(string $currentStatus, string $newStatus): bool
    {
        // If current status doesn't exist in transitions map, deny
        if (!isset(self::ALLOWED_TRANSITIONS[$currentStatus])) {
            return false;
        }

        // Allow self-transition (no-op)
        if ($currentStatus === $newStatus) {
            return true;
        }

        return in_array($newStatus, self::ALLOWED_TRANSITIONS[$currentStatus]);
    }

    /**
     * [P1.2 FIX]: Get allowed next states for current status.
     *
     * Useful for UI to show only valid action buttons.
     *
     * @param UserKyc $kyc
     * @return array<KycStatus>
     */
    public function getAllowedTransitions(UserKyc $kyc): array
    {
        $allowedValues = self::ALLOWED_TRANSITIONS[$kyc->status] ?? [];

        return array_map(
            fn($value) => KycStatus::from($value),
            $allowedValues
        );
    }

    /**
     * @deprecated Use transitionTo() instead
     */
    public function transitionStatus(UserKyc $kyc, string $status, ?string $remarks = null): UserKyc
    {
        trigger_error(
            'KycStatusService::transitionStatus() is deprecated. Use transitionTo() with KycStatus enum.',
            E_USER_DEPRECATED
        );

        $data = [];
        if ($remarks) {
            $data['admin_remarks'] = $remarks;
        }

        return $this->transitionTo($kyc, KycStatus::from($status), $data);
    }
}