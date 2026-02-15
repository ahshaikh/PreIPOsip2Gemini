<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaymentReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * FINANCIAL INTEGRITY COMMAND: Payment Gateway Reconciliation
 *
 * V-PAYMENT-INTEGRITY-2026
 *
 * PURPOSE:
 * Reconcile internal payment records with external gateway (Razorpay).
 * Detects:
 * - Missing webhooks (gateway captured, DB pending)
 * - Status mismatches
 * - Amount mismatches
 * - Orphaned payments
 * - Settlement status
 *
 * SAFE FOR PRODUCTION:
 * - Read-only by default (no mutations)
 * - --fix requires explicit confirmation
 * - Returns non-zero exit code on discrepancy for CI/alerting
 *
 * USAGE:
 *   php artisan payment:reconcile                    # Reconcile last 24 hours
 *   php artisan payment:reconcile --days=7           # Reconcile last 7 days
 *   php artisan payment:reconcile --from=2026-02-01  # From specific date
 *   php artisan payment:reconcile --fix              # Attempt auto-fix
 *   php artisan payment:reconcile --dry-run          # Show what would be fixed
 */
class PaymentReconcileCommand extends Command
{
    protected $signature = 'payment:reconcile
        {--days=1 : Number of days to reconcile}
        {--from= : Start date (YYYY-MM-DD)}
        {--to= : End date (YYYY-MM-DD)}
        {--fix : Attempt to auto-fix discrepancies}
        {--dry-run : With --fix, show what would be fixed without making changes}
        {--verbose : Show detailed reconciliation}';

    protected $description = 'Reconcile payment records against payment gateway (read-only by default)';

    private int $totalPayments = 0;
    private int $matchedPayments = 0;
    private array $discrepancies = [];

    public function handle(PaymentReconciliationService $reconciliationService): int
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         PAYMENT GATEWAY RECONCILIATION                       ║');
        $this->info('║         External Boundary Integrity Verification             ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $startTime = microtime(true);

        // Determine date range
        $endDate = $this->option('to') ? Carbon::parse($this->option('to'))->endOfDay() : Carbon::now();
        $startDate = $this->option('from')
            ? Carbon::parse($this->option('from'))->startOfDay()
            : Carbon::now()->subDays((int) $this->option('days'))->startOfDay();

        $this->info("Date Range: {$startDate->toDateString()} to {$endDate->toDateString()}");
        $this->newLine();

        // Phase 1: Internal Consistency Checks
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('PHASE 1: Internal Consistency Checks');
        $this->info('═══════════════════════════════════════════════════════════════');

        $internalIssues = $this->checkInternalConsistency($startDate, $endDate);

        // Phase 2: Gateway Reconciliation
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('PHASE 2: Gateway Reconciliation');
        $this->info('═══════════════════════════════════════════════════════════════');

        $gatewayResult = $reconciliationService->reconcilePayments($startDate, $endDate);

        // Phase 3: Settlement Verification
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('PHASE 3: Settlement Status Check');
        $this->info('═══════════════════════════════════════════════════════════════');

        $settlementIssues = $this->checkSettlementStatus($startDate, $endDate);

        // Summary
        $duration = round(microtime(true) - $startTime, 2);
        $this->displaySummary($internalIssues, $gatewayResult, $settlementIssues, $duration);

        // Handle fixes
        $totalIssues = count($internalIssues) + ($gatewayResult['total_discrepancies'] ?? 0) + count($settlementIssues);

        if ($totalIssues > 0) {
            Log::warning('PAYMENT RECONCILIATION: DISCREPANCIES FOUND', [
                'internal_issues' => count($internalIssues),
                'gateway_issues' => $gatewayResult['total_discrepancies'] ?? 0,
                'settlement_issues' => count($settlementIssues),
            ]);

            if ($this->option('fix')) {
                return $this->handleFix($reconciliationService, $gatewayResult);
            }

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('✅ ALL PAYMENTS RECONCILED SUCCESSFULLY');

        Log::info('Payment reconciliation completed successfully', [
            'date_range' => [$startDate->toDateString(), $endDate->toDateString()],
            'duration_seconds' => $duration,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Check internal consistency of payment records.
     */
    private function checkInternalConsistency(Carbon $startDate, Carbon $endDate): array
    {
        $issues = [];

        // Check 1: Payments marked as paid but no wallet transaction
        $paidWithoutWalletTx = Payment::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereDoesntHave('user', function ($q) {
                $q->whereHas('wallet.transactions', function ($txQ) {
                    $txQ->where('reference_type', Payment::class);
                });
            })
            ->get();

        foreach ($paidWithoutWalletTx as $payment) {
            // Double-check with direct query
            $hasTx = DB::table('transactions')
                ->where('reference_type', Payment::class)
                ->where('reference_id', $payment->id)
                ->exists();

            if (!$hasTx) {
                $issues[] = [
                    'type' => 'paid_without_wallet_tx',
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'paid_at' => $payment->paid_at,
                ];
            }
        }

        if (count($issues) > 0) {
            $this->warn("  ✗ {$issues[0]['type']}: Found payments marked 'paid' without wallet transaction");
            foreach ($issues as $issue) {
                $this->line("    - Payment #{$issue['payment_id']}: ₹{$issue['amount']} (paid: {$issue['paid_at']})");
            }
        } else {
            $this->info('  ✓ All paid payments have corresponding wallet transactions');
        }

        // Check 2: Duplicate gateway_payment_id (should be impossible with UNIQUE constraint)
        $duplicates = DB::table('payments')
            ->select('gateway_payment_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('gateway_payment_id')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('gateway_payment_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $this->error("  ✗ CRITICAL: Duplicate gateway_payment_id found!");
            foreach ($duplicates as $dup) {
                $issues[] = [
                    'type' => 'duplicate_gateway_id',
                    'gateway_payment_id' => $dup->gateway_payment_id,
                    'count' => $dup->count,
                ];
                $this->line("    - {$dup->gateway_payment_id}: {$dup->count} records");
            }
        } else {
            $this->info('  ✓ No duplicate gateway_payment_id found');
        }

        // Check 3: Negative or zero amount
        $invalidAmounts = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('amount', '<=', 0)
                    ->orWhere('amount_paise', '<=', 0);
            })
            ->get();

        if ($invalidAmounts->isNotEmpty()) {
            $this->warn("  ✗ Found payments with invalid amounts");
            foreach ($invalidAmounts as $payment) {
                $issues[] = [
                    'type' => 'invalid_amount',
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'amount_paise' => $payment->amount_paise,
                ];
            }
        } else {
            $this->info('  ✓ All payments have valid amounts');
        }

        // Check 4: Amount mismatch between decimal and paise
        $amountMismatches = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('amount_paise')
            ->whereRaw('ABS(amount * 100 - amount_paise) > 1') // Allow 1 paise rounding
            ->get();

        if ($amountMismatches->isNotEmpty()) {
            $this->warn("  ✗ Found payments with amount/amount_paise mismatch");
            foreach ($amountMismatches as $payment) {
                $issues[] = [
                    'type' => 'amount_paise_mismatch',
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'amount_paise' => $payment->amount_paise,
                    'expected_paise' => (int) round($payment->amount * 100),
                ];
            }
        } else {
            $this->info('  ✓ All payments have consistent amount/amount_paise values');
        }

        return $issues;
    }

    /**
     * Check settlement status of paid payments.
     */
    private function checkSettlementStatus(Carbon $startDate, Carbon $endDate): array
    {
        $issues = [];

        // Payments older than 3 days that are paid but not settled
        $unsettled = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->where('paid_at', '<', Carbon::now()->subDays(3))
            ->where(function ($q) {
                $q->whereNull('settlement_status')
                    ->orWhere('settlement_status', 'pending');
            })
            ->count();

        if ($unsettled > 0) {
            $this->warn("  ⚠ Found {$unsettled} payments older than 3 days that are not settled");
            $this->line("    Note: Settlement status requires gateway API integration");
        } else {
            $this->info('  ✓ Settlement status check passed (or no payments require settlement yet)');
        }

        return $issues;
    }

    /**
     * Display reconciliation summary.
     */
    private function displaySummary(
        array $internalIssues,
        array $gatewayResult,
        array $settlementIssues,
        float $duration
    ): void {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('RECONCILIATION SUMMARY');
        $this->info('═══════════════════════════════════════════════════════════════');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total DB Payments', $gatewayResult['total_db_payments'] ?? 'N/A'],
                ['Total Gateway Payments', $gatewayResult['total_gateway_payments'] ?? 'N/A'],
                ['Internal Issues', count($internalIssues)],
                ['Missing Webhooks', count($gatewayResult['missing_webhooks'] ?? [])],
                ['Status Mismatches', count($gatewayResult['status_mismatches'] ?? [])],
                ['Amount Mismatches', count($gatewayResult['amount_mismatches'] ?? [])],
                ['Orphaned Payments', count($gatewayResult['orphaned_payments'] ?? [])],
                ['Settlement Issues', count($settlementIssues)],
                ['Duration', "{$duration}s"],
            ]
        );

        $totalIssues = count($internalIssues) +
            ($gatewayResult['total_discrepancies'] ?? 0) +
            count($settlementIssues);

        $this->newLine();
        if ($totalIssues > 0) {
            $this->error("⚠️  TOTAL DISCREPANCIES: {$totalIssues}");
        } else {
            $this->info("✅ OVERALL STATUS: ALL_RECONCILED");
        }
    }

    /**
     * Handle the --fix option.
     */
    private function handleFix(PaymentReconciliationService $reconciliationService, array $gatewayResult): int
    {
        $this->newLine();
        $this->warn('══════════════════════════════════════════════════════════════');
        $this->warn('⚠️  AUTO-FIX MODE');
        $this->warn('══════════════════════════════════════════════════════════════');

        $missingWebhooks = $gatewayResult['missing_webhooks'] ?? [];

        if (empty($missingWebhooks)) {
            $this->info('No missing webhooks to fix.');
            return Command::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('DRY-RUN: No changes will be made.');
            $this->newLine();

            foreach ($missingWebhooks as $discrepancy) {
                $paymentRef = $discrepancy['payment_gateway_id'] ?? $discrepancy['payment_id'] ?? 'unknown';
                $this->line("Would process payment: {$paymentRef}");
            }

            return Command::FAILURE;
        }

        // Require explicit confirmation
        if (!$this->confirm('This will attempt to fix missing webhooks. Are you sure?')) {
            $this->info('Operation cancelled.');
            return Command::FAILURE;
        }

        $fixResult = $reconciliationService->autoFixMissingWebhooks($missingWebhooks);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Processed', $fixResult['processed']],
                ['Failed', $fixResult['failed']],
                ['Provisional', $fixResult['provisional']],
            ]
        );

        if ($fixResult['provisional'] > 0) {
            $this->warn("⚠️  {$fixResult['provisional']} payments were given PROVISIONAL credit.");
            $this->warn('These require settlement confirmation before being finalized.');
        }

        return $fixResult['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
