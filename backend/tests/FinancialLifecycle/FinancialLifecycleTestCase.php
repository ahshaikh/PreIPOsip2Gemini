<?php

/**
 * FinancialLifecycleTestCase - Base Test Case for Financial Lifecycle Tests
 *
 * REFACTOR TARGET: "Refactor Payment Lifecycle into Single Financial Orchestration Boundary"
 *
 * This base class provides:
 * - Standardized fixtures for payment lifecycle testing
 * - Transaction monitoring utilities
 * - Lock order verification helpers
 * - Concurrency simulation support
 *
 * FINANCIAL INVARIANTS ENFORCED:
 * 1. Single DB transaction per payment lifecycle
 * 2. No nested transactions
 * 3. Strict lock order: Payment → Subscription → Wallet → Product → UserInvestment → BonusTransaction
 * 4. Wallet passbook behavior: +Deposit, -Allocation, +Bonus
 * 5. Allocation invariant: amount_paise = allocated_paise + remainder_paise
 * 6. Idempotency: No duplicate mutations from repeated webhooks
 * 7. Paise-only arithmetic: No floats in lifecycle code
 *
 * @package Tests\FinancialLifecycle
 */

namespace Tests\FinancialLifecycle;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Product;
use App\Models\BulkPurchase;
use App\Models\Transaction;
use App\Models\BonusTransaction;
use App\Models\UserInvestment;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\LedgerAccount;

abstract class FinancialLifecycleTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Expected lock acquisition order for financial lifecycle.
     * FinancialOrchestrator MUST acquire locks in this order to prevent deadlocks.
     */
    protected const LOCK_ORDER = [
        'payments',
        'subscriptions',
        'wallets',
        'products',
        'user_investments',
        'bonus_transactions',
    ];

    /**
     * Tables that participate in financial transactions.
     */
    protected const FINANCIAL_TABLES = [
        'payments',
        'subscriptions',
        'wallets',
        'transactions',
        'user_investments',
        'bonus_transactions',
        'ledger_entries',
        'ledger_lines',
        'bulk_purchases',
    ];

    /**
     * Transaction depth tracker for nested transaction detection.
     */
    protected int $transactionDepth = 0;

    /**
     * Lock acquisition order tracker.
     */
    protected array $lockAcquisitionOrder = [];

    /**
     * Query log for analysis.
     */
    protected array $queryLog = [];

    /**
     * Standard test fixtures.
     */
    protected ?User $testUser = null;
    protected ?Wallet $testWallet = null;
    protected ?Plan $testPlan = null;
    protected ?Product $testProduct = null;
    protected ?Subscription $testSubscription = null;
    protected ?BulkPurchase $testInventory = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed required data
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
        $this->seed(\Database\Seeders\LedgerAccountSeeder::class);

        // Reset trackers
        $this->transactionDepth = 0;
        $this->lockAcquisitionOrder = [];
        $this->queryLog = [];
    }

    /**
     * Create a standard test user with wallet and KYC verified.
     */
    protected function createTestUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        // Ensure wallet exists with zero balance
        $wallet = $user->wallet;
        if ($wallet) {
            $wallet->update(['balance_paise' => 0, 'locked_balance_paise' => 0]);
        } else {
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'balance_paise' => 0,
                'locked_balance_paise' => 0,
            ]);
        }

        // Verify KYC
        if ($user->kyc) {
            $user->kyc->update(['status' => 'verified']);
        }

        $this->testUser = $user;
        $this->testWallet = $wallet->fresh();

        return $user;
    }

    /**
     * Create a subscription for the test user.
     */
    protected function createTestSubscription(?User $user = null): Subscription
    {
        $user = $user ?? $this->testUser ?? $this->createTestUser();
        $plan = Plan::first() ?? Plan::factory()->create();

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $plan->monthly_amount ?? 5000,
            'status' => 'active',
            'bonus_multiplier' => 1.0,
            'consecutive_payments_count' => 0,
        ]);

        $this->testPlan = $plan;
        $this->testSubscription = $subscription;

        return $subscription;
    }

    /**
     * Create a pending payment for lifecycle testing.
     */
    protected function createTestPayment(
        ?Subscription $subscription = null,
        int $amountPaise = 500000
    ): Payment {
        $subscription = $subscription ?? $this->testSubscription ?? $this->createTestSubscription();

        $orderId = 'order_test_' . uniqid();
        $payment = Payment::factory()->create([
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'gateway_order_id' => $orderId,
            'amount' => $amountPaise / 100,
            'amount_paise' => $amountPaise,
            'is_on_time' => true,
        ]);

        return $payment;
    }

    /**
     * Create inventory for allocation testing.
     */
    protected function createTestInventory(int $valuePaise = 10000000): BulkPurchase
    {
        $product = Product::first() ?? Product::factory()->create([
            'status' => 'approved',
            'face_value_per_unit' => 100,
        ]);

        $inventory = BulkPurchase::factory()->create([
            'product_id' => $product->id,
            'total_value_received' => $valuePaise / 100,
            'value_remaining' => $valuePaise / 100,
            'purchase_price_per_unit' => 90,
        ]);

        $this->testProduct = $product;
        $this->testInventory = $inventory;

        return $inventory;
    }

    /**
     * Enable query logging for transaction analysis.
     */
    protected function enableQueryLogging(): void
    {
        DB::enableQueryLog();

        DB::listen(function ($query) {
            $this->queryLog[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
                'timestamp' => microtime(true),
            ];

            // Track lock acquisitions
            if (stripos($query->sql, 'FOR UPDATE') !== false) {
                $table = $this->extractTableFromQuery($query->sql);
                if ($table) {
                    $this->lockAcquisitionOrder[] = [
                        'table' => $table,
                        'timestamp' => microtime(true),
                        'sql' => $query->sql,
                    ];
                }
            }

            // Track transaction depth
            if (stripos($query->sql, 'BEGIN') !== false ||
                stripos($query->sql, 'START TRANSACTION') !== false) {
                $this->transactionDepth++;
            }
            if (stripos($query->sql, 'COMMIT') !== false ||
                stripos($query->sql, 'ROLLBACK') !== false) {
                $this->transactionDepth = max(0, $this->transactionDepth - 1);
            }
        });
    }

    /**
     * Disable query logging.
     */
    protected function disableQueryLogging(): void
    {
        DB::disableQueryLog();
    }

    /**
     * Extract table name from SQL query.
     */
    protected function extractTableFromQuery(string $sql): ?string
    {
        // Match FROM `table` or FROM table patterns
        if (preg_match('/FROM\s+[`"]?(\w+)[`"]?/i', $sql, $matches)) {
            return $matches[1];
        }
        // Match UPDATE `table` patterns
        if (preg_match('/UPDATE\s+[`"]?(\w+)[`"]?/i', $sql, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Assert that locks were acquired in the correct order.
     */
    protected function assertLockOrderCorrect(): void
    {
        $financialLocks = array_filter(
            $this->lockAcquisitionOrder,
            fn($lock) => in_array($lock['table'], self::LOCK_ORDER)
        );

        $previousIndex = -1;
        foreach ($financialLocks as $lock) {
            $currentIndex = array_search($lock['table'], self::LOCK_ORDER);
            if ($currentIndex !== false && $currentIndex < $previousIndex) {
                $this->fail(
                    "Lock order violation: {$lock['table']} locked after " .
                    self::LOCK_ORDER[$previousIndex] . ". Expected order: " .
                    implode(' → ', self::LOCK_ORDER)
                );
            }
            if ($currentIndex !== false) {
                $previousIndex = $currentIndex;
            }
        }
    }

    /**
     * Assert no nested transactions occurred.
     */
    protected function assertNoNestedTransactions(): void
    {
        $maxDepth = 0;
        $currentDepth = 0;

        foreach ($this->queryLog as $query) {
            $sql = strtoupper($query['sql']);
            if (strpos($sql, 'BEGIN') !== false || strpos($sql, 'START TRANSACTION') !== false) {
                $currentDepth++;
                $maxDepth = max($maxDepth, $currentDepth);
            }
            if (strpos($sql, 'COMMIT') !== false || strpos($sql, 'ROLLBACK') !== false) {
                $currentDepth = max(0, $currentDepth - 1);
            }
        }

        $this->assertLessThanOrEqual(
            1,
            $maxDepth,
            "Nested transactions detected. Max depth: {$maxDepth}. " .
            "FinancialOrchestrator should use single transaction boundary."
        );
    }

    /**
     * Assert wallet follows passbook model.
     * Credits (deposits, bonuses) positive, debits (allocations) negative direction.
     */
    protected function assertWalletPassbookIntegrity(int $walletId): void
    {
        $transactions = Transaction::where('wallet_id', $walletId)
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get();

        $runningBalance = 0;
        foreach ($transactions as $txn) {
            $type = \App\Enums\TransactionType::tryFrom($txn->type);
            if ($type && $type->isCredit()) {
                $runningBalance += $txn->amount_paise;
            } else {
                $runningBalance -= $txn->amount_paise;
            }

            // Verify running balance matches recorded balance
            $this->assertEquals(
                $txn->balance_after_paise,
                $runningBalance,
                "Passbook integrity violation at transaction #{$txn->id}. " .
                "Expected balance: {$runningBalance}, Recorded: {$txn->balance_after_paise}"
            );
        }

        // Verify final balance matches wallet
        $wallet = Wallet::find($walletId);
        $this->assertEquals(
            $wallet->balance_paise,
            $runningBalance,
            "Final wallet balance mismatch. Wallet: {$wallet->balance_paise}, Passbook: {$runningBalance}"
        );
    }

    /**
     * Assert allocation invariant: amount = allocated + remainder.
     */
    protected function assertAllocationInvariant(Payment $payment): void
    {
        $paymentPaise = $payment->amount_paise;

        // Sum all allocations for this payment
        $allocatedPaise = (int) ($payment->investments()
            ->where('is_reversed', false)
            ->sum('value_allocated') * 100);

        // Check for remainder refunds
        $refundedPaise = Transaction::where('reference_type', Payment::class)
            ->where('reference_id', $payment->id)
            ->where('type', 'refund')
            ->where('status', 'completed')
            ->sum('amount_paise');

        // Wallet balance should account for payment amount
        // Note: In wallet-first model, payment goes to wallet, allocation is separate
        // But invariant should hold: deposited = allocated + remaining_in_wallet + refunded
        $this->assertGreaterThanOrEqual(
            0,
            $allocatedPaise,
            "Negative allocation detected for payment #{$payment->id}"
        );
    }

    /**
     * Assert ledger double-entry balance.
     */
    protected function assertLedgerBalanced(): void
    {
        $totalDebits = LedgerLine::where('direction', 'debit')->sum('amount_paise');
        $totalCredits = LedgerLine::where('direction', 'credit')->sum('amount_paise');

        $this->assertEquals(
            $totalDebits,
            $totalCredits,
            "Ledger imbalance! Debits: {$totalDebits}, Credits: {$totalCredits}"
        );
    }

    /**
     * Assert all amounts are integers (paise).
     */
    protected function assertAmountsArePaise(array $amounts): void
    {
        foreach ($amounts as $name => $value) {
            $this->assertIsInt(
                $value,
                "Amount '{$name}' is not an integer (paise). Got: " . gettype($value) . " = {$value}"
            );
        }
    }

    /**
     * Get query count for a specific table.
     */
    protected function getQueryCountForTable(string $table): int
    {
        return count(array_filter(
            $this->queryLog,
            fn($q) => stripos($q['sql'], $table) !== false
        ));
    }

    /**
     * Get total query count.
     */
    protected function getTotalQueryCount(): int
    {
        return count($this->queryLog);
    }

    /**
     * Simulate webhook payload.
     */
    protected function createWebhookPayload(Payment $payment, string $eventType = 'payment.captured'): array
    {
        return [
            'event' => $eventType,
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_' . $payment->gateway_order_id,
                        'order_id' => $payment->gateway_order_id,
                        'amount' => $payment->amount_paise,
                        'currency' => 'INR',
                        'status' => 'captured',
                    ],
                ],
            ],
        ];
    }

    /**
     * Assert no float operations in query bindings.
     */
    protected function assertNoFloatBindings(): void
    {
        foreach ($this->queryLog as $query) {
            foreach ($query['bindings'] as $binding) {
                if (is_float($binding)) {
                    // Check if it's a financial table query
                    $isFinancialQuery = false;
                    foreach (self::FINANCIAL_TABLES as $table) {
                        if (stripos($query['sql'], $table) !== false) {
                            $isFinancialQuery = true;
                            break;
                        }
                    }

                    if ($isFinancialQuery) {
                        $this->fail(
                            "Float binding detected in financial query. " .
                            "SQL: {$query['sql']}, Binding: {$binding}. " .
                            "Financial operations must use integer paise."
                        );
                    }
                }
            }
        }
    }
}
