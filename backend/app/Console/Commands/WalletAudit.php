<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-LEDGER-INTEGRITY | V-INTEGER-AUDIT
 * Refactored to address Module 8 Audit Gaps:
 * 1. Integer Comparison: Uses exact integer matching for Paise (no epsilon needed).
 * 2. Automatic Flagging: Logs critical integrity failures for admin review.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

class WalletAudit extends Command
{
    protected $signature = 'wallet:audit {--fix : Attempt to auto-fix discrepancies (Use with caution)}';
    protected $description = 'Audit user wallets to ensure ledger integrity (Balance == Sum of Transactions)';

    public function handle()
    {
        $this->info('Starting Wallet Ledger Audit (Integer-Paise Basis)...');

        Wallet::chunk(100, function ($wallets) {
            foreach ($wallets as $wallet) {
                // [AUDIT FIX]: Sum transactions in Paise. 
                // Sum(All Transactions) must strictly equal the stored balance_paise.
                $ledgerBalance = (int) $wallet->transactions()->sum('amount_paise');
                
                $storedBalance = (int) $wallet->balance_paise;

                if ($storedBalance !== $ledgerBalance) {
                    $this->error("Integrity Error [User #{$wallet->user_id}]: Wallet {$storedBalance} != Ledger {$ledgerBalance}");
                    
                    Log::critical('WALLET_INTEGRITY_MISMATCH', [
                        'user_id' => $wallet->user_id,
                        'stored_balance_paise' => $storedBalance,
                        'ledger_sum_paise' => $ledgerBalance,
                        'drift' => $storedBalance - $ledgerBalance
                    ]);

                    if ($this->option('fix')) {
                        $this->warn("Auto-fixing balance for User #{$wallet->user_id}...");
                        $wallet->update(['balance_paise' => $ledgerBalance]);
                    }
                }
            }
        });

        $this->info('Audit Complete.');
    }
}