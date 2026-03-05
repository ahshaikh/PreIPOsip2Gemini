<?php

/**
 * PaymentIdempotencyTest
 *
 * INVARIANT: Repeated webhook calls must not duplicate financial mutations.
 *
 * Payment gateways may send the same webhook multiple times due to:
 * - Network timeouts
 * - Retry logic
 * - System restarts
 *
 * The system must handle duplicates gracefully.
 *
 * @package Tests\FinancialLifecycle\Idempotency
 */

namespace Tests\FinancialLifecycle\Idempotency;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use Illuminate\Support\Facades\Cache;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\BonusTransaction;

class PaymentIdempotencyTest extends FinancialLifecycleTestCase
{
    /**
     * Test that duplicate webhooks don't create duplicate deposits.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function duplicate_webhooks_dont_duplicate_deposits(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        $webhookService = app(\App\Services\PaymentWebhookService::class);

        $webhookPayload = [
            'order_id' => $payment->gateway_order_id,
            'id' => 'pay_' . $payment->gateway_order_id,
        ];

        // First webhook
        $webhookService->handleSuccessfulPayment($webhookPayload);

        $this->testWallet->refresh();
        $balanceAfterFirst = $this->testWallet->balance_paise;
        $txnCountAfterFirst = Transaction::where('wallet_id', $this->testWallet->id)->count();

        // Second webhook (duplicate)
        $webhookService->handleSuccessfulPayment($webhookPayload);

        $this->testWallet->refresh();
        $balanceAfterSecond = $this->testWallet->balance_paise;
        $txnCountAfterSecond = Transaction::where('wallet_id', $this->testWallet->id)->count();

        // Third webhook (another duplicate)
        $webhookService->handleSuccessfulPayment($webhookPayload);

        $this->testWallet->refresh();
        $balanceAfterThird = $this->testWallet->balance_paise;
        $txnCountAfterThird = Transaction::where('wallet_id', $this->testWallet->id)->count();

        // ASSERTION: Balance should not increase after first webhook
        $this->assertEquals(
            $balanceAfterFirst,
            $balanceAfterSecond,
            "Balance should not change on duplicate webhook"
        );

        $this->assertEquals(
            $balanceAfterFirst,
            $balanceAfterThird,
            "Balance should not change on third webhook"
        );

        // Transaction count should not increase
        $this->assertEquals(
            $txnCountAfterFirst,
            $txnCountAfterSecond,
            "Transaction count should not increase on duplicate"
        );
    }

    /**
     * Test that duplicate job processing is prevented.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function duplicate_job_processing_prevented(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);
        $payment->update(['status' => Payment::STATUS_PAID]);

        // Process job first time
        \App\Jobs\ProcessSuccessfulPaymentJob::dispatchSync($payment);

        $this->testWallet->refresh();
        $balanceAfterFirst = $this->testWallet->balance_paise;
        $bonusCountFirst = BonusTransaction::where('payment_id', $payment->id)->count();

        // Process job second time (duplicate)
        \App\Jobs\ProcessSuccessfulPaymentJob::dispatchSync($payment);

        $this->testWallet->refresh();
        $balanceAfterSecond = $this->testWallet->balance_paise;
        $bonusCountSecond = BonusTransaction::where('payment_id', $payment->id)->count();

        // ASSERTION: No additional deposits or bonuses
        $this->assertEquals(
            $balanceAfterFirst,
            $balanceAfterSecond,
            "Balance should not change on duplicate job"
        );

        $this->assertEquals(
            $bonusCountFirst,
            $bonusCountSecond,
            "Bonus count should not increase on duplicate job"
        );
    }

    /**
     * Test idempotency key mechanism.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function idempotency_key_prevents_duplicates(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        $idempotencyService = app(\App\Services\IdempotencyService::class);

        $key = "payment_processing:{$payment->id}";
        $executionCount = 0;

        // First execution
        $idempotencyService->executeOnce($key, function () use (&$executionCount) {
            $executionCount++;
            return true;
        });

        // Second execution (should be skipped)
        $idempotencyService->executeOnce($key, function () use (&$executionCount) {
            $executionCount++;
            return true;
        });

        // Third execution (should be skipped)
        $idempotencyService->executeOnce($key, function () use (&$executionCount) {
            $executionCount++;
            return true;
        });

        $this->assertEquals(
            1,
            $executionCount,
            "Operation should only execute once despite multiple calls"
        );
    }

    /**
     * Test that different payments can be processed.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function different_payments_processed_independently(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();

        $payment1 = $this->createTestPayment($subscription);
        $payment2 = $this->createTestPayment($subscription);

        // Both payments should be processed independently
        $this->processPaymentLifecycle($payment1);
        $this->processPaymentLifecycle($payment2);

        $this->testWallet->refresh();

        // Verify both deposits were credited
        $depositTxns = Transaction::where('wallet_id', $this->testWallet->id)
            ->where('type', 'deposit')
            ->count();

        $this->assertEquals(
            2,
            $depositTxns,
            "Two different payments should create two deposits"
        );
    }

    /**
     * Test idempotency survives across restarts.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function idempotency_survives_restart(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        // Process first time
        $this->processPaymentLifecycle($payment);

        $this->testWallet->refresh();
        $balanceAfterFirst = $this->testWallet->balance_paise;

        // Clear cache to simulate restart
        Cache::flush();

        // Process again (idempotency should be in database, not just cache)
        $this->processPaymentLifecycle($payment);

        $this->testWallet->refresh();
        $balanceAfterSecond = $this->testWallet->balance_paise;

        // Balance should be same (idempotency preserved)
        $this->assertEquals(
            $balanceAfterFirst,
            $balanceAfterSecond,
            "Idempotency should be preserved even after cache clear"
        );
    }

    /**
     * Test partial failure doesn't mark as complete.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function partial_failure_allows_retry(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        $initialBalance = $this->testWallet->balance_paise;

        // Simulate partial failure (e.g., bonus calculation fails)
        try {
            // Mock to fail on first attempt
            $this->mock(\App\Services\BonusCalculatorService::class, function ($mock) {
                $mock->shouldReceive('calculateAndAwardBonuses')
                    ->once()
                    ->andThrow(new \RuntimeException('Simulated failure'));
            });

            $this->processPaymentLifecycle($payment);
        } catch (\Throwable $e) {
            // Expected failure
        }

        // Clear mock for retry
        \Mockery::close();

        // Retry should work (not blocked by idempotency)
        // This depends on implementation - partial failure handling
        $this->markTestIncomplete(
            "Test partial failure retry behavior. " .
            "Implementation should either rollback completely or allow retry."
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
