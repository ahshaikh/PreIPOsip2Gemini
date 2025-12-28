<?php
// V-PHASE3-1730-086 (Created) | V-FINAL-1730-301 | V-FINAL-1730-365-Refactored | V-FINAL-1730-367 (Refactored to use Service)

namespace App\Jobs;

use App\Models\Payment;
use App\Services\LuckyDrawService; // <-- IMPORT
use Illuminate.Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate.Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateLuckyDrawEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payment $payment)
    {
    }

    /**
     * Execute the job.
     *
     * [G.22 FIX]: Added idempotency protection to prevent duplicate lucky draw entries
     */
    public function handle(
        LuckyDrawService $luckyDrawService,
        \App\Services\IdempotencyService $idempotency
    ): void {
        $idempotencyKey = "lucky_draw_entry:{$this->payment->id}";

        // [G.22]: Check if already processed to prevent duplicate entries
        if ($idempotency->isAlreadyExecuted($idempotencyKey, self::class)) {
            Log::info("Lucky draw entries already generated for Payment #{$this->payment->id}. Skipping.");
            return;
        }

        // [G.22]: Execute with idempotency protection
        $idempotency->executeOnce($idempotencyKey, function () use ($luckyDrawService) {
            try {
                $luckyDrawService->allocateEntries($this->payment);
            } catch (\Exception $e) {
                Log::error("Failed to allocate lucky draw entries: " . $e->getMessage());
                throw $e; // Re-throw to mark job as failed
            }
        }, [
            'job_class' => self::class,
            'input_data' => [
                'payment_id' => $this->payment->id,
                'user_id' => $this->payment->user_id,
                'amount' => $this->payment->amount,
            ],
        ]);
    }
}