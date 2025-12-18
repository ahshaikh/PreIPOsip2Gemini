<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-AUTO-FREEZE | V-SCALABLE-AUDIT
 * * ARCHITECTURAL FIX: 
 * Prevents "Table Scan" bottlenecks by only auditing active/dirty wallets.
 * Implements the "Auto-Freeze" security protocol for financial integrity.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Models\User;
use App\Notifications\WalletDiscrepancyAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WalletAudit extends Command
{
    protected $signature = 'wallet:audit {--all : Force audit of every wallet in the system}';
    protected $description = 'Audit wallet integrity and auto-freeze on discrepancy';

    public function handle()
    {
        $this->info('Starting Financial Integrity Audit...');

        // [PERFORMANCE FIX]: Only audit wallets changed since last check unless --all is passed
        $query = Wallet::query();
        if (!$this->option('all')) {
            $query->where('was_modified_since_last_audit', true);
        }

        $query->chunk(100, function ($wallets) {
            foreach ($wallets as $wallet) {
                // Calculate theoretical balance from immutable ledger
                $ledgerBalance = (int) $wallet->transactions()->sum('amount_paise');
                $storedBalance = (int) $wallet->balance_paise;

                if ($storedBalance !== $ledgerBalance) {
                    $this->handleDiscrepancy($wallet, $storedBalance, $ledgerBalance);
                } else {
                    // Reset dirty flag if healthy
                    $wallet->update(['was_modified_since_last_audit' => false]);
                }
            }
        });

        $this->info('Audit Complete.');
    }

    /**
     * [SECURITY FIX]: Atomic Freeze and Notification
     */
    protected function handleDiscrepancy(Wallet $wallet, int $stored, int $ledger)
    {
        DB::transaction(function () use ($wallet, $stored, $ledger) {
            // 1. Immutable Log Entry
            Log::critical("INTEGRITY_FAILURE: User #{$wallet->user_id}", [
                'diff' => $stored - $ledger,
                'wallet_id' => $wallet->id
            ]);

            // 2. [AUDIT REQUIREMENT]: Auto-Freeze the Wallet
            // Prevents any further 'withdraw' or 'transfer' calls from succeeding
            $wallet->update([
                'status' => 'frozen',
                'system_notes' => "Auto-frozen at " . now() . " due to ledger mismatch."
            ]);

            // 3. [AUDIT REQUIREMENT]: Notify Admins
            $admins = User::whereHas('roles', fn($q) => $q->where('name', 'super-admin'))->get();
            foreach ($admins as $admin) {
                $admin->notify(new WalletDiscrepancyAlert($wallet, $ledger));
            }
        });

        $this->error("Discrepancy found and frozen for User #{$wallet->user_id}");
    }
}