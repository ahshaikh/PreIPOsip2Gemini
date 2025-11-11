<?php
// V-PHASE3-1730-082

namespace App\Jobs;

use App\Models\Payment;
use App\Services\AllocationService;
use App\Services\BonusCalculatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessSuccessfulPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payment $payment)
    {
    }

    public function handle(
        BonusCalculatorService $bonusService, 
        AllocationService $allocationService
    ): void
    {
        DB::transaction(function () use ($bonusService, $allocationService) {
            // 1. Calculate Bonuses
            $totalBonusAmount = $bonusService->calculateAndAwardBonuses($this->payment);
            $this->payment->update(['bonuses_processed' => true]);

            // 2. Allocate Shares
            $totalInvestmentValue = $this->payment->amount + $totalBonusAmount;
            $allocationService->allocateShares($this->payment, $totalInvestmentValue);
            $this->payment->update(['shares_allocated' => true]);

            // 3. Check for Referral Completion
            ProcessReferralJob::dispatchIf($this->isFirstPayment(), $this->payment->user);
            
            // 4. Generate Lucky Draw Entries
            GenerateLuckyDrawEntryJob::dispatchIf($this->payment->is_on_time, $this->payment);

        });

        // 5. Send Notifications
        // SendPaymentConfirmationEmailJob::dispatch($this->payment);
        
        Log::info("All post-payment actions completed for Payment {$this->payment->id}");
    }

    private function isFirstPayment(): bool
    {
        return Payment::where('user_id', $this->payment->user_id)
                      ->where('status', 'paid')
                      ->count() === 1;
    }
}