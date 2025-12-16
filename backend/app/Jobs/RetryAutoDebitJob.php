<?php
// V-FINAL-1730-306 | V-AUDIT-MODULE7-002 (Removed Simulation Code & Duplicate Logic)

namespace App\Jobs;

use App\Models\Payment;
use App\Services\AutoDebitService; // V-AUDIT-MODULE7-002: Delegate to service instead of duplicating logic
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * V-AUDIT-MODULE7-002 (CRITICAL): Removed dangerous simulation code.
 *
 * CRITICAL FIX:
 * - Removed rand() simulation that was randomly marking payments as success/failure
 * - Removed duplicate business logic (DRY violation)
 * - Now delegates to AutoDebitService::processRetry() for centralized retry logic
 *
 * This job is dispatched by AutoDebitService when a payment fails and needs to be retried.
 */
class RetryAutoDebitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payment $payment)
    {
    }

    /**
     * Handle the job by delegating to AutoDebitService.
     *
     * V-AUDIT-MODULE7-002: Simplified to single responsibility - job scheduling.
     * All business logic is in AutoDebitService for better testability and reusability.
     */
    public function handle(AutoDebitService $service): void
    {
        $payment = $this->payment;

        if ($payment->status === 'paid') {
            Log::info("Payment #{$payment->id} already paid. Skipping retry.");
            return; // Already paid, stop.
        }

        Log::info("Attempting retry #{$payment->retry_count} for Payment #{$payment->id}");

        // V-AUDIT-MODULE7-002: Delegate to service instead of duplicating logic
        // This ensures consistent behavior between initial attempt and retries
        $service->processRetry($payment);
    }
}
