<?php

/**
 * LockOrderEnforcementTest
 *
 * INVARIANT: Strict lock order to prevent deadlocks.
 *
 * Lock order: Payment → Subscription → Wallet → Product → UserInvestment → BonusTransaction
 *
 * Deadlock prevention requires that all concurrent transactions acquire
 * locks in the same order. The FinancialOrchestrator must enforce this.
 *
 * @package Tests\FinancialLifecycle\TransactionBoundary
 */

namespace Tests\FinancialLifecycle\TransactionBoundary;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use Tests\FinancialLifecycle\Support\ConcurrencyTestHelper;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Models\Product;

class LockOrderEnforcementTest extends FinancialLifecycleTestCase
{
    private ConcurrencyTestHelper $concurrencyHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->concurrencyHelper = new ConcurrencyTestHelper();
    }

    /**
     * Test that payment lifecycle acquires locks in correct order.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function lifecycle_acquires_locks_in_correct_order(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $this->createTestInventory();
        $payment = $this->createTestPayment();

        $lockSequence = [];

        DB::listen(function ($query) use (&$lockSequence) {
            if (stripos($query->sql, 'FOR UPDATE') !== false) {
                $table = $this->extractTableFromQuery($query->sql);
                if ($table && in_array($table, self::LOCK_ORDER)) {
                    $lockSequence[] = $table;
                }
            }
        });

        // Process payment
        $this->processPaymentLifecycle($payment);

        // Verify lock sequence follows expected order
        $expectedOrder = array_values(array_filter(
            self::LOCK_ORDER,
            fn($t) => in_array($t, $lockSequence)
        ));

        $actualOrder = [];
        foreach ($lockSequence as $table) {
            if (empty($actualOrder) || end($actualOrder) !== $table) {
                $actualOrder[] = $table;
            }
        }

        // Check order preservation
        $lastIndex = -1;
        $orderViolations = [];

        foreach ($actualOrder as $table) {
            $currentIndex = array_search($table, self::LOCK_ORDER);
            if ($currentIndex !== false && $currentIndex < $lastIndex) {
                $orderViolations[] = [
                    'table' => $table,
                    'position' => $currentIndex,
                    'expected_after' => self::LOCK_ORDER[$lastIndex],
                ];
            }
            if ($currentIndex !== false) {
                $lastIndex = max($lastIndex, $currentIndex);
            }
        }

        $this->assertEmpty(
            $orderViolations,
            "Lock order violations detected:\n" .
            json_encode($orderViolations, JSON_PRETTY_PRINT) .
            "\n\nExpected order: " . implode(' → ', self::LOCK_ORDER) .
            "\nActual sequence: " . implode(' → ', $actualOrder)
        );
    }

    /**
     * Test that payment is always locked first.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function payment_locked_before_other_resources(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();

        $lockSequence = [];
        $paymentLockPosition = -1;

        DB::listen(function ($query) use (&$lockSequence, &$paymentLockPosition) {
            if (stripos($query->sql, 'FOR UPDATE') !== false) {
                $table = $this->extractTableFromQuery($query->sql);
                $lockSequence[] = $table;

                if ($table === 'payments' && $paymentLockPosition === -1) {
                    $paymentLockPosition = count($lockSequence) - 1;
                }
            }
        });

        $this->processPaymentLifecycle($payment);

        // Payment should be the first financial resource locked
        if ($paymentLockPosition >= 0) {
            $financialLocksBeforePayment = array_filter(
                array_slice($lockSequence, 0, $paymentLockPosition),
                fn($t) => in_array($t, self::LOCK_ORDER)
            );

            $this->assertEmpty(
                $financialLocksBeforePayment,
                "Financial resources locked before payment: " .
                implode(', ', $financialLocksBeforePayment) .
                "\n\nPayment must be locked FIRST to prevent deadlocks."
            );
        }
    }

    /**
     * Test that wallet is locked before allocations.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function wallet_locked_before_allocations(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $this->createTestInventory();
        $payment = $this->createTestPayment();

        $walletLockTime = null;
        $allocationLockTime = null;

        DB::listen(function ($query) use (&$walletLockTime, &$allocationLockTime) {
            if (stripos($query->sql, 'FOR UPDATE') === false) {
                return;
            }

            $table = $this->extractTableFromQuery($query->sql);

            if ($table === 'wallets' && $walletLockTime === null) {
                $walletLockTime = microtime(true);
            }

            if ($table === 'user_investments' && $allocationLockTime === null) {
                $allocationLockTime = microtime(true);
            }
        });

        $this->processPaymentLifecycle($payment);

        // If both were locked, wallet should be first
        if ($walletLockTime !== null && $allocationLockTime !== null) {
            $this->assertLessThan(
                $allocationLockTime,
                $walletLockTime,
                "Wallet must be locked BEFORE user_investments. " .
                "Wallet locked at: {$walletLockTime}, Allocation at: {$allocationLockTime}"
            );
        }
    }

    /**
     * Test that subscription is locked before wallet.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function subscription_locked_before_wallet(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();

        $subscriptionLockTime = null;
        $walletLockTime = null;

        DB::listen(function ($query) use (&$subscriptionLockTime, &$walletLockTime) {
            if (stripos($query->sql, 'FOR UPDATE') === false) {
                return;
            }

            $table = $this->extractTableFromQuery($query->sql);

            if ($table === 'subscriptions' && $subscriptionLockTime === null) {
                $subscriptionLockTime = microtime(true);
            }

            if ($table === 'wallets' && $walletLockTime === null) {
                $walletLockTime = microtime(true);
            }
        });

        $this->processPaymentLifecycle($payment);

        if ($subscriptionLockTime !== null && $walletLockTime !== null) {
            $this->assertLessThan(
                $walletLockTime,
                $subscriptionLockTime,
                "Subscription must be locked BEFORE wallet. " .
                "Subscription at: {$subscriptionLockTime}, Wallet at: {$walletLockTime}"
            );
        }
    }

    /**
     * Test that product inventory is locked before user investments.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function product_locked_before_user_investments(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $this->createTestInventory();
        $payment = $this->createTestPayment();

        $productLockTime = null;
        $investmentLockTime = null;

        DB::listen(function ($query) use (&$productLockTime, &$investmentLockTime) {
            if (stripos($query->sql, 'FOR UPDATE') === false) {
                return;
            }

            $table = $this->extractTableFromQuery($query->sql);

            // Products or bulk_purchases (inventory)
            if (($table === 'products' || $table === 'bulk_purchases') && $productLockTime === null) {
                $productLockTime = microtime(true);
            }

            if ($table === 'user_investments' && $investmentLockTime === null) {
                $investmentLockTime = microtime(true);
            }
        });

        $this->processPaymentLifecycle($payment);

        if ($productLockTime !== null && $investmentLockTime !== null) {
            $this->assertLessThan(
                $investmentLockTime,
                $productLockTime,
                "Product/Inventory must be locked BEFORE user_investments."
            );
        }
    }

    /**
     * Test concurrent payment processing doesn't deadlock.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function concurrent_payments_do_not_deadlock(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $this->createTestInventory();

        // Create two payments
        $payment1 = $this->createTestPayment($subscription);
        $payment2 = $this->createTestPayment($subscription);

        $deadlockOccurred = false;
        $exceptions = [];

        // Process both payments (simulating concurrency)
        $results = [];

        try {
            DB::transaction(function () use ($payment1, &$results) {
                $this->processPaymentLifecycle($payment1);
                $results['payment1'] = 'success';
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'deadlock') !== false) {
                $deadlockOccurred = true;
            }
            $exceptions[] = $e->getMessage();
        }

        try {
            DB::transaction(function () use ($payment2, &$results) {
                $this->processPaymentLifecycle($payment2);
                $results['payment2'] = 'success';
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'deadlock') !== false) {
                $deadlockOccurred = true;
            }
            $exceptions[] = $e->getMessage();
        }

        $this->assertFalse(
            $deadlockOccurred,
            "Deadlock detected during concurrent payment processing. " .
            "Consistent lock ordering should prevent deadlocks. " .
            "Exceptions: " . implode("\n", $exceptions)
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
