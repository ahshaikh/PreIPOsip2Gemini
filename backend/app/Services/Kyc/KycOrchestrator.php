<?php
// V-AUDIT-MODULE2-004 (Created) - KYC State Orchestrator
// Purpose: Centralized KYC state management and decision engine
// This service determines when a KYC should be marked as fully verified
// based on granular component verification status

namespace App\Services\Kyc;

use App\Models\UserKyc;
use App\Enums\KycStatus;
use App\Notifications\KycVerified;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * KycOrchestrator - State Machine for KYC Verification
 *
 * This service is responsible for:
 * 1. Evaluating if all required KYC components are verified
 * 2. Updating the overall KYC status based on component status
 * 3. Triggering notifications when KYC becomes fully verified
 * 4. Ensuring business logic consistency across all KYC flows
 *
 * CRITICAL: This is the ONLY place where $kyc->status should be set to 'verified'
 */
class KycOrchestrator
{
    /**
     * Update individual component verification status
     *
     * @param UserKyc $kyc
     * @param string $component - 'aadhaar', 'pan', 'bank', 'demat'
     * @param string $source - 'digilocker', 'api', 'manual', 'penny_drop'
     * @return UserKyc
     */
    public function markComponentAsVerified(UserKyc $kyc, string $component, string $source): UserKyc
    {
        DB::transaction(function () use ($kyc, $component, $source) {
            // Update component-specific flags
            switch ($component) {
                case 'aadhaar':
                    $kyc->is_aadhaar_verified = true;
                    $kyc->aadhaar_verified_at = now();
                    $kyc->aadhaar_verification_source = $source;
                    Log::info("KYC #{$kyc->id}: Aadhaar verified via {$source}");
                    break;

                case 'pan':
                    $kyc->is_pan_verified = true;
                    $kyc->pan_verified_at = now();
                    $kyc->pan_verification_source = $source;
                    Log::info("KYC #{$kyc->id}: PAN verified via {$source}");
                    break;

                case 'bank':
                    $kyc->is_bank_verified = true;
                    $kyc->bank_verified_at = now();
                    $kyc->bank_verification_source = $source;
                    Log::info("KYC #{$kyc->id}: Bank verified via {$source}");
                    break;

                case 'demat':
                    $kyc->is_demat_verified = true;
                    $kyc->demat_verified_at = now();
                    Log::info("KYC #{$kyc->id}: Demat verified via {$source}");
                    break;

                default:
                    throw new \InvalidArgumentException("Invalid component: {$component}");
            }

            $kyc->save();

            // After updating component, check if ALL required components are now verified
            $this->evaluateOverallStatus($kyc);
        });

        return $kyc->fresh();
    }

    /**
     * Mark a component as failed/unverified
     *
     * @param UserKyc $kyc
     * @param string $component
     * @param string $reason
     * @return UserKyc
     */
    public function markComponentAsFailed(UserKyc $kyc, string $component, string $reason): UserKyc
    {
        DB::transaction(function () use ($kyc, $component, $reason) {
            // Update status to processing or rejected based on severity
            if ($kyc->status !== KycStatus::REJECTED->value) {
                $kyc->status = KycStatus::PROCESSING->value;
            }

            // Log the failure reason
            Log::warning("KYC #{$kyc->id}: {$component} verification failed - {$reason}");

            $kyc->save();
        });

        return $kyc->fresh();
    }

    /**
     * Evaluate overall KYC status based on component verification
     *
     * This is the CENTRAL decision point for KYC verification.
     * The global status is set to 'verified' ONLY when ALL required components pass.
     *
     * Required Components (as per FSD):
     * - Aadhaar/Identity: MUST be verified
     * - PAN: MUST be verified
     * - Bank Account: MUST be verified
     * - Demat: OPTIONAL (as per business rules from settings)
     *
     * @param UserKyc $kyc
     * @return void
     */
    public function evaluateOverallStatus(UserKyc $kyc): void
    {
        // Don't re-evaluate if already in a finalized state (unless forced)
        if (in_array($kyc->status, [KycStatus::VERIFIED->value, KycStatus::REJECTED->value])) {
            // Allow re-evaluation only if manually triggered or if components changed
            // For now, skip to prevent accidental status changes
            Log::info("KYC #{$kyc->id}: Already in finalized state ({$kyc->status}), skipping re-evaluation");
            return;
        }

        // Check if ALL required components are verified
        $isAadhaarVerified = (bool) $kyc->is_aadhaar_verified;
        $isPanVerified = (bool) $kyc->is_pan_verified;
        $isBankVerified = (bool) $kyc->is_bank_verified;

        // Check if Demat is required (from settings)
        $isDematRequired = (bool) setting('kyc_require_demat', false);
        $isDematVerified = (bool) $kyc->is_demat_verified;

        // Log current verification state for debugging
        Log::debug("KYC #{$kyc->id} Verification Status", [
            'aadhaar' => $isAadhaarVerified,
            'pan' => $isPanVerified,
            'bank' => $isBankVerified,
            'demat' => $isDematVerified,
            'demat_required' => $isDematRequired,
        ]);

        // Determine if all requirements are met
        $allRequirementsMet = $isAadhaarVerified && $isPanVerified && $isBankVerified;

        // If demat is required, include it in the check
        if ($isDematRequired) {
            $allRequirementsMet = $allRequirementsMet && $isDematVerified;
        }

        // Update overall status based on requirements
        if ($allRequirementsMet) {
            DB::transaction(function () use ($kyc) {
                $kyc->status = KycStatus::VERIFIED->value;
                $kyc->verified_at = now();
                $kyc->rejection_reason = null; // Clear any previous rejection reason
                $kyc->save();

                Log::info("KYC #{$kyc->id}: ALL components verified. Status set to VERIFIED.");

                // Send notification to user
                try {
                    $kyc->user->notify(new KycVerified());
                } catch (\Exception $e) {
                    Log::error("Failed to send KYC verified notification: " . $e->getMessage());
                }
            });
        } else {
            // Not all components verified yet - keep in PROCESSING or SUBMITTED state
            if ($kyc->status === KycStatus::PENDING->value) {
                $kyc->status = KycStatus::SUBMITTED->value;
                $kyc->save();
            }

            Log::info("KYC #{$kyc->id}: Not all components verified yet. Current status: {$kyc->status}");
        }
    }

    /**
     * Check if KYC is fully verified
     *
     * @param UserKyc $kyc
     * @return bool
     */
    public function isFullyVerified(UserKyc $kyc): bool
    {
        $isDematRequired = (bool) setting('kyc_require_demat', false);

        $requiredComponentsVerified = $kyc->is_aadhaar_verified
            && $kyc->is_pan_verified
            && $kyc->is_bank_verified;

        if ($isDematRequired) {
            $requiredComponentsVerified = $requiredComponentsVerified && $kyc->is_demat_verified;
        }

        return $requiredComponentsVerified && $kyc->status === KycStatus::VERIFIED->value;
    }

    /**
     * Get verification progress percentage
     *
     * @param UserKyc $kyc
     * @return int
     */
    public function getVerificationProgress(UserKyc $kyc): int
    {
        $isDematRequired = (bool) setting('kyc_require_demat', false);
        $totalComponents = $isDematRequired ? 4 : 3;

        $verifiedCount = 0;
        if ($kyc->is_aadhaar_verified) $verifiedCount++;
        if ($kyc->is_pan_verified) $verifiedCount++;
        if ($kyc->is_bank_verified) $verifiedCount++;
        if ($isDematRequired && $kyc->is_demat_verified) $verifiedCount++;

        return (int) round(($verifiedCount / $totalComponents) * 100);
    }

    /**
     * Get list of pending verification components
     *
     * @param UserKyc $kyc
     * @return array
     */
    public function getPendingComponents(UserKyc $kyc): array
    {
        $pending = [];

        if (!$kyc->is_aadhaar_verified) {
            $pending[] = 'aadhaar';
        }

        if (!$kyc->is_pan_verified) {
            $pending[] = 'pan';
        }

        if (!$kyc->is_bank_verified) {
            $pending[] = 'bank';
        }

        if (setting('kyc_require_demat', false) && !$kyc->is_demat_verified) {
            $pending[] = 'demat';
        }

        return $pending;
    }

    /**
     * Reset KYC verification status (for resubmission)
     *
     * @param UserKyc $kyc
     * @param array $componentsToReset - ['aadhaar', 'pan', 'bank', 'demat']
     * @return UserKyc
     */
    public function resetVerification(UserKyc $kyc, array $componentsToReset = []): UserKyc
    {
        DB::transaction(function () use ($kyc, $componentsToReset) {
            // If no specific components specified, reset all
            if (empty($componentsToReset)) {
                $componentsToReset = ['aadhaar', 'pan', 'bank', 'demat'];
            }

            foreach ($componentsToReset as $component) {
                switch ($component) {
                    case 'aadhaar':
                        $kyc->is_aadhaar_verified = false;
                        $kyc->aadhaar_verified_at = null;
                        $kyc->aadhaar_verification_source = null;
                        break;

                    case 'pan':
                        $kyc->is_pan_verified = false;
                        $kyc->pan_verified_at = null;
                        $kyc->pan_verification_source = null;
                        break;

                    case 'bank':
                        $kyc->is_bank_verified = false;
                        $kyc->bank_verified_at = null;
                        $kyc->bank_verification_source = null;
                        break;

                    case 'demat':
                        $kyc->is_demat_verified = false;
                        $kyc->demat_verified_at = null;
                        break;
                }
            }

            // Reset overall status
            $kyc->status = KycStatus::RESUBMISSION_REQUIRED->value;
            $kyc->verified_at = null;
            $kyc->verified_by = null;

            $kyc->save();

            Log::info("KYC #{$kyc->id}: Verification reset for components: " . implode(', ', $componentsToReset));
        });

        return $kyc->fresh();
    }
}
