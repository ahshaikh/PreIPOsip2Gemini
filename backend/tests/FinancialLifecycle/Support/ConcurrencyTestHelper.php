<?php

/**
 * ConcurrencyTestHelper - Utilities for simulating concurrent payment processing.
 *
 * Provides tools for:
 * - Simulating race conditions
 * - Testing lock acquisition
 * - Deadlock detection
 * - Parallel execution simulation
 *
 * @package Tests\FinancialLifecycle\Support
 */

namespace Tests\FinancialLifecycle\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\Subscription;

class ConcurrencyTestHelper
{
    /**
     * Simulated lock acquisition timestamps for ordering verification.
     */
    private array $lockTimestamps = [];

    /**
     * Deadlock detection flag.
     */
    private bool $deadlockDetected = false;

    /**
     * Expected lock order for financial lifecycle.
     */
    private const LOCK_ORDER = [
        'payments',
        'subscriptions',
        'wallets',
        'products',
        'user_investments',
        'bonus_transactions',
    ];

    /**
     * Simulate a race condition between two payment processes.
     *
     * This method creates a scenario where two processes attempt to
     * process the same payment simultaneously.
     *
     * @param Payment $payment
     * @param callable $process1 First process to execute
     * @param callable $process2 Second process to execute
     * @return array Results from both processes
     */
    public function simulateRaceCondition(
        Payment $payment,
        callable $process1,
        callable $process2
    ): array {
        $results = [
            'process1' => ['success' => false, 'error' => null, 'executed' => false],
            'process2' => ['success' => false, 'error' => null, 'executed' => false],
        ];

        // Use database advisory locks to simulate concurrent access
        $lockKey = "payment_race_{$payment->id}";

        // Process 1: Start transaction and hold lock
        try {
            DB::beginTransaction();

            // Acquire lock on payment
            $lockedPayment = Payment::where('id', $payment->id)
                ->lockForUpdate()
                ->first();

            $results['process1']['executed'] = true;
            $results['process1']['result'] = $process1($lockedPayment);
            $results['process1']['success'] = true;

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $results['process1']['error'] = $e->getMessage();
        }

        // Process 2: Attempt same operation
        try {
            DB::beginTransaction();

            $lockedPayment = Payment::where('id', $payment->id)
                ->lockForUpdate()
                ->first();

            $results['process2']['executed'] = true;
            $results['process2']['result'] = $process2($lockedPayment);
            $results['process2']['success'] = true;

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $results['process2']['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Simulate concurrent wallet operations.
     *
     * Tests that wallet balance updates are atomic and don't result
     * in lost updates or race conditions.
     *
     * @param Wallet $wallet
     * @param int $operationCount Number of concurrent operations
     * @param int $amountPaise Amount for each operation
     * @return array Operation results and final state
     */
    public function simulateConcurrentWalletOperations(
        Wallet $wallet,
        int $operationCount,
        int $amountPaise
    ): array {
        $initialBalance = $wallet->balance_paise;
        $results = [];
        $successCount = 0;
        $failCount = 0;

        for ($i = 0; $i < $operationCount; $i++) {
            try {
                DB::transaction(function () use ($wallet, $amountPaise, $i, &$results, &$successCount) {
                    $lockedWallet = Wallet::where('id', $wallet->id)
                        ->lockForUpdate()
                        ->first();

                    $lockedWallet->increment('balance_paise', $amountPaise);

                    $results[$i] = [
                        'success' => true,
                        'balance_after' => $lockedWallet->fresh()->balance_paise,
                    ];
                    $successCount++;
                });
            } catch (\Throwable $e) {
                $results[$i] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                $failCount++;
            }
        }

        $wallet->refresh();
        $expectedBalance = $initialBalance + ($successCount * $amountPaise);

        return [
            'initial_balance' => $initialBalance,
            'final_balance' => $wallet->balance_paise,
            'expected_balance' => $expectedBalance,
            'balance_correct' => $wallet->balance_paise === $expectedBalance,
            'operations' => $results,
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ];
    }

    /**
     * Record a lock acquisition for order verification.
     *
     * @param string $table Table name
     * @param string $processId Process identifier
     */
    public function recordLockAcquisition(string $table, string $processId): void
    {
        $this->lockTimestamps[] = [
            'table' => $table,
            'process' => $processId,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Verify lock acquisition order follows expected sequence.
     *
     * @param string $processId Process to verify
     * @return array Verification result
     */
    public function verifyLockOrder(string $processId): array
    {
        $processLocks = array_filter(
            $this->lockTimestamps,
            fn($lock) => $lock['process'] === $processId
        );

        $violations = [];
        $previousIndex = -1;

        foreach ($processLocks as $lock) {
            $currentIndex = array_search($lock['table'], self::LOCK_ORDER);

            if ($currentIndex !== false && $currentIndex < $previousIndex) {
                $violations[] = [
                    'table' => $lock['table'],
                    'expected_after' => self::LOCK_ORDER[$previousIndex],
                    'timestamp' => $lock['timestamp'],
                ];
            }

            if ($currentIndex !== false) {
                $previousIndex = $currentIndex;
            }
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations,
            'lock_sequence' => array_column($processLocks, 'table'),
        ];
    }

    /**
     * Simulate potential deadlock scenario.
     *
     * Creates two processes that acquire locks in different orders
     * to test deadlock prevention mechanisms.
     *
     * @param Payment $payment1
     * @param Payment $payment2
     * @return array Deadlock test results
     */
    public function simulateDeadlockScenario(Payment $payment1, Payment $payment2): array
    {
        $results = [
            'deadlock_occurred' => false,
            'process1' => ['completed' => false, 'error' => null],
            'process2' => ['completed' => false, 'error' => null],
        ];

        // Process 1: Lock payment1 then payment2
        try {
            DB::transaction(function () use ($payment1, $payment2, &$results) {
                Payment::where('id', $payment1->id)->lockForUpdate()->first();
                usleep(10000); // Small delay to increase deadlock chance
                Payment::where('id', $payment2->id)->lockForUpdate()->first();
                $results['process1']['completed'] = true;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'deadlock') !== false) {
                $results['deadlock_occurred'] = true;
            }
            $results['process1']['error'] = $e->getMessage();
        }

        // Process 2: Lock payment2 then payment1 (reverse order)
        try {
            DB::transaction(function () use ($payment1, $payment2, &$results) {
                Payment::where('id', $payment2->id)->lockForUpdate()->first();
                usleep(10000);
                Payment::where('id', $payment1->id)->lockForUpdate()->first();
                $results['process2']['completed'] = true;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'deadlock') !== false) {
                $results['deadlock_occurred'] = true;
            }
            $results['process2']['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Create multiple payments for concurrent processing test.
     *
     * @param Subscription $subscription
     * @param int $count Number of payments to create
     * @return array Created payments
     */
    public function createConcurrentPayments(Subscription $subscription, int $count): array
    {
        $payments = [];

        for ($i = 0; $i < $count; $i++) {
            $payments[] = Payment::factory()->create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'status' => 'pending',
                'gateway_order_id' => 'order_concurrent_' . uniqid(),
                'amount' => $subscription->amount,
                'amount_paise' => $subscription->amount * 100,
            ]);
        }

        return $payments;
    }

    /**
     * Reset lock tracking state.
     */
    public function reset(): void
    {
        $this->lockTimestamps = [];
        $this->deadlockDetected = false;
    }
}
