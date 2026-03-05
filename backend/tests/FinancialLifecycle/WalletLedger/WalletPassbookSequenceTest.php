<?php

/**
 * WalletPassbookSequenceTest
 *
 * INVARIANT: Wallet behaves as a passbook.
 *
 * Every wallet transaction must:
 * - Record balance_before and balance_after
 * - Maintain running balance consistency
 * - Follow sequence: +Deposit, -Allocation, +Bonus
 *
 * @package Tests\FinancialLifecycle\WalletLedger
 */

namespace Tests\FinancialLifecycle\WalletLedger;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Enums\TransactionType;

class WalletPassbookSequenceTest extends FinancialLifecycleTestCase
{
    /**
     * Test that every transaction records balance before and after.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function transactions_record_balance_before_after(): void
    {
        $this->createTestUser();

        $walletService = app(\App\Services\WalletService::class);

        // Perform multiple operations
        $walletService->deposit($this->testUser, 100000, TransactionType::DEPOSIT, 'Deposit 1');
        $walletService->deposit($this->testUser, 50000, TransactionType::DEPOSIT, 'Deposit 2');

        $transactions = Transaction::where('wallet_id', $this->testWallet->id)
            ->orderBy('id')
            ->get();

        foreach ($transactions as $txn) {
            $this->assertNotNull(
                $txn->balance_before_paise,
                "Transaction #{$txn->id} missing balance_before_paise"
            );

            $this->assertNotNull(
                $txn->balance_after_paise,
                "Transaction #{$txn->id} missing balance_after_paise"
            );
        }
    }

    /**
     * Test that running balance is consistent.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function running_balance_consistent(): void
    {
        $this->createTestUser();

        $walletService = app(\App\Services\WalletService::class);

        // Perform multiple operations
        $walletService->deposit($this->testUser, 100000, TransactionType::DEPOSIT, 'Deposit 1');
        $walletService->deposit($this->testUser, 50000, TransactionType::DEPOSIT, 'Deposit 2');
        $walletService->deposit($this->testUser, 25000, TransactionType::DEPOSIT, 'Deposit 3');

        $transactions = Transaction::where('wallet_id', $this->testWallet->id)
            ->orderBy('id')
            ->get();

        $runningBalance = 0;

        foreach ($transactions as $index => $txn) {
            // Verify balance_before matches previous balance_after
            $this->assertEquals(
                $runningBalance,
                $txn->balance_before_paise,
                "Transaction #{$txn->id} balance_before should match previous balance_after"
            );

            // Update running balance based on transaction type
            $type = TransactionType::tryFrom($txn->type);
            if ($type && $type->isCredit()) {
                $runningBalance += $txn->amount_paise;
            } else {
                $runningBalance -= $txn->amount_paise;
            }

            // Verify balance_after matches calculated
            $this->assertEquals(
                $runningBalance,
                $txn->balance_after_paise,
                "Transaction #{$txn->id} balance_after inconsistent"
            );
        }

        // Final balance should match wallet
        $this->testWallet->refresh();
        $this->assertEquals(
            $runningBalance,
            $this->testWallet->balance_paise,
            "Final running balance should match wallet balance"
        );
    }

    /**
     * Test passbook sequence: deposit, then potential withdrawal.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function passbook_sequence_deposit_withdrawal(): void
    {
        $this->createTestUser();

        $walletService = app(\App\Services\WalletService::class);

        // Deposit
        $depositTxn = $walletService->deposit(
            $this->testUser,
            100000,
            TransactionType::DEPOSIT,
            'Test deposit'
        );

        $this->assertEquals(0, $depositTxn->balance_before_paise);
        $this->assertEquals(100000, $depositTxn->balance_after_paise);

        // Withdrawal
        $withdrawTxn = $walletService->withdraw(
            $this->testUser,
            30000,
            TransactionType::WITHDRAWAL,
            'Test withdrawal'
        );

        $this->assertEquals(100000, $withdrawTxn->balance_before_paise);
        $this->assertEquals(70000, $withdrawTxn->balance_after_paise);
    }

    /**
     * Test that credits have positive effect on balance.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function credits_increase_balance(): void
    {
        $this->createTestUser();

        $walletService = app(\App\Services\WalletService::class);

        $creditTypes = [
            TransactionType::DEPOSIT,
            TransactionType::REFUND,
            TransactionType::BONUS_CREDIT,
        ];

        foreach ($creditTypes as $type) {
            $initialBalance = $this->testWallet->fresh()->balance_paise;

            $txn = $walletService->deposit(
                $this->testUser,
                10000,
                $type,
                "Test {$type->value}"
            );

            $this->testWallet->refresh();

            $this->assertEquals(
                $initialBalance + 10000,
                $this->testWallet->balance_paise,
                "Credit type {$type->value} should increase balance"
            );
        }
    }

    /**
     * Test that debits have negative effect on balance.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function debits_decrease_balance(): void
    {
        $this->createTestUser();

        $walletService = app(\App\Services\WalletService::class);

        // First deposit
        $walletService->deposit($this->testUser, 100000, TransactionType::DEPOSIT, 'Initial');

        $debitTypes = [
            TransactionType::WITHDRAWAL,
            TransactionType::INVESTMENT,
        ];

        foreach ($debitTypes as $type) {
            $initialBalance = $this->testWallet->fresh()->balance_paise;

            if ($initialBalance < 10000) {
                continue; // Skip if insufficient balance
            }

            $txn = $walletService->withdraw(
                $this->testUser,
                10000,
                $type,
                "Test {$type->value}"
            );

            $this->testWallet->refresh();

            $this->assertEquals(
                $initialBalance - 10000,
                $this->testWallet->balance_paise,
                "Debit type {$type->value} should decrease balance"
            );
        }
    }

    /**
     * Test transaction chain integrity.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function transaction_chain_integrity(): void
    {
        $this->createTestUser();

        $walletService = app(\App\Services\WalletService::class);

        // Perform a series of operations
        $walletService->deposit($this->testUser, 100000, TransactionType::DEPOSIT, 'D1');
        $walletService->deposit($this->testUser, 50000, TransactionType::DEPOSIT, 'D2');
        $walletService->withdraw($this->testUser, 30000, TransactionType::WITHDRAWAL, 'W1');
        $walletService->deposit($this->testUser, 20000, TransactionType::BONUS_CREDIT, 'B1');

        $transactions = Transaction::where('wallet_id', $this->testWallet->id)
            ->orderBy('id')
            ->get();

        // Verify chain
        $previousBalanceAfter = 0;

        foreach ($transactions as $txn) {
            // Each transaction's balance_before should equal previous balance_after
            $this->assertEquals(
                $previousBalanceAfter,
                $txn->balance_before_paise,
                "Chain broken at transaction #{$txn->id}"
            );

            $previousBalanceAfter = $txn->balance_after_paise;
        }

        // Final check
        $this->testWallet->refresh();
        $this->assertEquals(
            $previousBalanceAfter,
            $this->testWallet->balance_paise,
            "Wallet balance should match last transaction balance_after"
        );
    }

    /**
     * Test that all amounts are positive in passbook.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function all_amounts_positive(): void
    {
        $this->createTestUser();

        $walletService = app(\App\Services\WalletService::class);

        $walletService->deposit($this->testUser, 100000, TransactionType::DEPOSIT, 'D1');
        $walletService->withdraw($this->testUser, 30000, TransactionType::WITHDRAWAL, 'W1');

        $transactions = Transaction::where('wallet_id', $this->testWallet->id)->get();

        foreach ($transactions as $txn) {
            $this->assertGreaterThan(
                0,
                $txn->amount_paise,
                "Transaction amount should always be positive. Direction is indicated by type."
            );
        }
    }
}
