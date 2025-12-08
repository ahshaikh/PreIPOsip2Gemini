<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\BonusTransaction;
use App\Jobs\ProcessSuccessfulPaymentJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill Missing Bonuses Command
 *
 * This command finds all 'paid' payments that don't have corresponding bonuses
 * and processes them to award the bonuses retroactively.
 *
 * Use Case: Fix historical data where payments were completed before the
 * ProcessSuccessfulPaymentJob dispatch was added to PaymentController::verify()
 *
 * Usage:
 *   php artisan bonuses:backfill
 *   php artisan bonuses:backfill --dry-run
 *   php artisan bonuses:backfill --payment-id=123
 */
class BackfillMissingBonuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonuses:backfill
                            {--dry-run : Show what would be processed without actually processing}
                            {--payment-id= : Process a specific payment ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill missing bonuses for paid payments that were processed before the bonus job was added';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $specificPaymentId = $this->option('payment-id');

        $this->info('=== Backfill Missing Bonuses ===');
        $this->info('Started at: ' . now()->toDateTimeString());

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Build query for payments without bonuses
        $query = Payment::with(['subscription.plan', 'user'])
            ->where('status', 'paid')
            ->whereDoesntHave('bonuses') // Payments without any bonus transactions
            ->orderBy('paid_at', 'asc');

        // If specific payment ID provided
        if ($specificPaymentId) {
            $query->where('id', $specificPaymentId);
        }

        $missingBonusPayments = $query->get();

        if ($missingBonusPayments->isEmpty()) {
            $this->info('✅ No payments found that are missing bonuses!');
            return 0;
        }

        $this->info("Found {$missingBonusPayments->count()} payments missing bonuses:");
        $this->newLine();

        // Display table of affected payments
        $tableData = $missingBonusPayments->map(function ($payment) {
            return [
                'ID' => $payment->id,
                'User' => $payment->user->email ?? 'N/A',
                'Amount' => '₹' . number_format($payment->amount, 2),
                'Paid At' => $payment->paid_at?->format('Y-m-d H:i'),
                'Plan' => $payment->subscription?->plan?->name ?? 'N/A',
            ];
        })->toArray();

        $this->table(
            ['ID', 'User', 'Amount', 'Paid At', 'Plan'],
            $tableData
        );

        if ($isDryRun) {
            $this->newLine();
            $this->info('🔍 DRY RUN: Would process ' . $missingBonusPayments->count() . ' payments');
            $this->info('Run without --dry-run to actually process these payments');
            return 0;
        }

        // Confirm before processing
        if (!$this->confirm('Do you want to process these payments and award bonuses?', true)) {
            $this->warn('Operation cancelled');
            return 0;
        }

        $this->newLine();
        $this->info('Processing payments...');
        $progressBar = $this->output->createProgressBar($missingBonusPayments->count());
        $progressBar->start();

        $processed = 0;
        $failed = 0;
        $errors = [];

        foreach ($missingBonusPayments as $payment) {
            try {
                // Dispatch the job to process bonuses
                ProcessSuccessfulPaymentJob::dispatchSync($payment);
                $processed++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'payment_id' => $payment->id,
                    'user' => $payment->user->email ?? 'N/A',
                    'error' => $e->getMessage(),
                ];
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('=== Processing Complete ===');
        $this->info("✅ Successfully processed: {$processed}");

        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
            $this->newLine();
            $this->error('Failed Payments:');
            $this->table(
                ['Payment ID', 'User', 'Error'],
                $errors
            );
        }

        $this->newLine();
        $this->info('Finished at: ' . now()->toDateTimeString());

        return $failed > 0 ? 1 : 0;
    }
}
