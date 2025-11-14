<?php
// V-FINAL-1730-301 | V-FINAL-1730-365-Refactored | V-FINAL-1730-367 (Refactored to use Service)

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
     */
    public function handle(LuckyDrawService $luckyDrawService): void
    {
        try {
            $luckyDrawService->allocateEntries($this->payment);
        } catch (\Exception $e) {
            Log::error("Failed to allocate lucky draw entries: " . $e->getMessage());
        }
    }
}