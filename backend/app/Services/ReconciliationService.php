<?php
/**
 * FIX 3 (P0): Reconciliation Service
 *
 * CRITICAL: Automated daily verification of all financial invariants
 * Detects data drift, balance mismatches, and integrity violations.
 *
 * Checks Performed:
 * 1. Wallet Balance Conservation (balance = sum of transactions)
 * 2. Inventory Conservation (value_remaining + allocated = total_received)
 * 3. Admin Ledger Equation (Assets = Liabilities + Equity)
 * 4. Payment Idempotency (no duplicate gateway_payment_id)
 * 5. Locked Balance Validation (locked >= pending withdrawals)
 *
 * Schedule: Daily at 02:00 AM via Cron
 * Alerts: Email/Slack to admins on failure
 */

namespace App\Services;

use App\Models\{Wallet, Transaction, BulkPurchase, UserInvestment, AdminLedgerEntry, Payment, Withdrawal};
use Illuminate\Support\Facades\{DB, Log, Mail};
use Illuminate\Support\Collection;

class ReconciliationService
{
    protected array $errors = [];
    protected array $warnings = [];
    protected array $stats = [];

    /**
     * Run complete daily reconciliation
     *
     * @return array Summary of reconciliation results
     */
    public function runDailyReconciliation(): array
    {
        $this->errors = [];
        $this->warnings = [];
        $this->stats = [
            'start_time' => now(),
            'checks_performed' => 0,
            'wallets_checked' => 0,
            'bulk_purchases_checked' => 0,
        ];

        Log::info('Starting daily reconciliation', [
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Check 1: Wallet Balance Conservation
        $this->checkWalletBalances();

        // Check 2: Inventory Conservation
        $this->checkInventoryConservation();

        // Check 3: Admin Ledger Equation
        $this->checkAdminLedgerEquation();

        // Check 4: Payment Idempotency
        $this->checkPaymentIdempotency();

        // Check 5: Locked Balance Validation
        $this->checkLockedBalances();

        // Check 6: Investment Provenance
        $this->checkInvestmentProvenance();

        $this->stats['end_time'] = now();
        $this->stats['duration_seconds'] = $this->stats['end_time']->diffInSeconds($this->stats['start_time']);
        $this->stats['error_count'] = count($this->errors);
        $this->stats['warning_count'] = count($this->warnings);

        $result = [
            'success' => empty($this->errors),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'stats' => $this->stats,
        ];

        if (!empty($this->errors)) {
            $this->alertAdmins($result);
            Log::critical('Daily reconciliation FAILED', $result);
        } else {
            Log::info('Daily reconciliation completed successfully', $this->stats);
        }

        // Store reconciliation result
        $this->storeReconciliationResult($result);

        return $result;
    }

    /**
     * Check 1: Wallet Balance Conservation
     * Invariant: Wallet.balance_paise = SUM(credits) - SUM(debits)
     */
    protected function checkWalletBalances(): void
    {
        Log::info('Checking wallet balance conservation...');
        $this->stats['checks_performed']++;

        $walletsWithMismatches = [];

        // Check all wallets
        Wallet::chunk(1000, function (Collection $wallets) use (&$walletsWithMismatches) {
            foreach ($wallets as $wallet) {
                $this->stats['wallets_checked']++;

                // Calculate expected balance from transactions
                $credits = Transaction::where('wallet_id', $wallet->id)
                    ->where('type', 'credit')
                    ->where('status', 'completed')
                    ->sum('amount_paise');

                $debits = Transaction::where('wallet_id', $wallet->id)
                    ->where('type', 'debit')
                    ->where('status', 'completed')
                    ->sum('amount_paise');

                $expectedBalance = $credits - $debits;

                if ($wallet->balance_paise !== $expectedBalance) {
                    $difference = $wallet->balance_paise - $expectedBalance;

                    $walletsWithMismatches[] = [
                        'wallet_id' => $wallet->id,
                        'user_id' => $wallet->user_id,
                        'stored_balance_paise' => $wallet->balance_paise,
                        'stored_balance_rupees' => $wallet->balance_paise / 100,
                        'calculated_balance_paise' => $expectedBalance,
                        'calculated_balance_rupees' => $expectedBalance / 100,
                        'difference_paise' => $difference,
                        'difference_rupees' => $difference / 100,
                        'credits' => $credits,
                        'debits' => $debits,
                    ];
                }
            }
        });

        if (!empty($walletsWithMismatches)) {
            $this->errors[] = [
                'type' => 'wallet_balance_mismatch',
                'severity' => 'critical',
                'message' => count($walletsWithMismatches) . ' wallet(s) have balance mismatches',
                'details' => $walletsWithMismatches,
            ];
        }
    }

    /**
     * Check 2: Inventory Conservation
     * Invariant: value_remaining + allocated = total_value_received
     */
    protected function checkInventoryConservation(): void
    {
        Log::info('Checking inventory conservation...');
        $this->stats['checks_performed']++;

        $inventoryMismatches = [];

        BulkPurchase::chunk(500, function (Collection $bulkPurchases) use (&$inventoryMismatches) {
            foreach ($bulkPurchases as $bulk) {
                $this->stats['bulk_purchases_checked']++;

                // Calculate allocated amount from UserInvestments
                $allocatedAmount = UserInvestment::where('bulk_purchase_id', $bulk->id)
                    ->where('is_reversed', false)
                    ->sum(DB::raw('value_allocated * 100')); // Convert to paise

                $expectedRemaining = $bulk->total_value_received - ($allocatedAmount / 100);

                // Allow small rounding differences (< 0.01 rupees = < 1 paise)
                if (abs($bulk->value_remaining - $expectedRemaining) >= 0.01) {
                    $inventoryMismatches[] = [
                        'bulk_purchase_id' => $bulk->id,
                        'product_id' => $bulk->product_id,
                        'company_id' => $bulk->company_id,
                        'total_value_received' => $bulk->total_value_received,
                        'stored_remaining' => $bulk->value_remaining,
                        'calculated_remaining' => $expectedRemaining,
                        'difference' => $bulk->value_remaining - $expectedRemaining,
                        'allocated_amount' => $allocatedAmount / 100,
                        'investment_count' => UserInvestment::where('bulk_purchase_id', $bulk->id)
                            ->where('is_reversed', false)
                            ->count(),
                    ];
                }
            }
        });

        if (!empty($inventoryMismatches)) {
            $this->errors[] = [
                'type' => 'inventory_mismatch',
                'severity' => 'critical',
                'message' => count($inventoryMismatches) . ' inventory record(s) have allocation mismatches',
                'details' => $inventoryMismatches,
            ];
        }
    }

    /**
     * Check 3: Admin Ledger Equation
     * Invariant: Assets (cash + inventory) = Liabilities + Equity (revenue - expenses)
     */
    protected function checkAdminLedgerEquation(): void
    {
        Log::info('Checking admin ledger equation...');
        $this->stats['checks_performed']++;

        // Calculate account balances
        $cash = AdminLedgerEntry::where('account', 'cash')
            ->sum(DB::raw('CASE WHEN type = "debit" THEN amount_paise ELSE -amount_paise END'));

        $inventory = AdminLedgerEntry::where('account', 'inventory')
            ->sum(DB::raw('CASE WHEN type = "debit" THEN amount_paise ELSE -amount_paise END'));

        $liabilities = AdminLedgerEntry::where('account', 'liabilities')
            ->sum(DB::raw('CASE WHEN type = "credit" THEN amount_paise ELSE -amount_paise END'));

        $revenue = AdminLedgerEntry::where('account', 'revenue')
            ->sum(DB::raw('CASE WHEN type = "credit" THEN amount_paise ELSE -amount_paise END'));

        $expenses = AdminLedgerEntry::where('account', 'expenses')
            ->sum(DB::raw('CASE WHEN type = "debit" THEN amount_paise ELSE -amount_paise END'));

        $assets = $cash + $inventory;
        $equity = $revenue - $expenses;
        $rightSide = $liabilities + $equity;

        // Allow small rounding differences (< 1 rupee = < 100 paise)
        if (abs($assets - $rightSide) >= 100) {
            $this->errors[] = [
                'type' => 'ledger_equation_imbalance',
                'severity' => 'critical',
                'message' => 'Admin ledger equation does not balance',
                'details' => [
                    'assets_paise' => $assets,
                    'assets_rupees' => $assets / 100,
                    'cash_paise' => $cash,
                    'inventory_paise' => $inventory,
                    'liabilities_paise' => $liabilities,
                    'revenue_paise' => $revenue,
                    'expenses_paise' => $expenses,
                    'equity_paise' => $equity,
                    'right_side_paise' => $rightSide,
                    'difference_paise' => $assets - $rightSide,
                    'difference_rupees' => ($assets - $rightSide) / 100,
                ],
            ];
        }

        $this->stats['ledger_assets'] = $assets / 100;
        $this->stats['ledger_liabilities'] = $liabilities / 100;
        $this->stats['ledger_equity'] = $equity / 100;
    }

    /**
     * Check 4: Payment Idempotency
     * Invariant: gateway_payment_id must be unique
     */
    protected function checkPaymentIdempotency(): void
    {
        Log::info('Checking payment idempotency...');
        $this->stats['checks_performed']++;

        $duplicates = Payment::select('gateway_payment_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('gateway_payment_id')
            ->groupBy('gateway_payment_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $duplicateDetails = $duplicates->map(function ($dup) {
                return [
                    'gateway_payment_id' => $dup->gateway_payment_id,
                    'count' => $dup->count,
                    'payment_ids' => Payment::where('gateway_payment_id', $dup->gateway_payment_id)
                        ->pluck('id')
                        ->toArray(),
                ];
            })->toArray();

            $this->errors[] = [
                'type' => 'payment_idempotency_violation',
                'severity' => 'critical',
                'message' => count($duplicates) . ' duplicate gateway_payment_id(s) found',
                'details' => $duplicateDetails,
            ];
        }
    }

    /**
     * Check 5: Locked Balance Validation
     * Invariant: locked_balance_paise >= pending withdrawal amounts
     */
    protected function checkLockedBalances(): void
    {
        Log::info('Checking locked balances...');
        $this->stats['checks_performed']++;

        $lockedBalanceIssues = [];

        Wallet::chunk(1000, function (Collection $wallets) use (&$lockedBalanceIssues) {
            foreach ($wallets as $wallet) {
                // Calculate pending withdrawals for this user
                $pendingWithdrawals = Withdrawal::where('user_id', $wallet->user_id)
                    ->whereIn('status', ['pending', 'approved'])
                    ->sum(DB::raw('(amount + fee + tds_deducted) * 100')); // Convert to paise

                if ($wallet->locked_balance_paise < $pendingWithdrawals) {
                    $lockedBalanceIssues[] = [
                        'wallet_id' => $wallet->id,
                        'user_id' => $wallet->user_id,
                        'locked_balance_paise' => $wallet->locked_balance_paise,
                        'locked_balance_rupees' => $wallet->locked_balance_paise / 100,
                        'pending_withdrawals_paise' => $pendingWithdrawals,
                        'pending_withdrawals_rupees' => $pendingWithdrawals / 100,
                        'shortfall_paise' => $pendingWithdrawals - $wallet->locked_balance_paise,
                        'shortfall_rupees' => ($pendingWithdrawals - $wallet->locked_balance_paise) / 100,
                    ];
                }
            }
        });

        if (!empty($lockedBalanceIssues)) {
            $this->warnings[] = [
                'type' => 'locked_balance_insufficient',
                'severity' => 'high',
                'message' => count($lockedBalanceIssues) . ' wallet(s) have insufficient locked balance for pending withdrawals',
                'details' => $lockedBalanceIssues,
            ];
        }
    }

    /**
     * Check 6: Investment Provenance
     * Ensure all UserInvestments link to valid BulkPurchase
     */
    protected function checkInvestmentProvenance(): void
    {
        Log::info('Checking investment provenance...');
        $this->stats['checks_performed']++;

        $orphanedInvestments = UserInvestment::whereDoesntHave('bulkPurchase')->count();

        if ($orphanedInvestments > 0) {
            $this->errors[] = [
                'type' => 'orphaned_investments',
                'severity' => 'critical',
                'message' => "{$orphanedInvestments} investment(s) have no BulkPurchase provenance",
                'details' => [
                    'count' => $orphanedInvestments,
                    'sample_ids' => UserInvestment::whereDoesntHave('bulkPurchase')
                        ->limit(10)
                        ->pluck('id')
                        ->toArray(),
                ],
            ];
        }
    }

    /**
     * Alert admins via email/Slack on reconciliation failure
     */
    protected function alertAdmins(array $result): void
    {
        // Get all admin emails
        $adminEmails = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('roles.name', ['admin', 'super-admin'])
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->pluck('users.email')
            ->toArray();

        if (empty($adminEmails)) {
            Log::warning('No admin emails found for reconciliation alerts');
            return;
        }

        foreach ($adminEmails as $email) {
            try {
                Mail::raw(
                    $this->formatAlertEmail($result),
                    function ($message) use ($email) {
                        $message->to($email)
                            ->subject('[CRITICAL] PreIPOsip Reconciliation Failed - ' . now()->toDateString());
                    }
                );
            } catch (\Exception $e) {
                Log::error('Failed to send reconciliation alert', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // TODO: Add Slack notification
        // Slack::send('PreIPOsip Reconciliation Failed', $result);
    }

    /**
     * Format alert email body
     */
    protected function formatAlertEmail(array $result): string
    {
        $text = "CRITICAL ALERT: Daily Reconciliation Failed\n";
        $text .= "Date: " . now()->toDateTimeString() . "\n";
        $text .= "Duration: " . $result['stats']['duration_seconds'] . " seconds\n\n";

        $text .= "ERRORS (" . count($result['errors']) . "):\n";
        $text .= str_repeat('=', 60) . "\n";
        foreach ($result['errors'] as $error) {
            $text .= "- " . $error['message'] . " [" . $error['severity'] . "]\n";
            $text .= "  Type: " . $error['type'] . "\n\n";
        }

        if (!empty($result['warnings'])) {
            $text .= "\nWARNINGS (" . count($result['warnings']) . "):\n";
            $text .= str_repeat('=', 60) . "\n";
            foreach ($result['warnings'] as $warning) {
                $text .= "- " . $warning['message'] . " [" . $warning['severity'] . "]\n\n";
            }
        }

        $text .= "\nSTATS:\n";
        $text .= str_repeat('=', 60) . "\n";
        $text .= "Checks Performed: " . $result['stats']['checks_performed'] . "\n";
        $text .= "Wallets Checked: " . $result['stats']['wallets_checked'] . "\n";
        $text .= "Bulk Purchases Checked: " . $result['stats']['bulk_purchases_checked'] . "\n";

        $text .= "\nPlease investigate immediately and resolve data integrity issues.\n";
        $text .= "Access admin dashboard: " . config('app.url') . "/admin/reconciliation\n";

        return $text;
    }

    /**
     * Store reconciliation result in database
     */
    protected function storeReconciliationResult(array $result): void
    {
        DB::table('reconciliation_logs')->insert([
            'run_date' => now()->toDateString(),
            'run_time' => now()->toTimeString(),
            'success' => $result['success'],
            'error_count' => count($result['errors']),
            'warning_count' => count($result['warnings']),
            'checks_performed' => $result['stats']['checks_performed'],
            'duration_seconds' => $result['stats']['duration_seconds'],
            'errors' => json_encode($result['errors']),
            'warnings' => json_encode($result['warnings']),
            'stats' => json_encode($result['stats']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
