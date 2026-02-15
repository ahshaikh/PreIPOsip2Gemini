<?php

namespace App\Console\Commands;

use App\Models\Wallet;
use App\Models\LedgerAccount;
use App\Models\LedgerLine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FINANCIAL INTEGRITY COMMAND: Wallet Ledger Reconciliation
 *
 * PURPOSE:
 * Verify that wallet balances match double-entry ledger records.
 * Detects any discrepancies that indicate:
 * - Direct balance mutations bypassing WalletService
 * - Incomplete transactions (partial commits)
 * - Data corruption
 *
 * SAFE FOR PRODUCTION:
 * - Read-only operation (no mutations)
 * - Can be run via scheduler for continuous monitoring
 * - Returns non-zero exit code on discrepancy for CI/alerting
 *
 * USAGE:
 *   php artisan wallet:reconcile              # Check all wallets
 *   php artisan wallet:reconcile --user=123   # Check specific user
 *   php artisan wallet:reconcile --fix        # DANGEROUS: Auto-fix (requires senior_admin)
 */
class WalletReconcileCommand extends Command
{
    protected $signature = 'wallet:reconcile
        {--user= : Reconcile specific user ID only}
        {--verbose : Show detailed reconciliation for each wallet}
        {--fix : DANGEROUS: Attempt to auto-correct discrepancies via adjustment entries}
        {--dry-run : With --fix, show what would be fixed without making changes}
        {--tolerance=0 : Allow discrepancy up to N paise (default: exact match)}';

    protected $description = 'Reconcile wallet balances against double-entry ledger (read-only by default)';

    private int $totalWallets = 0;
    private int $matchedWallets = 0;
    private int $discrepancies = 0;
    private array $discrepancyDetails = [];

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         WALLET LEDGER RECONCILIATION                         ║');
        $this->info('║         Financial Integrity Verification                     ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $startTime = microtime(true);

        // Get USER_WALLET_LIABILITY account
        $walletLiabilityAccount = LedgerAccount::where('code', 'USER_WALLET_LIABILITY')->first();

        if (!$walletLiabilityAccount) {
            $this->error('CRITICAL: USER_WALLET_LIABILITY ledger account not found!');
            $this->error('Double-entry ledger may not be initialized.');
            return Command::FAILURE;
        }

        // Build wallet query
        $query = Wallet::with('user:id,email,username');

        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        $tolerance = (int) $this->option('tolerance');
        $this->info("Tolerance: {$tolerance} paise");
        $this->newLine();

        // Process wallets in chunks to avoid memory issues
        $query->chunkById(100, function ($wallets) use ($walletLiabilityAccount, $tolerance) {
            foreach ($wallets as $wallet) {
                $this->reconcileWallet($wallet, $walletLiabilityAccount, $tolerance);
            }
        });

        // Summary
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('RECONCILIATION SUMMARY');
        $this->info('═══════════════════════════════════════════════════════════════');

        $duration = round(microtime(true) - $startTime, 2);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Wallets Checked', $this->totalWallets],
                ['Wallets Matched', $this->matchedWallets],
                ['Discrepancies Found', $this->discrepancies],
                ['Duration', "{$duration}s"],
            ]
        );

        if ($this->discrepancies > 0) {
            $this->newLine();
            $this->error('⚠️  DISCREPANCIES DETECTED');
            $this->newLine();

            // Log critical error for monitoring
            Log::critical('WALLET RECONCILIATION FAILED', [
                'total_wallets' => $this->totalWallets,
                'discrepancies' => $this->discrepancies,
                'details' => $this->discrepancyDetails,
            ]);

            // Show discrepancy details
            $this->table(
                ['User ID', 'Email', 'Wallet Balance', 'Ledger Balance', 'Difference'],
                collect($this->discrepancyDetails)->map(fn ($d) => [
                    $d['user_id'],
                    $d['email'],
                    '₹' . number_format($d['wallet_balance_paise'] / 100, 2),
                    '₹' . number_format($d['ledger_balance_paise'] / 100, 2),
                    '₹' . number_format($d['difference_paise'] / 100, 2),
                ])->toArray()
            );

            // Handle --fix option
            if ($this->option('fix')) {
                return $this->handleFix();
            }

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('✅ ALL WALLETS RECONCILED SUCCESSFULLY');

        Log::info('Wallet reconciliation completed successfully', [
            'total_wallets' => $this->totalWallets,
            'duration_seconds' => $duration,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Reconcile a single wallet against ledger.
     */
    private function reconcileWallet(Wallet $wallet, LedgerAccount $liabilityAccount, int $tolerance): void
    {
        $this->totalWallets++;

        $walletBalancePaise = $wallet->balance_paise;

        // Calculate ledger balance for this user
        // USER_WALLET_LIABILITY is a liability account (credit normal)
        // Credits increase the balance (deposits), Debits decrease it (withdrawals)
        $ledgerBalance = $this->calculateLedgerBalanceForUser($wallet->user_id, $liabilityAccount->id);

        $difference = abs($walletBalancePaise - $ledgerBalance);

        if ($difference <= $tolerance) {
            $this->matchedWallets++;

            if ($this->option('verbose')) {
                $this->line("  ✓ User #{$wallet->user_id}: ₹" . number_format($walletBalancePaise / 100, 2));
            }
        } else {
            $this->discrepancies++;

            $detail = [
                'user_id' => $wallet->user_id,
                'email' => $wallet->user?->email ?? 'N/A',
                'wallet_balance_paise' => $walletBalancePaise,
                'ledger_balance_paise' => $ledgerBalance,
                'difference_paise' => $walletBalancePaise - $ledgerBalance,
            ];

            $this->discrepancyDetails[] = $detail;

            $this->warn("  ✗ User #{$wallet->user_id}: Wallet ₹" .
                number_format($walletBalancePaise / 100, 2) .
                " ≠ Ledger ₹" . number_format($ledgerBalance / 100, 2) .
                " (Δ ₹" . number_format(($walletBalancePaise - $ledgerBalance) / 100, 2) . ")");
        }
    }

    /**
     * Calculate user's wallet balance from DOUBLE-ENTRY LEDGER.
     *
     * ⚠️ CRITICAL ACCOUNTING PRINCIPLE:
     * Ledger is the SINGLE SOURCE OF TRUTH.
     * Transactions table is NOT authoritative.
     *
     * For USER_WALLET_LIABILITY (credit-normal liability account):
     * - CREDIT increases balance (deposits, bonus credits)
     * - DEBIT decreases balance (withdrawals, investments)
     *
     * Balance = SUM(credits) - SUM(debits) for USER_WALLET_LIABILITY lines
     * linked to this user via ledger_entries.reference_type/reference_id
     *
     * @param int $userId User ID to calculate balance for
     * @param int $liabilityAccountId USER_WALLET_LIABILITY account ID
     * @return int Balance in paise
     */
    private function calculateLedgerBalanceForUser(int $userId, int $liabilityAccountId): int
    {
        // Get all ledger entries related to this user
        // Entries reference user operations via reference_type + reference_id
        // We need to find all entries that affected this user's wallet

        // Step 1: Find all ledger entries linked to this user's transactions
        // Reference types that link to user: user_deposit, user_investment,
        // bonus_credit, withdrawal, refund, share_sale

        $userReferenceTypes = [
            'user_deposit',
            'user_investment',
            'bonus_credit',
            'bonus_to_wallet',
            'withdrawal',
            'refund',
            'share_sale',
            'tds_deduction',
        ];

        // Get ledger entry IDs that reference this user's operations
        // These entries will have lines against USER_WALLET_LIABILITY
        $entryIds = DB::table('ledger_entries')
            ->where(function ($query) use ($userId, $userReferenceTypes) {
                // Direct user reference via Payment, Withdrawal, etc.
                $query->whereIn('reference_type', $userReferenceTypes)
                      ->whereIn('reference_id', function ($subquery) use ($userId) {
                          // Get IDs from user's transactions
                          $subquery->select('id')
                                   ->from('transactions')
                                   ->where('user_id', $userId);
                      });
            })
            ->orWhere(function ($query) use ($userId) {
                // Also check entries referencing Payment model directly
                $query->where('reference_type', 'payment')
                      ->whereIn('reference_id', function ($subquery) use ($userId) {
                          $subquery->select('id')
                                   ->from('payments')
                                   ->where('user_id', $userId);
                      });
            })
            ->orWhere(function ($query) use ($userId) {
                // Check entries referencing Withdrawal model
                $query->where('reference_type', 'withdrawal')
                      ->whereIn('reference_id', function ($subquery) use ($userId) {
                          $subquery->select('id')
                                   ->from('withdrawals')
                                   ->where('user_id', $userId);
                      });
            })
            ->pluck('id');

        if ($entryIds->isEmpty()) {
            return 0;
        }

        // Step 2: Sum credits and debits for USER_WALLET_LIABILITY from these entries
        $ledgerSum = DB::table('ledger_lines')
            ->whereIn('ledger_entry_id', $entryIds)
            ->where('ledger_account_id', $liabilityAccountId)
            ->selectRaw("
                SUM(CASE WHEN direction = 'CREDIT' THEN amount_paise ELSE 0 END) as credits,
                SUM(CASE WHEN direction = 'DEBIT' THEN amount_paise ELSE 0 END) as debits
            ")
            ->first();

        $credits = (int) ($ledgerSum->credits ?? 0);
        $debits = (int) ($ledgerSum->debits ?? 0);

        // USER_WALLET_LIABILITY is credit-normal (liability account)
        // Credits increase what we owe user (their balance goes up)
        // Debits decrease what we owe user (their balance goes down)
        return $credits - $debits;
    }

    /**
     * Handle the --fix option (DANGEROUS).
     */
    private function handleFix(): int
    {
        $this->newLine();
        $this->warn('══════════════════════════════════════════════════════════════');
        $this->warn('⚠️  AUTO-FIX MODE');
        $this->warn('══════════════════════════════════════════════════════════════');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('DRY-RUN: No changes will be made.');
            $this->newLine();

            foreach ($this->discrepancyDetails as $detail) {
                $adjustment = $detail['ledger_balance_paise'] - $detail['wallet_balance_paise'];
                $this->line("Would create adjustment for User #{$detail['user_id']}: " .
                    ($adjustment > 0 ? '+' : '') . "₹" . number_format($adjustment / 100, 2));
            }

            return Command::FAILURE;
        }

        $this->error('AUTO-FIX WITHOUT DRY-RUN IS DISABLED FOR SAFETY.');
        $this->error('Manual reconciliation adjustments must be made through:');
        $this->error('  1. Admin panel → Wallet Adjustments');
        $this->error('  2. Direct database intervention by DBA with audit trail');
        $this->newLine();
        $this->info('Use --dry-run to see what adjustments would be needed.');

        return Command::FAILURE;
    }
}
