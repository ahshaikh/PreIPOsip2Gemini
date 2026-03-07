<?php

namespace App\Console\Commands;

use App\Models\BonusTransaction;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DebugFinancialGraph extends Command
{
    protected $signature = 'debug:financial-graph {paymentId}';
    protected $description = 'Print financial mutation graph for a payment (Payment -> Bonus -> WalletTransaction -> Ledger)';

    public function handle(): int
    {
        $paymentId = (int) $this->argument('paymentId');
        $payment = Payment::find($paymentId);

        if (!$payment) {
            $this->error("Payment not found: {$paymentId}");
            return self::FAILURE;
        }

        $wallet = Wallet::where('user_id', $payment->user_id)->first();

        $bonuses = BonusTransaction::where('payment_id', $payment->id)
            ->orderBy('id')
            ->get();

        $walletTransactions = $this->resolveWalletTransactions($payment, $wallet, $bonuses);
        $ledgerEntries = $this->resolveLedgerEntries($payment, $bonuses, $walletTransactions);
        $ledgerLines = $this->resolveLedgerLines($ledgerEntries);

        $this->line('====================================');
        $this->line(' FINANCIAL MUTATION GRAPH');
        $this->line(" Payment ID: {$payment->id}");
        $this->line('====================================');
        $this->newLine();

        $this->line('Payment');
        $this->line("  id: {$payment->id}");
        $this->line("  user_id: {$payment->user_id}");
        $this->line("  amount: {$payment->amount}");
        if (method_exists($payment, 'getAmountPaiseStrict')) {
            $this->line("  amount_paise: {$payment->getAmountPaiseStrict()}");
        }
        $this->newLine();

        $this->line('BonusTransactions');
        if ($bonuses->isEmpty()) {
            $this->line('  (none)');
        } else {
            foreach ($bonuses as $bonus) {
                $this->line("  id: {$bonus->id}");
                $this->line("  type: {$bonus->type}");
                $this->line("  amount: {$bonus->amount}");
                $this->line("  payment_id: {$bonus->payment_id}");
                $this->line("  description: {$bonus->description}");
                $this->newLine();
            }
        }

        $this->line('WalletTransactions');
        if ($walletTransactions->isEmpty()) {
            $this->line('  (none)');
        } else {
            foreach ($walletTransactions as $txn) {
                $this->line("  id: {$txn->id}");
                $this->line("  type: {$txn->type}");
                $this->line("  amount_paise: {$txn->amount_paise}");
                $this->line("  reference_type: {$txn->reference_type}");
                $this->line("  reference_id: {$txn->reference_id}");
                $this->line("  wallet_id: {$txn->wallet_id}");
                $this->newLine();
            }
        }

        $this->line('LedgerEntries');
        if ($ledgerEntries->isEmpty()) {
            $this->line('  (none)');
        } else {
            foreach ($ledgerEntries as $entry) {
                $this->line("  id: {$entry->id}");
                $this->line("  reference_type: {$entry->reference_type}");
                $this->line("  reference_id: {$entry->reference_id}");
                $this->line("  description: {$entry->description}");
                $this->newLine();
            }
        }

        $this->line('LedgerLines');
        if ($ledgerLines->isEmpty()) {
            $this->line('  (none)');
        } else {
            foreach ($ledgerLines as $line) {
                $amountPaise = $this->ledgerLineAmountPaise($line);
                $this->line("  id: {$line->id}");
                $this->line("  ledger_entry_id: {$line->ledger_entry_id}");
                $this->line("  direction: {$line->direction}");
                $this->line("  amount_paise: {$amountPaise}");
                $this->newLine();
            }
        }

        $this->line('Wallet State');
        if (!$wallet) {
            $this->line('  wallet: (not found)');
        } else {
            $this->line("  wallet_id: {$wallet->id}");
            $this->line("  balance_paise: {$wallet->balance_paise}");
            $this->line("  locked_balance_paise: {$wallet->locked_balance_paise}");
        }
        $this->newLine();

        $ledgerSumPaise = $this->resolveWalletLedgerSumPaise($wallet);
        $this->line('Ledger Sum');
        $this->line("  sum(amount_paise): {$ledgerSumPaise}");
        $this->newLine();

        if ($wallet && $ledgerSumPaise !== $wallet->balance_paise) {
            $this->error(' FINANCIAL INVARIANT VIOLATION');
            $this->error(" Wallet {$wallet->id} mismatch: balance_paise={$wallet->balance_paise} ledger_sum_paise={$ledgerSumPaise}");
        } else {
            $this->info(' Financial invariant check passed');
        }

        return self::SUCCESS;
    }

    private function resolveWalletTransactions(Payment $payment, ?Wallet $wallet, Collection $bonuses): Collection
    {
        $bonusIds = $bonuses->pluck('id')->filter()->values();

        $query = Transaction::query();

        if ($wallet) {
            $query->where('wallet_id', $wallet->id);
        } else {
            $query->where('user_id', $payment->user_id);
        }

        if ($bonusIds->isNotEmpty()) {
            $query->whereIn('reference_id', $bonusIds)
                ->whereIn('reference_type', [
                    BonusTransaction::class,
                    'App\\Models\\BonusTransaction',
                    'BonusTransaction',
                    'bonus_transaction',
                ]);
        } else {
            $query->where('reference_id', $payment->id)
                ->whereIn('reference_type', [
                    Payment::class,
                    'App\\Models\\Payment',
                    'Payment',
                    'payment',
                ]);
        }

        return $query->orderBy('id')->get();
    }

    private function resolveLedgerEntries(Payment $payment, Collection $bonuses, Collection $walletTransactions): Collection
    {
        $entryQuery = LedgerEntry::query();
        $txIds = $walletTransactions->pluck('id')->filter()->values();
        $bonusIds = $bonuses->pluck('id')->filter()->values();

        if (Schema::hasColumn('ledger_entries', 'transaction_id')) {
            if ($txIds->isEmpty()) {
                return collect();
            }
            return $entryQuery->whereIn('transaction_id', $txIds)->orderBy('id')->get();
        }

        if (!Schema::hasColumn('ledger_entries', 'reference_id')) {
            return collect();
        }

        $entryQuery->where(function ($q) use ($txIds, $bonusIds, $payment) {
            if ($txIds->isNotEmpty()) {
                $q->whereIn('reference_id', $txIds);
            }
            if ($bonusIds->isNotEmpty()) {
                $q->orWhereIn('reference_id', $bonusIds);
            }
            $q->orWhere('reference_id', $payment->id);
        });

        return $entryQuery->orderBy('id')->get();
    }

    private function resolveLedgerLines(Collection $ledgerEntries): Collection
    {
        if ($ledgerEntries->isEmpty()) {
            return collect();
        }

        return LedgerLine::whereIn('ledger_entry_id', $ledgerEntries->pluck('id'))
            ->orderBy('id')
            ->get();
    }

    private function ledgerLineAmountPaise(LedgerLine $line): int
    {
        if (Schema::hasColumn('ledger_lines', 'amount_paise')) {
            return (int) ($line->amount_paise ?? 0);
        }

        if (Schema::hasColumn('ledger_lines', 'amount')) {
            return (int) round(((float) ($line->amount ?? 0)) * 100);
        }

        return 0;
    }

    private function resolveWalletLedgerSumPaise(?Wallet $wallet): int
    {
        if (!$wallet) {
            return 0;
        }

        if (Schema::hasColumn('ledger_entries', 'wallet_id') && Schema::hasColumn('ledger_entries', 'amount_paise')) {
            return (int) LedgerEntry::where('wallet_id', $wallet->id)->sum('amount_paise');
        }

        if (Schema::hasColumn('ledger_entries', 'wallet_id') && Schema::hasColumn('ledger_entries', 'amount')) {
            return (int) round((float) LedgerEntry::where('wallet_id', $wallet->id)->sum('amount') * 100);
        }

        // Fallback: approximate using completed wallet transactions when direct ledger-wallet mapping is unavailable.
        return (int) Transaction::where('wallet_id', $wallet->id)
            ->where('status', 'completed')
            ->sum('amount_paise');
    }
}

