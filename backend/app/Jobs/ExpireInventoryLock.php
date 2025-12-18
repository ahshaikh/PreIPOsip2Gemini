<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-INVENTORY-RECOVERY | V-ATOMIC-RELEASE
 * * ARCHITECTURAL FIX: 
 * Implements a "Compensating Transaction" pattern. 
 * If a payment session expires, units are returned to the deal inventory atomically.
 */

namespace App\Jobs;

use App\Models\UserInvestment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireInventoryLock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job to release expired soft-locks.
     */
    public function handle()
    {
        // [AUDIT REQUIREMENT]: 10-Minute Expiration Threshold
        $expiredInvestments = UserInvestment::where('status', 'pending_payment')
            ->where('created_at', '<', now()->subMinutes(10))
            ->with('deal')
            ->get();

        if ($expiredInvestments->isEmpty()) {
            return;
        }

        foreach ($expiredInvestments as $investment) {
            $this->releaseUnits($investment);
        }
    }

    /**
     * Atomically return units to the deal and cleanup the pending record.
     */
    protected function releaseUnits(UserInvestment $investment)
    {
        DB::transaction(function () use ($investment) {
            // [ANTI-PATTERN FIX]: Ensure deal is locked before incrementing units
            $deal = $investment->deal()->lockForUpdate()->first();
            
            if ($deal) {
                $deal->increment('available_shares', $investment->units);
                
                Log::info("INVENTORY_RELEASED: Deal #{$deal->id} regained {$investment->units} units from User #{$investment->user_id}");
            }

            // Delete the 'pending_payment' placeholder
            $investment->delete();
        });
    }
}