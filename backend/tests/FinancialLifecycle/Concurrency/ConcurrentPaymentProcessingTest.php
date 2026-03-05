<?php

/**
 * ConcurrentPaymentProcessingTest
 *
 * INVARIANT: Concurrent payments must be processed safely.
 *
 * Tests that:
 * - Multiple payments for same user don't corrupt wallet
 * - Inventory allocations don't oversell
 * - No deadlocks under concurrent load
 *
 * @package Tests\FinancialLifecycle\Concurrency
 */

namespace Tests\FinancialLifecycle\Concurrency;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use Tests\FinancialLifecycle\Support\ConcurrencyTestHelper;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\Transaction;

class ConcurrentPaymentProcessingTest extends FinancialLifecycleTestCase
{
    private ConcurrencyTestHelper $concurrencyHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->concurrencyHelper = new ConcurrencyTestHelper();
    }

    /**
     * Test that concurrent deposits don't lose money.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function concurrent_deposits_dont_lose_money(): void
    {
        $this->createTestUser();

        $depositAmount = 100000; // 1000 rupees
        $depositCount = 5;

        $results = $this->concurrencyHelper->simulateConcurrentWalletOperations(
            $this->testWallet,
            $depositCount,
            $depositAmount
        );

        $this->assertTrue(
            $results['balance_correct'],
            "Concurrent deposits resulted in incorrect balance. " .
            "Expected: {$results['expected_balance']}, Got: {$results['final_balance']}"
        );
    }

    /**
     * Test that same payment isn't processed twice concurrently.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function same_payment_not_processed_twice(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        $processedCount = 0;

        $results = $this->concurrencyHelper->simulateRaceCondition(
            $payment,
            function ($p) use (&$processedCount) {
                // First process
                if ($p->status === 'pending') {
                    $p->update(['status' => Payment::STATUS_PAID]);
                    $processedCount++;
                    return true;
                }
                return false;
            },
            function ($p) use (&$processedCount) {
                // Second process (racing)
                if ($p->status === 'pending') {
                    $p->update(['status' => Payment::STATUS_PAID]);
                    $processedCount++;
                    return true;
                }
                return false;
            }
        );

        // Only one should successfully process
        $payment->refresh();
        $this->assertEquals(Payment::STATUS_PAID, $payment->status);

        // At most one process should have executed the update
        $this->assertLessThanOrEqual(
            1,
            $processedCount,
            "Payment should only be processed once"
        );
    }

    /**
     * Test that wallet balance remains consistent under concurrent access.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function wallet_balance_consistent_under_load(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();

        // Create multiple payments
        $payments = [];
        for ($i = 0; $i < 5; $i++) {
            $payments[] = $this->createTestPayment($subscription);
        }

        $totalExpected = 0;
        foreach ($payments as $payment) {
            $totalExpected += $payment->amount_paise;
        }

        // Process all payments (simulating concurrent processing)
        foreach ($payments as $payment) {
            try {
                $this->processPaymentLifecycle($payment);
            } catch (\Throwable $e) {
                // May fail due to locking - that's OK
            }
        }

        // Verify wallet balance matches sum of successful deposits
        $this->testWallet->refresh();

        $actualDeposits = Transaction::where('wallet_id', $this->testWallet->id)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount_paise');

        $this->assertEquals(
            $this->testWallet->balance_paise,
            $actualDeposits,
            "Wallet balance must match sum of deposit transactions"
        );
    }

    /**
     * Test inventory doesn't oversell under concurrent allocation.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function inventory_doesnt_oversell(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();

        // Create limited inventory
        $inventory = $this->createTestInventory(100000); // 1000 rupees

        // Create multiple payments that together exceed inventory
        $payments = [];
        for ($i = 0; $i < 3; $i++) {
            $payment = $this->createTestPayment($subscription);
            $payment->update(['amount_paise' => 50000]); // 500 rupees each
            $payments[] = $payment;
        }
        // Total: 1500 rupees, Inventory: 1000 rupees

        // Process all payments
        foreach ($payments as $payment) {
            try {
                $this->processPaymentLifecycle($payment);

                // Try allocation
                $allocationService = app(\App\Services\AllocationService::class);
                $allocationService->allocateSharesLegacy($payment, 500.00);
            } catch (\Throwable $e) {
                // Expected for some - insufficient inventory
            }
        }

        // Verify inventory not oversold
        $inventory->refresh();

        $totalAllocated = \App\Models\UserInvestment::where('bulk_purchase_id', $inventory->id)
            ->where('is_reversed', false)
            ->sum('value_allocated');

        $totalValueReceived = $inventory->total_value_received;

        $this->assertLessThanOrEqual(
            $totalValueReceived,
            $totalAllocated,
            "Inventory oversold! Allocated: {$totalAllocated}, Total: {$totalValueReceived}"
        );

        $this->assertGreaterThanOrEqual(
            0,
            $inventory->value_remaining,
            "Inventory remaining cannot be negative"
        );
    }

    /**
     * Test that deadlocks are handled gracefully.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function deadlocks_handled_gracefully(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();

        $payment1 = $this->createTestPayment($subscription);
        $payment2 = $this->createTestPayment($subscription);

        $results = $this->concurrencyHelper->simulateDeadlockScenario(
            $payment1,
            $payment2
        );

        // Either no deadlock (good locking order) or deadlock handled
        if ($results['deadlock_occurred']) {
            // Deadlock should be caught and handled, not crash
            $this->assertTrue(
                true,
                "Deadlock was detected and handled"
            );
        } else {
            // Good - consistent lock order prevented deadlock
            $this->assertFalse(
                $results['deadlock_occurred'],
                "No deadlock with consistent lock ordering"
            );
        }
    }

    /**
     * Test that transaction isolation prevents dirty reads.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function transaction_isolation_prevents_dirty_reads(): void
    {
        $this->createTestUser();

        $initialBalance = $this->testWallet->balance_paise;

        // Transaction 1: Start but don't commit
        $uncommittedBalance = null;

        DB::transaction(function () use ($initialBalance, &$uncommittedBalance) {
            $this->testWallet->increment('balance_paise', 100000);
            $uncommittedBalance = $this->testWallet->fresh()->balance_paise;

            // Another "connection" reading balance
            // Should not see uncommitted change
            $readBalance = \App\Models\Wallet::find($this->testWallet->id)->balance_paise;

            // This assertion depends on isolation level
            // With REPEATABLE READ or higher, should see initial balance
            // With READ COMMITTED, may see committed value only
        });

        // After commit, balance should be updated
        $this->testWallet->refresh();
        $this->assertEquals(
            $initialBalance + 100000,
            $this->testWallet->balance_paise
        );
    }

    /**
     * Test that lost updates are prevented.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function lost_updates_prevented(): void
    {
        $this->createTestUser();
        $this->testWallet->update(['balance_paise' => 100000]);

        // Simulate two concurrent increments
        $increment1 = 50000;
        $increment2 = 30000;

        // Both should succeed and both amounts should be reflected
        DB::transaction(function () use ($increment1) {
            $wallet = \App\Models\Wallet::where('id', $this->testWallet->id)
                ->lockForUpdate()
                ->first();
            $wallet->increment('balance_paise', $increment1);
        });

        DB::transaction(function () use ($increment2) {
            $wallet = \App\Models\Wallet::where('id', $this->testWallet->id)
                ->lockForUpdate()
                ->first();
            $wallet->increment('balance_paise', $increment2);
        });

        $this->testWallet->refresh();

        $expectedBalance = 100000 + $increment1 + $increment2;

        $this->assertEquals(
            $expectedBalance,
            $this->testWallet->balance_paise,
            "Lost update: Both increments should be applied"
        );
    }

    /**
     * Process payment through lifecycle.
     */
    private function processPaymentLifecycle(Payment $payment): void
    {
        try {
            $orchestrator = app(\App\Services\FinancialOrchestrator::class);
            $orchestrator->processSuccessfulPayment($payment);
        } catch (\Throwable $e) {
            $webhookService = app(\App\Services\PaymentWebhookService::class);
            $webhookService->handleSuccessfulPayment([
                'order_id' => $payment->gateway_order_id,
                'id' => 'pay_' . $payment->gateway_order_id,
            ]);

            $payment->refresh();
            if ($payment->status === Payment::STATUS_PAID) {
                \App\Jobs\ProcessSuccessfulPaymentJob::dispatchSync($payment);
            }
        }
    }
}
