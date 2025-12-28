<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LedgerReconciliationService;
use App\Services\PaymentReconciliationService;
use Carbon\Carbon;

/**
 * ReconcileLedgers Command - Scheduled Reconciliation
 *
 * [E.17 + E.18]: Automated reconciliation job
 *
 * USAGE:
 * - php artisan reconcile:ledgers
 * - php artisan reconcile:ledgers --type=wallets
 * - php artisan reconcile:ledgers --type=payments --days=7
 *
 * SCHEDULE:
 * - Run daily at 2 AM (add to Kernel.php)
 * - Run hourly for payment reconciliation
 */
class ReconcileLedgers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reconcile:ledgers
                            {--type=all : Type of reconciliation (all, wallets, payments, system)}
                            {--days=1 : Number of days to reconcile for payment reconciliation}
                            {--auto-fix : Automatically fix discrepancies (DANGEROUS)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile ledgers and detect financial discrepancies';

    private LedgerReconciliationService $ledgerService;
    private PaymentReconciliationService $paymentService;

    public function __construct(
        LedgerReconciliationService $ledgerService,
        PaymentReconciliationService $paymentService
    ) {
        parent::__construct();
        $this->ledgerService = $ledgerService;
        $this->paymentService = $paymentService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $autoFix = $this->option('auto-fix');

        $this->info("🔍 Starting ledger reconciliation: {$type}");
        $this->newLine();

        $results = [];

        // Execute reconciliation based on type
        switch ($type) {
            case 'wallets':
                $results['wallets'] = $this->reconcileWallets($autoFix);
                break;

            case 'payments':
                $results['payments'] = $this->reconcilePayments($autoFix);
                break;

            case 'system':
                $results['system'] = $this->reconcileSystemBalance();
                break;

            case 'all':
            default:
                $results['wallets'] = $this->reconcileWallets($autoFix);
                $results['system'] = $this->reconcileSystemBalance();
                $results['payments'] = $this->reconcilePayments($autoFix);
                break;
        }

        // Display summary
        $this->newLine();
        $this->displaySummary($results);

        return Command::SUCCESS;
    }

    /**
     * Reconcile wallet balances
     */
    private function reconcileWallets(bool $autoFix): array
    {
        $this->info("📊 Reconciling wallet balances...");

        $result = $this->ledgerService->reconcileAllWallets();

        if ($result['discrepancies_count'] === 0) {
            $this->info("✅ All {$result['total_wallets']} wallets are balanced!");
        } else {
            $this->warn("⚠️  Found {$result['discrepancies_count']} wallet discrepancies:");

            $this->table(
                ['Wallet ID', 'User ID', 'Discrepancy (₹)'],
                array_map(function ($d) {
                    return [
                        $d['wallet_id'],
                        $d['user_id'],
                        number_format($d['discrepancy_rupees'], 2),
                    ];
                }, $result['discrepancies'])
            );

            if ($autoFix) {
                $this->warn("🔧 Auto-fixing wallet discrepancies...");
                $this->warn("⚠️  THIS IS DANGEROUS - Use only in emergency!");

                if ($this->confirm('Are you ABSOLUTELY sure you want to auto-fix?', false)) {
                    foreach ($result['discrepancies'] as $discrepancy) {
                        $wallet = \App\Models\Wallet::find($discrepancy['wallet_id']);
                        $this->ledgerService->autoFixWalletBalance(
                            $wallet,
                            "Auto-fix via reconcile:ledgers command"
                        );
                        $this->info("  ✓ Fixed wallet #{$discrepancy['wallet_id']}");
                    }
                } else {
                    $this->info("Auto-fix cancelled.");
                }
            }
        }

        return $result;
    }

    /**
     * Reconcile system balance
     */
    private function reconcileSystemBalance(): array
    {
        $this->info("📊 Reconciling system-wide balance...");

        $result = $this->ledgerService->reconcileSystemBalance();

        $this->table(
            ['Metric', 'Value (₹)'],
            [
                ['Total Credits', number_format($result['total_credits_rupees'], 2)],
                ['Total Debits', number_format($result['total_debits_rupees'], 2)],
                ['System Balance', number_format($result['system_balance_rupees'], 2)],
            ]
        );

        if ($result['is_balanced']) {
            $this->info("✅ System balance is valid!");
        } else {
            $this->error("❌ System balance violation! More debits than credits.");
        }

        return $result;
    }

    /**
     * Reconcile external payments
     */
    private function reconcilePayments(bool $autoFix): array
    {
        $this->info("📊 Reconciling external payments...");

        $days = (int) $this->option('days');
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays($days);

        $this->info("Date range: {$startDate->toDateString()} to {$endDate->toDateString()}");

        $result = $this->paymentService->reconcilePayments($startDate, $endDate);

        if ($result['total_discrepancies'] === 0) {
            $this->info("✅ All payments reconciled!");
        } else {
            $this->warn("⚠️  Found {$result['total_discrepancies']} payment discrepancies:");

            if (!empty($result['missing_webhooks'])) {
                $this->warn("\n  Missing Webhooks: " . count($result['missing_webhooks']));
                $this->table(
                    ['Payment ID', 'Gateway ID', 'Issue'],
                    array_map(function ($d) {
                        return [
                            $d['payment_id'] ?? 'N/A',
                            $d['payment_gateway_id'],
                            $d['issue'],
                        ];
                    }, $result['missing_webhooks'])
                );

                if ($autoFix && $this->confirm('Auto-fix missing webhooks?', false)) {
                    $fixResult = $this->paymentService->autoFixMissingWebhooks($result['missing_webhooks']);
                    $this->info("  ✓ Fixed {$fixResult['processed']} payments");
                    if ($fixResult['failed'] > 0) {
                        $this->error("  ✗ Failed to fix {$fixResult['failed']} payments");
                    }
                }
            }

            if (!empty($result['status_mismatches'])) {
                $this->warn("\n  Status Mismatches: " . count($result['status_mismatches']));
            }

            if (!empty($result['amount_mismatches'])) {
                $this->warn("\n  Amount Mismatches: " . count($result['amount_mismatches']));
            }

            if (!empty($result['orphaned_payments'])) {
                $this->warn("\n  Orphaned Payments: " . count($result['orphaned_payments']));
            }
        }

        return $result;
    }

    /**
     * Display reconciliation summary
     */
    private function displaySummary(array $results): void
    {
        $this->info("═══════════════════════════════════════");
        $this->info("       RECONCILIATION SUMMARY");
        $this->info("═══════════════════════════════════════");

        foreach ($results as $type => $result) {
            $status = $this->getStatusEmoji($result);
            $this->line("  {$status} " . ucfirst($type) . " Reconciliation");
        }

        $this->newLine();
        $this->info("Reconciliation completed at: " . now()->toDateTimeString());
    }

    /**
     * Get status emoji based on result
     */
    private function getStatusEmoji(array $result): string
    {
        if (isset($result['overall_status'])) {
            return $result['overall_status'] === 'ALL_BALANCED' ||
                   $result['overall_status'] === 'ALL_RECONCILED' ? '✅' : '⚠️';
        }

        if (isset($result['is_balanced'])) {
            return $result['is_balanced'] ? '✅' : '⚠️';
        }

        if (isset($result['discrepancies_count'])) {
            return $result['discrepancies_count'] === 0 ? '✅' : '⚠️';
        }

        return '❓';
    }
}
