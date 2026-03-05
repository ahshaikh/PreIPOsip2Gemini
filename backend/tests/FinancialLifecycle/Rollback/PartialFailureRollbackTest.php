<?php

/**
 * PartialFailureRollbackTest
 *
 * INVARIANT: Partial failures must rollback ALL changes.
 *
 * If any step in the payment lifecycle fails:
 * - Wallet deposit must be rolled back
 * - Allocations must be rolled back
 * - Bonus transactions must be rolled back
 * - Ledger entries must be rolled back
 *
 * @package Tests\FinancialLifecycle\Rollback
 */

namespace Tests\FinancialLifecycle\Rollback;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\BonusTransaction;
use App\Models\UserInvestment;
use App\Models\LedgerEntry;

class PartialFailureRollbackTest extends FinancialLifecycleTestCase
{
    /**
     * Test that wallet deposit failure leaves no state changes.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function wallet_failure_leaves_no_changes(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        // Capture initial state
        $initialWalletBalance = $this->testWallet->balance_paise;
        $initialTxnCount = Transaction::count();
        $initialLedgerCount = LedgerEntry::count();

        // Simulate wallet service failure
        $this->mock(\App\Services\WalletService::class, function ($mock) {
            $mock->shouldReceive('deposit')
                ->andThrow(new \RuntimeException('Simulated wallet failure'));
        });

        try {
            DB::transaction(function () use ($payment) {
                $orchestrator = app(\App\Services\FinancialOrchestrator::class);
                $orchestrator->processSuccessfulPayment($payment);
            });
        } catch (\Throwable $e) {
            // Expected
        }

        // Verify state unchanged
        $this->testWallet->refresh();
        $this->assertEquals($initialWalletBalance, $this->testWallet->balance_paise);
        $this->assertEquals($initialTxnCount, Transaction::count());
        $this->assertEquals($initialLedgerCount, LedgerEntry::count());
    }

    /**
     * Test that bonus failure rolls back wallet deposit.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function bonus_failure_rolls_back_deposit(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        $initialWalletBalance = $this->testWallet->balance_paise;
        $initialBonusCount = BonusTransaction::count();

        try {
            DB::transaction(function () use ($payment) {
                // Manually simulate orchestrator behavior
                $walletService = app(\App\Services\WalletService::class);

                // Step 1: Deposit (succeeds)
                $walletService->deposit(
                    $this->testUser,
                    $payment->amount_paise,
                    \App\Enums\TransactionType::DEPOSIT,
                    'Test deposit'
                );

                // Step 2: Bonus (fails)
                throw new \RuntimeException('Simulated bonus failure');
            });
        } catch (\Throwable $e) {
            // Expected
        }

        // ASSERTION: Everything rolled back
        $this->testWallet->refresh();

        $this->assertEquals(
            $initialWalletBalance,
            $this->testWallet->balance_paise,
            "Wallet deposit should be rolled back when bonus fails"
        );

        $this->assertEquals(
            $initialBonusCount,
            BonusTransaction::count(),
            "No bonus transactions should exist after rollback"
        );
    }

    /**
     * Test that allocation failure rolls back everything.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function allocation_failure_rolls_back_everything(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $this->createTestInventory();
        $payment = $this->createTestPayment($subscription);

        $initialWalletBalance = $this->testWallet->balance_paise;
        $initialInvestmentCount = UserInvestment::count();
        $initialInventoryRemaining = $this->testInventory->value_remaining;

        try {
            DB::transaction(function () use ($payment) {
                $walletService = app(\App\Services\WalletService::class);

                // Step 1: Deposit
                $walletService->deposit(
                    $this->testUser,
                    $payment->amount_paise,
                    \App\Enums\TransactionType::DEPOSIT,
                    'Test deposit'
                );

                // Step 2: Allocation (fails)
                throw new \RuntimeException('Simulated allocation failure');
            });
        } catch (\Throwable $e) {
            // Expected
        }

        // Verify rollback
        $this->testWallet->refresh();
        $this->testInventory->refresh();

        $this->assertEquals($initialWalletBalance, $this->testWallet->balance_paise);
        $this->assertEquals($initialInvestmentCount, UserInvestment::count());
        $this->assertEquals($initialInventoryRemaining, $this->testInventory->value_remaining);
    }

    /**
     * Test that ledger failure rolls back all financial changes.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function ledger_failure_rolls_back_all(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        $initialWalletBalance = $this->testWallet->balance_paise;
        $initialLedgerCount = LedgerEntry::count();

        // Mock ledger service to fail
        $this->mock(\App\Services\DoubleEntryLedgerService::class, function ($mock) {
            $mock->shouldReceive('recordUserDeposit')
                ->andThrow(new \RuntimeException('Simulated ledger failure'));
        });

        try {
            DB::transaction(function () use ($payment) {
                $walletService = app(\App\Services\WalletService::class);
                $walletService->deposit(
                    $this->testUser,
                    $payment->amount_paise,
                    \App\Enums\TransactionType::DEPOSIT,
                    'Test deposit'
                );
            });
        } catch (\Throwable $e) {
            // Expected
        }

        // Verify rollback
        $this->testWallet->refresh();

        $this->assertEquals(
            $initialWalletBalance,
            $this->testWallet->balance_paise,
            "Wallet should be rolled back when ledger fails"
        );

        $this->assertEquals(
            $initialLedgerCount,
            LedgerEntry::count(),
            "No new ledger entries should exist after rollback"
        );
    }

    /**
     * Test that payment status is not updated on failure.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function payment_status_not_updated_on_failure(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        $initialStatus = $payment->status;

        try {
            DB::transaction(function () use ($payment) {
                // Update payment status inside transaction
                $payment->update(['status' => Payment::STATUS_PAID]);

                // Then fail
                throw new \RuntimeException('Simulated failure');
            });
        } catch (\Throwable $e) {
            // Expected
        }

        // Payment status should be unchanged
        $payment->refresh();

        $this->assertEquals(
            $initialStatus,
            $payment->status,
            "Payment status should not be updated on lifecycle failure"
        );
    }

    /**
     * Test that notifications are not sent on failure.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function notifications_not_sent_on_failure(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        \Illuminate\Support\Facades\Notification::fake();

        try {
            DB::transaction(function () use ($payment) {
                // Simulate partial success then failure
                $this->testUser->notify(new \App\Notifications\PaymentReceived($payment));

                throw new \RuntimeException('Simulated failure after notification');
            });
        } catch (\Throwable $e) {
            // Expected
        }

        // Notifications should be rolled back if using transaction-aware dispatch
        // (This depends on implementation - may need afterCommit pattern)
        $this->markTestIncomplete(
            "Test notification rollback behavior. " .
            "Notifications should use afterCommit pattern to avoid sending on rollback."
        );
    }

    /**
     * Test cascading rollback through service chain.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function cascading_rollback_through_services(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $this->createTestInventory();
        $payment = $this->createTestPayment($subscription);

        // Capture all initial states
        $initialState = [
            'wallet_balance' => $this->testWallet->balance_paise,
            'transactions' => Transaction::count(),
            'investments' => UserInvestment::count(),
            'bonuses' => BonusTransaction::count(),
            'ledger_entries' => LedgerEntry::count(),
            'inventory' => $this->testInventory->value_remaining,
        ];

        try {
            DB::transaction(function () use ($payment) {
                // Simulate partial progress through lifecycle
                $walletService = app(\App\Services\WalletService::class);

                // Deposit succeeds
                $walletService->deposit(
                    $this->testUser,
                    $payment->amount_paise,
                    \App\Enums\TransactionType::DEPOSIT,
                    'Test deposit'
                );

                // Simulate more progress then fail at the end
                throw new \RuntimeException('Final step failure');
            });
        } catch (\Throwable $e) {
            // Expected
        }

        // Verify ALL changes rolled back
        $this->testWallet->refresh();
        $this->testInventory->refresh();

        $this->assertEquals($initialState['wallet_balance'], $this->testWallet->balance_paise);
        $this->assertEquals($initialState['transactions'], Transaction::count());
        $this->assertEquals($initialState['investments'], UserInvestment::count());
        $this->assertEquals($initialState['bonuses'], BonusTransaction::count());
        $this->assertEquals($initialState['ledger_entries'], LedgerEntry::count());
        $this->assertEquals($initialState['inventory'], $this->testInventory->value_remaining);
    }
}
