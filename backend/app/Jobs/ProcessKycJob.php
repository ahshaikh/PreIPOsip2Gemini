<?php
// V-FINAL-1730-476 (Created) | V-FIX-STATE-MACHINE (Orchestration Compliance)

namespace App\Jobs;

use App\Models\UserKyc;
use App\Services\VerificationService;
use App\Services\Kyc\KycStatusService;
use App\Enums\KycStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessKycJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(public UserKyc $kyc)
    {
    }

    /**
     * V-FIX-STATE-MACHINE: Use KycStatusService for all status transitions
     * Background job transitions: submitted → processing → verified/rejected
     */
    public function handle(VerificationService $service, KycStatusService $statusService): void
    {
        Log::info("Processing automated KYC for User #{$this->kyc->user_id}");

        try {
            // Transition to PROCESSING when job starts
            if ($this->kyc->status === KycStatus::SUBMITTED->value) {
                $statusService->transitionTo($this->kyc, KycStatus::PROCESSING);
            }

            $service->runAutomatedKyc($this->kyc);
        } catch (\Exception $e) {
            Log::error("Automated KYC Job Failed: " . $e->getMessage());

            // V-FIX-STATE-MACHINE: Use service to transition to RESUBMISSION_REQUIRED
            // Cannot go to REJECTED directly from SUBMITTED (state machine requires PROCESSING first)
            try {
                if ($this->kyc->status === KycStatus::SUBMITTED->value) {
                    // If still in submitted, transition to resubmission_required
                    $statusService->transitionTo(
                        $this->kyc,
                        KycStatus::RESUBMISSION_REQUIRED,
                        ['admin_remarks' => 'Automated check failed. Please verify your documents and try again.']
                    );
                } elseif ($this->kyc->status === KycStatus::PROCESSING->value) {
                    // If in processing, can transition to rejected
                    $statusService->transitionTo(
                        $this->kyc,
                        KycStatus::REJECTED,
                        ['rejection_reason' => 'Automated check failed. Please contact support.']
                    );
                }
            } catch (\Exception $transitionError) {
                Log::error("Failed to transition KYC status after error", [
                    'kyc_id' => $this->kyc->id,
                    'current_status' => $this->kyc->status,
                    'error' => $transitionError->getMessage()
                ]);
            }
        }
    }
}