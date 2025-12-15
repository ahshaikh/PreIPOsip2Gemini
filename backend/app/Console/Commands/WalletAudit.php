<?php
// V-AUDIT-FIX-MODULE7 (New Command)

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:audit {--fix : Attempt to auto-fix discrepancies (Use with caution)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit user wallets to ensure ledger integrity (Balance == Sum of Transactions)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Wallet Ledger Audit...');

        // Chunking to handle thousands of users without memory overflow
        Wallet::chunk(100, function ($wallets) {
            foreach ($wallets as $wallet) {
                // Calculate the theoretical balance from the ledger (transactions)
                // We sum 'amount'. Note: Withdrawals are negative, Deposits are positive.
                $ledgerBalance = $wallet->transactions()->sum('amount');
                
                // Compare with the actual cached 'balance' column + 'locked_balance' column?
                // Actually, transactions reduce 'balance' on withdrawal request (if locked) OR reduce it immediately.
                // When funds are locked, they are moved from 'balance' to 'locked_balance'.
                // So, Total Equity = balance + locked_balance.
                // The Sum of Transactions represents the Total Equity history.
                
                // Wait, in WalletService::withdraw(lockBalance: true):
                // $wallet->decrement('balance', $amount);
                // $wallet->increment('locked_balance', $amount);
                // Transaction created with amount = -$amount.
                
                // So: Transaction Sum = (Original Deposits) - (Withdrawals)
                // Current Wallet State = Balance + Locked Balance
                
                // However, the transaction sum includes the negative amount for the locked withdrawal.
                // Example: Deposit 1000. Balance 1000. Tx Sum 1000.
                // Withdraw 100 (Lock). Balance 900. Locked 100. Tx Sum 900 (1000 - 100).
                // So: (Balance + Locked Balance) should equal (Tx Sum + Locked Balance)? 
                // No.
                // If Tx Sum is 900, it means the user has 900 *net* available or processed.
                // But the 100 is still "theirs", just locked.
                
                // Let's look at WalletService again.
                // "withdraw... lockBalance=true... decrement balance... increment locked... create transaction amount = -amount"
                // The transaction creates a debit record immediately.
                // So the Ledger says "User spent 100". 
                // The Wallet says "User has 900 free + 100 locked".
                // If we sum transactions, we get 900.
                // If we sum wallet columns (balance + locked), we get 1000.
                
                // CORRECTION: The Ledger Integrity check should be:
                // Sum(Transactions) should match `balance`. 
                // BUT, locked funds are tricky.
                // Ideally, a "lock" shouldn't create a debit transaction until it's finalized?
                // WalletService creates the transaction with status 'pending' when locking.
                // If we treat 'pending' transactions as effectively "spent" from the main balance, then:
                // Sum(All Transactions) == Wallet Balance.
                
                // Let's verify:
                // Start: 0.
                // Deposit 100. Balance=100. Tx=+100. Sum=100. MATCH.
                // Lock 10. Balance=90. Locked=10. Tx=-10 (Pending).
                // Sum(Tx) = 100 - 10 = 90.
                // Wallet Balance = 90.
                // MATCH.
                
                // So, Sum(All Transactions) must equal Wallet->balance.
                // The locked_balance is a separate "bucket" that tracks the pending transactions.
                
                // Floating point comparison needs epsilon
                if (abs($wallet->balance - $ledgerBalance) > 0.001) {
                    $this->error("Integrity Error User ID {$wallet->user_id}: Wallet Balance {$wallet->balance} != Ledger Sum {$ledgerBalance}");
                    
                    Log::critical('Wallet Integrity Mismatch', [
                        'user_id' => $wallet->user_id,
                        'wallet_balance' => $wallet->balance,
                        'ledger_sum' => $ledgerBalance,
                        'difference' => $wallet->balance - $ledgerBalance
                    ]);

                    if ($this->option('fix')) {
                        $this->warn("Auto-fixing balance for User {$wallet->user_id}...");
                        $wallet->balance = $ledgerBalance;
                        $wallet->save();
                    }
                } else {
                    // $this->info("User {$wallet->user_id}: OK");
                }
            }
        });

        $this->info('Audit Complete.');
    }
}