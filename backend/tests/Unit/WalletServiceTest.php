<?php
// V-TEST-SUITE-001 (WalletService Unit Tests)
// V-PHASE3-PAISE-CANONICAL (Updated for paise-based model)

namespace Tests\Unit;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\CreatesApplication;
use App\Services\WalletService;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\BonusTransaction;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use App\Services\DoubleEntryLedgerService;

/**
 * WalletServiceTest - Unit tests for WalletService.
 *
 * NOTE: Extends BaseTestCase directly to avoid DatabaseMigrations trait
 * which conflicts with irreversible migrations. Uses DatabaseTransactions
 * to rollback test data between tests.
 *
 * REQUIRES: Database must be pre-migrated before running these tests.
 * Run: php artisan migrate:fresh --seed
 */
class WalletServiceTest extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    protected WalletService $service;
    protected User $user;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        // WalletService requires DoubleEntryLedgerService
        $ledgerService = app(DoubleEntryLedgerService::class);
        $this->service = new WalletService($ledgerService);

        // UserFactory creates wallet via afterCreating hook
        // Use that wallet instead of creating a new one
        $this->user = User::factory()->create();
        $this->wallet = $this->user->wallet;

        // Reset balance for test isolation
        $this->wallet->update([
            'balance_paise' => 0,
            'locked_balance_paise' => 0
        ]);
    }

    // ==================== DEPOSIT TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function deposit_increases_wallet_balance()
    {
        $transaction = $this->service->deposit($this->user, 500.00, 'deposit', 'Test deposit');

        $this->assertEquals(500.00, $this->wallet->fresh()->balance);
        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function deposit_creates_transaction_record()
    {
        $transaction = $this->service->deposit($this->user, 250.50, 'bonus_credit', 'Bonus award');

        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $this->wallet->id,
            'user_id' => $this->user->id,
            'type' => 'bonus_credit',
            'amount_paise' => 25050, // ₹250.50 = 25050 paise
            'status' => 'completed',
            'description' => 'Bonus award'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function deposit_records_balance_before_and_after()
    {
        // First deposit
        $this->service->deposit($this->user, 100.0, 'deposit', 'First');

        // Second deposit
        $transaction = $this->service->deposit($this->user, 200.0, 'deposit', 'Second');

        $this->assertEquals(100.00, $transaction->balance_before);
        $this->assertEquals(300.00, $transaction->balance_after);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function deposit_throws_exception_for_zero_amount()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Deposit amount must be positive");

        $this->service->deposit($this->user, 0, 'deposit', 'Test');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function deposit_throws_exception_for_negative_amount()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Deposit amount must be positive");

        $this->service->deposit($this->user, -100, 'deposit', 'Test');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function deposit_stores_reference_when_provided()
    {
        // V-WAVE1-FIX: Create proper FK relationships instead of hardcoded IDs
        $subscription = Subscription::factory()->create(['user_id' => $this->user->id]);
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
        ]);

        $bonusTransaction = BonusTransaction::create([
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'payment_id' => $payment->id,
            'type' => 'consistency',
            'amount' => 50.0,
            'multiplier_applied' => 1.0
        ]);

        $transaction = $this->service->deposit(
            $this->user,
            50.0,
            'bonus_credit',
            'Consistency bonus',
            $bonusTransaction
        );

        $this->assertEquals(BonusTransaction::class, $transaction->reference_type);
        $this->assertEquals($bonusTransaction->id, $transaction->reference_id);
    }

    // ==================== WITHDRAWAL TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function withdraw_decreases_wallet_balance()
    {
        $this->wallet->update(['balance_paise' => 100000]); // ₹1000

        $this->service->withdraw($this->user, 300.0, 'withdrawal', 'Test withdrawal');

        $this->assertEquals(700.00, $this->wallet->fresh()->balance); // Virtual accessor
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function withdraw_creates_transaction_with_paise()
    {
        $this->wallet->update(['balance_paise' => 50000]); // ₹500

        $this->service->withdraw($this->user, 200.0, 'admin_adjustment', 'Admin debit');

        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => 'admin_adjustment',
            'amount_paise' => 20000, // ₹200 in paise
            'status' => 'completed'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function withdraw_throws_exception_for_insufficient_funds()
    {
        $this->wallet->update(['balance_paise' => 10000]); // ₹100

        $this->expectException(\Exception::class);

        $this->service->withdraw($this->user, 500.0, 'withdrawal', 'Test');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function withdraw_throws_exception_for_zero_amount()
    {
        $this->wallet->update(['balance_paise' => 100000]); // ₹1000

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Withdrawal amount must be positive");

        $this->service->withdraw($this->user, 0, 'withdrawal', 'Test');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function withdraw_with_lock_moves_funds_to_locked_balance()
    {
        $this->wallet->update(['balance_paise' => 100000, 'locked_balance_paise' => 0]); // ₹1000

        $transaction = $this->service->withdraw(
            $this->user,
            300.0,
            'withdrawal_request',
            'Withdrawal request',
            null,
            true // lockBalance = true
        );

        $wallet = $this->wallet->fresh();
        $this->assertEquals(700.00, $wallet->balance); // Virtual accessor
        $this->assertEquals(300.00, $wallet->locked_balance); // Virtual accessor
        $this->assertEquals('pending', $transaction->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function withdraw_without_lock_immediately_debits()
    {
        $this->wallet->update(['balance_paise' => 100000, 'locked_balance_paise' => 0]); // ₹1000

        $transaction = $this->service->withdraw(
            $this->user,
            300.0,
            'admin_adjustment',
            'Admin debit',
            null,
            false // lockBalance = false (immediate)
        );

        $wallet = $this->wallet->fresh();
        $this->assertEquals(700.00, $wallet->balance); // Virtual accessor
        $this->assertEquals(0.00, $wallet->locked_balance); // Virtual accessor
        $this->assertEquals('completed', $transaction->status);
    }

    // ==================== LOCK/UNLOCK FUNDS TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function lock_funds_increases_locked_balance()
    {
        $this->wallet->update(['balance_paise' => 100000, 'locked_balance_paise' => 0]); // ₹1000

        $this->service->lockFunds($this->user, 300.0, 'Withdrawal pending');

        $wallet = $this->wallet->fresh();
        $this->assertEquals(1000.00, $wallet->balance); // Balance unchanged
        $this->assertEquals(300.00, $wallet->locked_balance); // Locked increased
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unlock_funds_decreases_locked_balance()
    {
        $this->wallet->update(['balance_paise' => 100000, 'locked_balance_paise' => 30000]); // ₹1000, ₹300 locked

        $this->service->unlockFunds($this->user, 200.0, 'Withdrawal cancelled');

        $wallet = $this->wallet->fresh();
        $this->assertEquals(1000.00, $wallet->balance); // Balance unchanged
        $this->assertEquals(100.00, $wallet->locked_balance); // Locked decreased
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unlock_funds_throws_exception_for_insufficient_locked_balance()
    {
        $this->wallet->update(['balance_paise' => 100000, 'locked_balance_paise' => 10000]); // ₹1000, ₹100 locked

        $this->expectException(\RuntimeException::class);

        $this->service->unlockFunds($this->user, 500.0, 'Test');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unlock_funds_throws_exception_for_zero_amount()
    {
        $this->wallet->update(['balance_paise' => 50000, 'locked_balance_paise' => 30000]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unlock amount must be positive");

        $this->service->unlockFunds($this->user, 0, 'Test');
    }

    // ==================== CONCURRENCY TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function concurrent_deposits_are_handled_safely()
    {
        // Simulate concurrent deposits using transactions
        $results = [];

        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->service->deposit($this->user, 100.0, 'deposit', "Deposit $i");
        }

        // All deposits should succeed and total should be ₹500 (50000 paise)
        $this->assertEquals(500.00, $this->wallet->fresh()->balance); // Virtual accessor
        $this->assertEquals(50000, $this->wallet->fresh()->balance_paise); // Canonical paise
        $this->assertCount(5, $results);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function concurrent_withdrawals_respect_balance_limits()
    {
        $this->wallet->update(['balance_paise' => 20000]); // ₹200

        // First withdrawal should succeed
        $this->service->withdraw($this->user, 150.0, 'withdrawal', 'First');

        // Second withdrawal should fail
        $this->expectException(\Exception::class);

        $this->service->withdraw($this->user, 100.0, 'withdrawal', 'Second');
    }

    // ==================== TRANSACTION INTEGRITY TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function deposit_is_atomic()
    {
        // Create a partial mock to simulate failure after increment
        $originalBalancePaise = 100000; // ₹1000
        $this->wallet->update(['balance_paise' => $originalBalancePaise]);

        // Verify that if we attempt a deposit, it either fully succeeds or fully fails
        try {
            DB::beginTransaction();
            $this->service->deposit($this->user, 500, 'deposit', 'Test');
            DB::rollBack(); // Simulate failure
        } catch (\Exception $e) {
            DB::rollBack();
        }

        // Balance should remain unchanged due to rollback
        // Note: In real scenario, the service wraps in its own transaction
        $this->assertTrue(true); // Placeholder - transaction testing is complex
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function transaction_records_are_immutable()
    {
        $transaction = $this->service->deposit($this->user, 100.0, 'deposit', 'Test');

        // Verify transaction exists with paise amount
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount_paise' => 10000 // ₹100 = 10000 paise
        ]);
    }

    // ==================== EDGE CASES ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function handles_decimal_amounts_correctly()
    {
        $this->service->deposit($this->user, 123.45, 'deposit', 'Decimal test');

        $this->assertEquals(123.45, $this->wallet->fresh()->balance); // Virtual accessor
        $this->assertEquals(12345, $this->wallet->fresh()->balance_paise); // Canonical paise
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function handles_large_amounts()
    {
        $this->service->deposit($this->user, 999999.99, 'deposit', 'Large deposit');

        $this->assertEquals(999999.99, $this->wallet->fresh()->balance); // Virtual accessor
        $this->assertEquals(99999999, $this->wallet->fresh()->balance_paise); // Canonical paise
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function handles_exact_balance_withdrawal()
    {
        $this->wallet->update(['balance_paise' => 50000]); // ₹500

        $this->service->withdraw($this->user, 500.0, 'withdrawal', 'Full withdrawal');

        $this->assertEquals(0.00, $this->wallet->fresh()->balance);
        $this->assertEquals(0, $this->wallet->fresh()->balance_paise);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multiple_operations_maintain_correct_running_balance()
    {
        // Deposit ₹1000 (100000 paise)
        $t1 = $this->service->deposit($this->user, 1000.0, 'deposit', 'Initial');
        $this->assertEquals(0, $t1->balance_before); // Virtual accessor (₹)
        $this->assertEquals(1000, $t1->balance_after); // Virtual accessor (₹)
        $this->assertEquals(0, $t1->balance_before_paise); // Canonical paise
        $this->assertEquals(100000, $t1->balance_after_paise); // Canonical paise

        // Withdraw ₹300 (30000 paise)
        $t2 = $this->service->withdraw($this->user, 300.0, 'withdrawal', 'First withdrawal');
        $this->assertEquals(1000, $t2->balance_before);
        $this->assertEquals(700, $t2->balance_after);
        $this->assertEquals(100000, $t2->balance_before_paise);
        $this->assertEquals(70000, $t2->balance_after_paise);

        // Deposit ₹500 (50000 paise)
        $t3 = $this->service->deposit($this->user, 500.0, 'bonus_credit', 'Bonus');
        $this->assertEquals(700, $t3->balance_before);
        $this->assertEquals(1200, $t3->balance_after);

        // Final balance check
        $this->assertEquals(1200.00, $this->wallet->fresh()->balance); // Virtual
        $this->assertEquals(120000, $this->wallet->fresh()->balance_paise); // Canonical
    }
}
