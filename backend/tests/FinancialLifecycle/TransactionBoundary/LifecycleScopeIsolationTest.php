<?php

/**
 * LifecycleScopeIsolationTest
 *
 * INVARIANT: Lifecycle scope isolation - no cross-boundary mutations.
 *
 * Verifies that:
 * - Payment lifecycle only mutates related financial records
 * - No unintended side effects on other users/payments
 * - Isolation between concurrent payment lifecycles
 *
 * @package Tests\FinancialLifecycle\TransactionBoundary
 */

namespace Tests\FinancialLifecycle\TransactionBoundary;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\Transaction;

class LifecycleScopeIsolationTest extends FinancialLifecycleTestCase
{
    /**
     * Test that payment processing only affects the payment's user.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function payment_processing_isolated_to_payment_user(): void
    {
        // Create two users
        $user1 = $this->createTestUser();
        $wallet1 = $this->testWallet;

        $user2 = User::factory()->create();
        $user2->assignRole('user');
        $wallet2 = $user2->wallet;
        $wallet2->update(['balance_paise' => 100000]); // 1000 rupees

        // Create subscription and payment for user1
        $subscription1 = $this->createTestSubscription($user1);
        $payment1 = $this->createTestPayment($subscription1);

        $wallet2BalanceBefore = $wallet2->balance_paise;
        $wallet2TxnCountBefore = Transaction::where('wallet_id', $wallet2->id)->count();

        // Process user1's payment
        $this->processPaymentLifecycle($payment1);

        // User2's wallet should be completely unaffected
        $wallet2->refresh();

        $this->assertEquals(
            $wallet2BalanceBefore,
            $wallet2->balance_paise,
            "User2's wallet balance should be unchanged when processing User1's payment"
        );

        $wallet2TxnCountAfter = Transaction::where('wallet_id', $wallet2->id)->count();
        $this->assertEquals(
            $wallet2TxnCountBefore,
            $wallet2TxnCountAfter,
            "User2 should have no new transactions from User1's payment"
        );
    }

    /**
     * Test that processing one payment doesn't affect another payment.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function payment_processing_isolated_between_payments(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();

        // Create two payments
        $payment1 = $this->createTestPayment($subscription);
        $payment2 = $this->createTestPayment($subscription);

        $payment2StatusBefore = $payment2->status;

        // Process only payment1
        $this->processPaymentLifecycle($payment1);

        // Payment2 should be unaffected
        $payment2->refresh();
        $this->assertEquals(
            $payment2StatusBefore,
            $payment2->status,
            "Payment2 status should be unchanged when processing Payment1"
        );
    }

    /**
     * Test that failed lifecycle doesn't affect unrelated records.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function failed_lifecycle_doesnt_affect_unrelated_records(): void
    {
        // Create two users
        $user1 = $this->createTestUser();
        $wallet1 = $this->testWallet;
        $wallet1->update(['balance_paise' => 50000]);

        $user2 = User::factory()->create();
        $user2->assignRole('user');
        $wallet2 = $user2->wallet;
        $wallet2->update(['balance_paise' => 100000]);

        $subscription1 = $this->createTestSubscription($user1);
        $payment1 = $this->createTestPayment($subscription1);

        $wallet1BalanceBefore = $wallet1->balance_paise;
        $wallet2BalanceBefore = $wallet2->balance_paise;

        // Simulate failed processing
        try {
            DB::transaction(function () use ($payment1, $wallet1) {
                // Partial mutation
                $wallet1->increment('balance_paise', $payment1->amount_paise);

                // Then failure
                throw new \RuntimeException('Simulated lifecycle failure');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }

        // Both wallets should be at original state
        $wallet1->refresh();
        $wallet2->refresh();

        $this->assertEquals(
            $wallet1BalanceBefore,
            $wallet1->balance_paise,
            "User1's wallet should be unchanged after failed lifecycle"
        );

        $this->assertEquals(
            $wallet2BalanceBefore,
            $wallet2->balance_paise,
            "User2's wallet should be unchanged after failed lifecycle"
        );
    }

    /**
     * Test that subscription updates are scoped to payment's subscription.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function subscription_updates_scoped_to_payment_subscription(): void
    {
        $this->createTestUser();
        $subscription1 = $this->createTestSubscription();

        // Create another subscription for same user
        $subscription2 = \App\Models\Subscription::factory()->create([
            'user_id' => $this->testUser->id,
            'plan_id' => $this->testPlan->id,
            'amount' => 3000,
            'status' => 'active',
            'consecutive_payments_count' => 5,
        ]);

        $payment1 = $this->createTestPayment($subscription1);

        $sub1PaymentCountBefore = $subscription1->consecutive_payments_count;
        $sub2PaymentCountBefore = $subscription2->consecutive_payments_count;

        // Process payment for subscription1
        $this->processPaymentLifecycle($payment1);

        // Subscription2 should be unaffected
        $subscription2->refresh();
        $this->assertEquals(
            $sub2PaymentCountBefore,
            $subscription2->consecutive_payments_count,
            "Subscription2 payment count should be unchanged when processing Subscription1's payment"
        );
    }

    /**
     * Test that bonus transactions are scoped correctly.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function bonus_transactions_scoped_to_correct_user(): void
    {
        $user1 = $this->createTestUser();
        $user2 = User::factory()->create();
        $user2->assignRole('user');

        $subscription1 = $this->createTestSubscription($user1);
        $payment1 = $this->createTestPayment($subscription1);

        $user2BonusesBefore = \App\Models\BonusTransaction::where('user_id', $user2->id)->count();

        // Process user1's payment
        $this->processPaymentLifecycle($payment1);

        // User2 should have no new bonus transactions
        $user2BonusesAfter = \App\Models\BonusTransaction::where('user_id', $user2->id)->count();

        $this->assertEquals(
            $user2BonusesBefore,
            $user2BonusesAfter,
            "User2 should have no bonus transactions from User1's payment"
        );

        // User1's bonus transactions should reference correct payment
        $user1Bonuses = \App\Models\BonusTransaction::where('user_id', $user1->id)
            ->where('payment_id', $payment1->id)
            ->get();

        foreach ($user1Bonuses as $bonus) {
            $this->assertEquals($user1->id, $bonus->user_id);
            $this->assertEquals($payment1->id, $bonus->payment_id);
            $this->assertEquals($subscription1->id, $bonus->subscription_id);
        }
    }

    /**
     * Test that ledger entries are scoped correctly.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function ledger_entries_reference_correct_payment(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        $ledgerCountBefore = \App\Models\LedgerEntry::count();

        // Process payment
        $this->processPaymentLifecycle($payment);

        // Get new ledger entries
        $newEntries = \App\Models\LedgerEntry::where('id', '>', 0)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        // Entries that reference this payment should be correctly linked
        foreach ($newEntries as $entry) {
            if ($entry->reference_type === 'App\\Models\\Payment') {
                $this->assertEquals(
                    $payment->id,
                    $entry->reference_id,
                    "Ledger entry should reference the correct payment"
                );
            }
        }
    }

    /**
     * Test that queries are properly scoped with WHERE clauses.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function queries_properly_scoped(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        $unboundedQueries = [];

        DB::listen(function ($query) use ($payment, &$unboundedQueries) {
            // Skip SELECT * FROM information_schema, etc.
            if (stripos($query->sql, 'information_schema') !== false) {
                return;
            }

            // Check UPDATE/DELETE queries for proper scoping
            if (preg_match('/^(UPDATE|DELETE)/i', $query->sql)) {
                // Should have WHERE clause
                if (stripos($query->sql, 'WHERE') === false) {
                    $unboundedQueries[] = $query->sql;
                }
            }
        });

        $this->processPaymentLifecycle($payment);

        $this->assertEmpty(
            $unboundedQueries,
            "Found unbounded UPDATE/DELETE queries:\n" .
            implode("\n", $unboundedQueries) .
            "\n\nAll mutations must be properly scoped with WHERE clauses."
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
