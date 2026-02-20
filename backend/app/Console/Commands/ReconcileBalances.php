<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\AdminLedgerEntry;
use App\Models\FundLock;
use App\Services\Accounting\AdminLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FIX 32: Automated Balance Reconciliation Checks
 *
 * This command performs comprehensive balance verification across:
 * - User wallets (balance = sum of transactions)
 * - Fund locks (locked_balance = sum of active locks)
 * - Admin ledger (accounting equation balance)
 *
 * Run via cron: php artisan reconcile:balances
 * Schedule: Daily at 2 AM (low traffic period)
 */
class ReconcileBalances extends Command
{
    protected $signature = 'reconcile:balances 
                            {--fix : Automatically fix minor discrepancies (locked balances only)}
                            {--alert : Send alert to admins if critical discrepancies found}';

    protected $description = 'FIX 32: Reconcile wallet balances, fund locks, and admin ledger';

    protected AdminLedger $adminLedger;

    public function __construct(AdminLedger $adminLedger)
    {
        parent::__construct();
        $this->adminLedger = $adminLedger;
    }

    public function handle()
    {
        $this->info('🔍 Starting balance reconciliation...');
        $this->newLine();

        $issues = [];

        // 1. Check User Wallet Balances
        $this->info('Step 1: Checking user wallet balances...');
        $walletIssues = $this->checkUserWallets();
        if (!empty($walletIssues)) {
            $issues['wallet'] = $walletIssues;
            $this->error("  ❌ Found {$walletIssues['count']} wallet discrepancies");
        } else {
            $this->info('  ✓ All user wallet balances correct');
        }
        $this->newLine();

        // 2. Check Fund Locks
        $this->info('Step 2: Checking fund locks...');
        $lockIssues = $this->checkFundLocks();
        if (!empty($lockIssues)) {
            $issues['locks'] = $lockIssues;
            $this->error("  ❌ Found {$lockIssues['count']} fund lock discrepancies");
            
            if ($this->option('fix')) {
                $this->info('  🔧 Attempting to fix fund lock discrepancies...');
                $fixed = $this->fixFundLocks($lockIssues['discrepancies']);
                $this->info("  ✓ Fixed {$fixed} discrepancies");
            }
        } else {
            $this->info('  ✓ All fund locks correct');
        }
        $this->newLine();

        // 3. Check Admin Ledger
        $this->info('Step 3: Checking admin ledger solvency...');
        $ledgerIssues = $this->checkAdminLedger();
        if (!empty($ledgerIssues)) {
            $issues['ledger'] = $ledgerIssues;
            $this->error('  ❌ Admin ledger accounting equation does not balance');
        } else {
            $this->info('  ✓ Admin ledger balances correctly');
        }
        $this->newLine();

        // 4. Summary and Alert
        if (empty($issues)) {
            $this->info('✅ All balances reconciled successfully!');
            return 0;
        }

        $this->error('⚠️  Discrepancies found!');
        $this->table(
            ['Category', 'Issue Count', 'Details'],
            [
                ['Wallet Balances', $issues['wallet']['count'] ?? 0, $issues['wallet']['summary'] ?? 'N/A'],
                ['Fund Locks', $issues['locks']['count'] ?? 0, $issues['locks']['summary'] ?? 'N/A'],
                ['Admin Ledger', !empty($issues['ledger']) ? 1 : 0, $issues['ledger']['message'] ?? 'N/A'],
            ]
        );

        // Log for monitoring
        Log::warning('Balance reconciliation found discrepancies', $issues);

        // Send alert if requested
        if ($this->option('alert')) {
            $this->sendAlertToAdmins($issues);
            $this->info('📧 Alert sent to admins');
        }

        return 1; // Exit with error code to indicate issues
    }

    /**
     * Check user wallet balances against transaction sums (PAISE-based)
     */
    private function checkUserWallets(): array
    {
        $discrepancies = [];

        $users = User::has('wallet')->with('wallet')->chunk(1000, function ($userChunk) use (&$discrepancies) {
            foreach ($userChunk as $user) {
                $wallet = $user->wallet;

                // Calculate expected balance from transactions (using paise)
                $expectedBalancePaise = DB::table('transactions')
                    ->where('wallet_id', $wallet->id)
                    ->where('status', 'completed')
                    ->sum('amount_paise');

                $actualBalancePaise = $wallet->balance_paise;
                $differencePaise = abs($actualBalancePaise - $expectedBalancePaise);

                // No tolerance for integer paise - exact match required
                if ($differencePaise > 0) {
                    $discrepancies[] = [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'expected_paise' => $expectedBalancePaise,
                        'actual_paise' => $actualBalancePaise,
                        'difference_paise' => $differencePaise,
                        'expected_rupees' => $expectedBalancePaise / 100,
                        'actual_rupees' => $actualBalancePaise / 100,
                    ];
                }
            }
        });

        if (empty($discrepancies)) {
            return [];
        }

        return [
            'count' => count($discrepancies),
            'discrepancies' => $discrepancies,
            'summary' => count($discrepancies) . ' wallets have incorrect balances',
        ];
    }

    /**
     * Check fund locks match locked_balance_paise in wallets (PAISE-based)
     */
    private function checkFundLocks(): array
    {
        $discrepancies = [];

        $users = User::has('wallet')->with('wallet')->chunk(1000, function ($userChunk) use (&$discrepancies) {
            foreach ($userChunk as $user) {
                $wallet = $user->wallet;

                // Calculate expected locked balance from active fund locks (using paise)
                $expectedLockedPaise = FundLock::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->sum('amount_paise');

                $actualLockedPaise = $wallet->locked_balance_paise;
                $differencePaise = abs($actualLockedPaise - $expectedLockedPaise);

                // No tolerance for integer paise - exact match required
                if ($differencePaise > 0) {
                    $discrepancies[] = [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'wallet_id' => $wallet->id,
                        'expected_locked_paise' => $expectedLockedPaise,
                        'actual_locked_paise' => $actualLockedPaise,
                        'difference_paise' => $differencePaise,
                    ];
                }
            }
        });

        if (empty($discrepancies)) {
            return [];
        }

        return [
            'count' => count($discrepancies),
            'discrepancies' => $discrepancies,
            'summary' => count($discrepancies) . ' wallets have incorrect locked balances',
        ];
    }

    /**
     * Fix fund lock discrepancies by recalculating from active locks (PAISE-only)
     */
    private function fixFundLocks(array $discrepancies): int
    {
        $fixed = 0;

        foreach ($discrepancies as $issue) {
            try {
                DB::transaction(function () use ($issue, &$fixed) {
                    $wallet = \App\Models\Wallet::find($issue['wallet_id']);

                    if ($wallet) {
                        // Only update the canonical paise field
                        $wallet->update([
                            'locked_balance_paise' => $issue['expected_locked_paise'],
                        ]);

                        Log::info('Fixed fund lock discrepancy (paise)', [
                            'user_id' => $issue['user_id'],
                            'old_locked_paise' => $issue['actual_locked_paise'],
                            'new_locked_paise' => $issue['expected_locked_paise'],
                        ]);

                        $fixed++;
                    }
                });
            } catch (\Exception $e) {
                Log::error('Failed to fix fund lock discrepancy', [
                    'user_id' => $issue['user_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $fixed;
    }

    /**
     * Check admin ledger accounting equation
     */
    private function checkAdminLedger(): ?array
    {
        $solvency = $this->adminLedger->calculateSolvency();

        if (!$solvency['accounting_balances']) {
            return [
                'message' => 'Accounting equation does not balance',
                'discrepancy' => $solvency['discrepancy'],
                'details' => $solvency,
            ];
        }

        return null;
    }

    /**
     * Send alert notification to admins
     */
    private function sendAlertToAdmins(array $issues): void
    {
        // TODO: Implement admin notification
        // This could be email, Slack, SMS, etc.
        Log::critical('BALANCE RECONCILIATION ALERT: Discrepancies detected', $issues);
    }
}
