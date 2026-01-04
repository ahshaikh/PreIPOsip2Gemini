<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Transaction;
use App\Jobs\ProcessSuccessfulPaymentJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfill Missing Wallet Credits
 *
 * Purpose: Fix payments that were marked as 'paid' but never credited to wallet
 * Cause: Queue worker not running, enum errors, or compliance gate issues
 *
 * Usage: php artisan payments:backfill-wallet-credits
 */
class BackfillMissingWalletCredits extends Command
{
    protected $signature = 'payments:backfill-wallet-credits
                            {--dry-run : Show what would be fixed without actually fixing}
                            {--payment= : Fix specific payment ID only}';

    protected $description = 'Backfill wallet credits for payments that were approved but never credited';

    public function handle()
    {
        $this->info('🔍 Scanning for payments with missing wallet credits...');

        $query = Payment::where('status', 'paid')
            ->whereNotNull('paid_at')
            ->with(['user', 'subscription']);

        // Filter by specific payment if provided
        if ($paymentId = $this->option('payment')) {
            $query->where('id', $paymentId);
        }

        $payments = $query->get();
        $this->info("Found {$payments->count()} paid payments to check");

        $fixed = 0;
        $alreadyProcessed = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($payments as $payment) {
            // Check if wallet transaction already exists for this payment
            $existingTransaction = Transaction::where('reference_type', 'App\\Models\\Payment')
                ->where('reference_id', $payment->id)
                ->where('type', 'deposit')  // TransactionType::DEPOSIT->value
                ->exists();

            if ($existingTransaction) {
                $alreadyProcessed++;
                $this->line("  ✓ Payment #{$payment->id} - Already processed");
                continue;
            }

            // Check if user exists
            if (!$payment->user) {
                $this->warn("  ⚠ Payment #{$payment->id} - User not found, skipping");
                $skipped++;
                continue;
            }

            // Check if user KYC is verified
            if ($payment->user->kyc_status !== 'verified') {
                $this->warn("  ⚠ Payment #{$payment->id} - User KYC not verified (status: {$payment->user->kyc_status}), skipping");
                $skipped++;
                continue;
            }

            // Found a payment that needs wallet credit
            $this->warn("  🔧 Payment #{$payment->id} - Missing wallet credit (₹{$payment->amount})");

            if ($this->option('dry-run')) {
                $this->line("     [DRY RUN] Would credit ₹{$payment->amount} to user {$payment->user->email}");
                $fixed++;
                continue;
            }

            // Clear any failed idempotency records to allow retry
            $idempotencyKey = "payment_processing:{$payment->id}";
            DB::table('job_executions')
                ->where('idempotency_key', $idempotencyKey)
                ->where('status', 'failed')
                ->delete();

            // Actually process the payment
            try {
                ProcessSuccessfulPaymentJob::dispatchSync($payment);

                // Verify wallet was credited
                $transactionCreated = Transaction::where('reference_type', 'App\\Models\\Payment')
                    ->where('reference_id', $payment->id)
                    ->where('type', 'deposit')
                    ->exists();

                if ($transactionCreated) {
                    $this->info("     ✅ Successfully credited ₹{$payment->amount} to {$payment->user->email}");
                    $fixed++;

                    Log::info("BACKFILL: Wallet credit successful", [
                        'payment_id' => $payment->id,
                        'user_id' => $payment->user_id,
                        'amount' => $payment->amount,
                    ]);
                } else {
                    $this->error("     ❌ Transaction not created after job execution");
                    $errors++;
                }

            } catch (\Exception $e) {
                $this->error("     ❌ Error: {$e->getMessage()}");
                $errors++;

                Log::error("BACKFILL: Wallet credit failed", [
                    'payment_id' => $payment->id,
                    'user_id' => $payment->user_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Summary
        $this->newLine();
        $this->info('📊 Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Already Processed', $alreadyProcessed],
                ['Fixed', $fixed],
                ['Errors', $errors],
                ['Skipped (KYC)', $skipped],
                ['Total Checked', $payments->count()],
            ]
        );

        if ($this->option('dry-run')) {
            $this->warn('This was a DRY RUN. No changes were made.');
            $this->info('Remove --dry-run flag to actually fix the payments.');
        }

        return $errors > 0 ? 1 : 0;
    }
}
