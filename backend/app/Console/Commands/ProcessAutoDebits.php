<?php
// V-FINAL-1730-204

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessSuccessfulPaymentJob;

class ProcessAutoDebits extends Command
{
    protected $signature = 'app:process-auto-debits';
    protected $description = 'Process automated SIP payments for due subscriptions.';

    public function handle()
    {
        $this->info('Starting auto-debit process...');

        // 1. Find active subscriptions where next_payment_date is today (or past)
        $dueSubscriptions = Subscription::where('status', 'active')
            ->where('next_payment_date', '<=', now())
            ->get();

        $this->info("Found {$dueSubscriptions->count()} subscriptions due for payment.");

        foreach ($dueSubscriptions as $sub) {
            // Check if a pending payment already exists for this cycle to avoid duplicates
            $exists = Payment::where('subscription_id', $sub->id)
                ->where('status', 'pending')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->exists();

            if ($exists) {
                $this->warn("Pending payment already exists for Subscription #{$sub->id}. Skipping.");
                continue;
            }

            // 2. Create the Payment Record
            $payment = Payment::create([
                'user_id' => $sub->user_id,
                'subscription_id' => $sub->id,
                'amount' => $sub->plan->monthly_amount,
                'status' => 'pending',
                'gateway' => 'auto_debit',
                'is_on_time' => true, // Auto-debits are on time by definition
            ]);

            // 3. Attempt Charge (Simulation)
            // In a real app, this is where you'd call Razorpay's recurring API
            // using $sub->mandate_id (if we had stored it).
            try {
                // SIMULATION: We'll assume 80% success rate for testing logic
                // Replace this with: $gateway->charge($sub->mandate_id, $amount);
                $success = true; 

                if ($success) {
                    $payment->update([
                        'status' => 'paid', 
                        'paid_at' => now(),
                        'gateway_payment_id' => 'AUTO-' . \Illuminate\Support\Str::random(10)
                    ]);
                    
                    // Trigger the core logic (Bonus, Allocation, etc.)
                    ProcessSuccessfulPaymentJob::dispatch($payment);
                    
                    // Advance the date
                    $sub->update(['next_payment_date' => $sub->next_payment_date->addMonth()]);
                    
                    $this->info("Successfully charged Subscription #{$sub->id}");
                } else {
                    // Handle soft fail (insufficient funds, etc.)
                    $payment->update(['status' => 'failed']);
                    // Notify User...
                }

            } catch (\Exception $e) {
                Log::error("Auto-debit failed for Sub #{$sub->id}: " . $e->getMessage());
                $payment->update(['status' => 'failed']);
            }
        }
        
        return 0;
    }
}