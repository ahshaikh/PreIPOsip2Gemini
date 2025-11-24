<?php
// V-FINAL-1730-476 (Created)

namespace App\Jobs;

use App\Models\UserKyc;
use App\Services\VerificationService;
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

    public function handle(VerificationService $service): void
    {
        Log::info("Processing automated KYC for User #{$this->kyc->user_id}");
        
        try {
            $service->runAutomatedKyc($this->kyc);
        } catch (\Exception $e) {
            Log::error("Automated KYC Job Failed: " . $e->getMessage());
            $this->kyc->update(['status' => 'rejected', 'rejection_reason' => 'Automated check failed. Please try again or contact support.']);
        }
    }
}