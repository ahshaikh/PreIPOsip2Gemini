<?php

namespace App\Services\Kyc;

use App\Models\User;
use App\Models\UserKyc;
use App\Events\KycStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * KycStatusService
 * * [AUDIT FIX]: Consolidates status transition logic to prevent "State Drift."
 * * Handles audit logging and triggers secondary actions (bonuses, notifications).
 */
class KycStatusService
{
    /**
     * Update the KYC status of a user.
     *
     * @param UserKyc $kyc
     * @param string $status (pending|approved|rejected)
     * @param string|null $remarks
     * @return UserKyc
     */
    public function transitionStatus(UserKyc $kyc, string $status, ?string $remarks = null): UserKyc
    {
        return DB::transaction(function () use ($kyc, $status, $remarks) {
            $oldStatus = $kyc->status;

            // 1. Update the KYC Record
            $kyc->update([
                'status' => $status,
                'admin_remarks' => $remarks,
                'verified_at' => $status === 'approved' ? now() : $kyc->verified_at,
            ]);

            // 2. Sync the status to the User model for fast lookups in AuthGating
            $kyc->user->update(['kyc_status' => $status]);

            // 3. Log the transition for audit purposes
            Log::info("KYC Status Transition", [
                'user_id' => $kyc->user_id,
                'from' => $oldStatus,
                'to' => $status,
                'admin_id' => auth()->id()
            ]);

            // 4. Trigger Events (e.g., Send Email, Process Referral Bonus)
            event(new KycStatusUpdated($kyc, $oldStatus));

            return $kyc;
        });
    }
}