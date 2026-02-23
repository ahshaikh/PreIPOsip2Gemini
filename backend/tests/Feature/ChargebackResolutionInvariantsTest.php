<?php
// V-WAVE3-REVERSAL-HARDENING: Invariant tests for ChargebackResolutionService
// V-WAVE3-REVERSAL-AUDIT: Updated to use dedicated receivable table and relational links

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\BulkPurchase;
use App\Models\BonusTransaction;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\ChargebackReceivable;
use App\Services\ChargebackResolutionService;
use App\Services\WalletService;
use App\Services\PaymentWebhookService;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Exceptions\Financial\AccountRecoveryModeException;
use Illuminate\Support\Facades\DB;

class ChargebackResolutionInvariantsTest extends TestCase
{
    protected User $user;
    protected User $admin;
    protected Plan $plan;
    protected Subscription $subscription;
    protected ChargebackResolutionService $chargebackService;
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);

        $this->chargebackService = app(ChargebackResolutionService::class);
        $this->walletService = app(WalletService::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->user->wallet()->create(['balance_paise' => 0]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->plan = Plan::first();

        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => $this->plan->monthly_amount,
            'status' => 'active',
            'consecutive_payments_count' => 0,
            'bonus_multiplier' => 1.0,
        ]);

        // Ensure inventory
        $product = Product::first();
        if ($product) {
            BulkPurchase::factory()->create([
                'product_id' => $product->id,
                'total_value_received' => 10000000,
                'value_remaining' => 10000000,
            ]);
        }
    }

    // =========================================================================
    // INVARIANT TEST 1: User cannot withdraw during recovery mode
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_withdraw_during_recovery_mode()
    {
        // Setup: Give user some balance
        $this->walletService->deposit(
            $this->user,
            10000, // 100 rupees in paise
            'deposit',
            'Test deposit'
        );

        // Manually set recovery mode
        $this->user->wallet->update([
            'is_recovery_mode' => true,
            'receivable_balance_paise' => 5000, // User owes 50 rupees
        ]);

        // Attempt withdrawal - should fail
        $this->expectException(AccountRecoveryModeException::class);
        $this->expectExceptionMessage('Account is in financial recovery mode');

        $this->walletService->withdraw(
            $this->user,
            5000, // 50 rupees in paise
            'withdrawal',
            'Test withdrawal'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_deposit_during_recovery_mode()
    {
        // Setup: Set recovery mode
        $this->user->wallet->update([
            'is_recovery_mode' => true,
            'receivable_balance_paise' => 5000,
        ]);

        // Deposit should succeed
        $transaction = $this->walletService->deposit(
            $this->user,
            10000, // 100 rupees in paise
            'deposit',
            'Test deposit during recovery'
        );

        $this->assertNotNull($transaction);
        $this->assertEquals('completed', $transaction->status);
    }

    // =========================================================================
    // INVARIANT TEST 2: Ledger remains balanced after shortfall scenario
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function ledger_remains_balanced_after_shortfall_scenario()
    {
        // 1. Create and process a payment to generate bonus
        $payment = $this->createAndProcessPayment();

        // 2. Verify initial ledger balance
        $this->assertLedgerBalanced('After payment processing');

        // 3. Spend the bonus (withdraw most of wallet)
        $walletBalance = $this->user->wallet->fresh()->balance_paise;
        if ($walletBalance > 100) {
            // Leave only 1 rupee, spending the rest
            $this->walletService->withdraw(
                $this->user,
                $walletBalance - 100, // Leave 1 rupee
                'investment',
                'Test spending bonus'
            );
        }

        // 4. Verify ledger still balanced
        $this->assertLedgerBalanced('After spending bonus');

        // 5. Process refund (will create shortfall since wallet is near empty)
        $result = $this->chargebackService->resolveRefund(
            $payment,
            'Test refund with shortfall',
            ['reverse_bonuses' => true, 'reverse_allocations' => true, 'refund_payment' => true]
        );

        // 6. Verify ledger remains balanced after shortfall
        $this->assertLedgerBalanced('After refund with shortfall');

        // 7. Verify shortfall was recorded
        if ($result['bonus_shortfall_paise'] > 0) {
            $this->assertTrue($result['account_frozen'], 'Account should be frozen when shortfall exists');
            $this->assertTrue($result['receivable_created'], 'Receivable should be created for shortfall');

            // Verify wallet is in recovery mode
            $this->user->wallet->refresh();
            $this->assertTrue($this->user->wallet->is_recovery_mode);

            // V-WAVE3-REVERSAL-AUDIT: Check receivable in dedicated table
            $this->assertGreaterThan(0, $this->chargebackService->getReceivableBalance($this->user));
        }
    }

    // =========================================================================
    // INVARIANT TEST 3: Refund is idempotent
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function refund_is_idempotent_on_retry()
    {
        // Create and process payment
        $payment = $this->createAndProcessPayment();

        // First refund
        $result1 = $this->chargebackService->resolveRefund(
            $payment->fresh(),
            'Test refund',
            ['reverse_bonuses' => true, 'reverse_allocations' => true, 'refund_payment' => true]
        );

        $this->assertFalse($result1['already_processed'] ?? false);

        // Count reversals after first refund
        $reversalsAfterFirst = BonusTransaction::where('payment_id', $payment->id)
            ->where('type', 'reversal')
            ->count();

        // Second refund (should be idempotent)
        $result2 = $this->chargebackService->resolveRefund(
            $payment->fresh(),
            'Test refund retry',
            ['reverse_bonuses' => true, 'reverse_allocations' => true, 'refund_payment' => true]
        );

        $this->assertTrue($result2['already_processed']);

        // Count reversals after second refund (should be same)
        $reversalsAfterSecond = BonusTransaction::where('payment_id', $payment->id)
            ->where('type', 'reversal')
            ->count();

        $this->assertEquals(
            $reversalsAfterFirst,
            $reversalsAfterSecond,
            'Idempotent refund should not create duplicate reversals'
        );
    }

    // =========================================================================
    // INVARIANT TEST 4: Recovery mode clears when receivable is settled
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function recovery_mode_clears_when_receivable_is_fully_settled()
    {
        // Setup: Put user in recovery mode with receivable in dedicated table
        $receivableAmount = 5000; // 50 rupees

        // Create a payment to link the receivable to
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => 'refunded',
            'amount' => $this->plan->monthly_amount,
        ]);

        $this->user->wallet->update([
            'is_recovery_mode' => true,
        ]);

        // V-WAVE3-REVERSAL-AUDIT: Create receivable in dedicated table
        ChargebackReceivable::create([
            'user_id' => $this->user->id,
            'payment_id' => $payment->id,
            'amount_paise' => $receivableAmount,
            'paid_paise' => 0,
            'balance_paise' => $receivableAmount,
            'status' => ChargebackReceivable::STATUS_PENDING,
            'source_type' => ChargebackReceivable::SOURCE_REFUND,
            'reason' => 'Test receivable',
        ]);

        // Verify recovery mode is active
        $this->assertTrue($this->chargebackService->isInRecoveryMode($this->user));

        // Apply deposit to settle receivable
        $result = $this->chargebackService->applyDepositToReceivable($this->user, $receivableAmount);

        $this->assertEquals($receivableAmount, $result['applied_to_receivable_paise']);
        $this->assertEquals(0, $result['remaining_receivable_paise']);
        $this->assertTrue($result['recovery_mode_cleared']);

        // Verify wallet state
        $this->user->wallet->refresh();
        $this->assertFalse($this->user->wallet->is_recovery_mode);

        // Verify receivable is settled in dedicated table
        $this->assertEquals(0, $this->chargebackService->getReceivableBalance($this->user));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function partial_deposit_reduces_receivable_but_keeps_recovery_mode()
    {
        // Setup: Put user in recovery mode with receivable in dedicated table
        $receivableAmount = 10000; // 100 rupees

        // Create a payment to link the receivable to
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => 'refunded',
            'amount' => $this->plan->monthly_amount,
        ]);

        $this->user->wallet->update([
            'is_recovery_mode' => true,
        ]);

        // V-WAVE3-REVERSAL-AUDIT: Create receivable in dedicated table
        ChargebackReceivable::create([
            'user_id' => $this->user->id,
            'payment_id' => $payment->id,
            'amount_paise' => $receivableAmount,
            'paid_paise' => 0,
            'balance_paise' => $receivableAmount,
            'status' => ChargebackReceivable::STATUS_PENDING,
            'source_type' => ChargebackReceivable::SOURCE_REFUND,
            'reason' => 'Test receivable',
        ]);

        // Apply partial deposit
        $partialDeposit = 4000; // 40 rupees
        $result = $this->chargebackService->applyDepositToReceivable($this->user, $partialDeposit);

        $this->assertEquals($partialDeposit, $result['applied_to_receivable_paise']);
        $this->assertEquals(6000, $result['remaining_receivable_paise']);
        $this->assertFalse($result['recovery_mode_cleared']);

        // Verify wallet still in recovery mode
        $this->user->wallet->refresh();
        $this->assertTrue($this->user->wallet->is_recovery_mode);

        // Verify remaining receivable in dedicated table
        $this->assertEquals(6000, $this->chargebackService->getReceivableBalance($this->user));
    }

    // =========================================================================
    // INVARIANT TEST 5: Cannot clear recovery mode with outstanding receivable
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_clear_recovery_mode_with_outstanding_receivable()
    {
        // Setup: Put user in recovery mode with receivable in dedicated table
        $receivableAmount = 5000;

        // Create a payment to link the receivable to
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => 'refunded',
            'amount' => $this->plan->monthly_amount,
        ]);

        $this->user->wallet->update([
            'is_recovery_mode' => true,
        ]);

        // V-WAVE3-REVERSAL-AUDIT: Create receivable in dedicated table
        ChargebackReceivable::create([
            'user_id' => $this->user->id,
            'payment_id' => $payment->id,
            'amount_paise' => $receivableAmount,
            'paid_paise' => 0,
            'balance_paise' => $receivableAmount,
            'status' => ChargebackReceivable::STATUS_PENDING,
            'source_type' => ChargebackReceivable::SOURCE_REFUND,
            'reason' => 'Test receivable',
        ]);

        // Attempt to clear without forceOverride
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot clear recovery mode: Outstanding receivable');

        $this->chargebackService->clearRecoveryMode($this->user, 'test_reference', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_force_clear_recovery_mode()
    {
        // Setup: Put user in recovery mode with receivable in dedicated table
        $receivableAmount = 5000;

        // Create a payment to link the receivable to
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => 'refunded',
            'amount' => $this->plan->monthly_amount,
        ]);

        $this->user->wallet->update([
            'is_recovery_mode' => true,
        ]);

        // V-WAVE3-REVERSAL-AUDIT: Create receivable in dedicated table
        ChargebackReceivable::create([
            'user_id' => $this->user->id,
            'payment_id' => $payment->id,
            'amount_paise' => $receivableAmount,
            'paid_paise' => 0,
            'balance_paise' => $receivableAmount,
            'status' => ChargebackReceivable::STATUS_PENDING,
            'source_type' => ChargebackReceivable::SOURCE_REFUND,
            'reason' => 'Test receivable',
        ]);

        // Admin force override
        $this->actingAs($this->admin);
        $this->chargebackService->clearRecoveryMode($this->user, 'admin_override_test', true);

        // Verify cleared
        $this->user->wallet->refresh();
        $this->assertFalse($this->user->wallet->is_recovery_mode);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function createAndProcessPayment(): Payment
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => 'pending',
            'gateway_order_id' => 'order_invariant_' . uniqid(),
            'amount' => $this->plan->monthly_amount,
            'is_on_time' => true,
        ]);

        $webhookService = app(PaymentWebhookService::class);
        $webhookService->handleSuccessfulPayment([
            'order_id' => $payment->gateway_order_id,
            'id' => 'pay_invariant_' . uniqid(),
        ]);

        $payment->refresh();

        if ($payment->status === Payment::STATUS_PAID) {
            ProcessSuccessfulPaymentJob::dispatchSync($payment);
        }

        return $payment;
    }

    protected function assertLedgerBalanced(string $context): void
    {
        $totalDebits = LedgerLine::where('direction', 'debit')->sum('amount_paise');
        $totalCredits = LedgerLine::where('direction', 'credit')->sum('amount_paise');

        $this->assertEquals(
            $totalDebits,
            $totalCredits,
            "Ledger imbalanced {$context}! Debits: {$totalDebits}, Credits: {$totalCredits}"
        );
    }
}
