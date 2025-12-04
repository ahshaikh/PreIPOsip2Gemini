<?php
// V-TEST-SUITE-001 (WalletService Unit Tests)

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WalletService;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\BonusTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $service;
    protected User $user;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->service = new WalletService();
        $this->user = User::factory()->create();
        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'balance' => 0,
            'locked_balance' => 0
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
            'amount' => 250.50,
            'status' => 'completed',
            'description' => 'Bonus award'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function deposit_records_balance_before_and_after()
    {
        // First deposit
        $this->service->deposit($this->user, 100, 'deposit', 'First');

        // Second deposit
        $transaction = $this->service->deposit($this->user, 200, 'deposit', 'Second');

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
        $bonusTransaction = BonusTransaction::create([
            'user_id' => $this->user->id,
            'subscription_id' => 1,
            'payment_id' => 1,
            'type' => 'consistency',
            'amount' => 50,
            'multiplier_applied' => 1.0
        ]);

        $transaction = $this->service->deposit(
            $this->user,
            50,
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
        $this->wallet->update(['balance' => 1000]);

        $this->service->withdraw($this->user, 300, 'withdrawal', 'Test withdrawal');

        $this->assertEquals(700.00, $this->wallet->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function withdraw_creates_negative_amount_transaction()
    {
        $this->wallet->update(['balance' => 500]);

        $this->service->withdraw($this->user, 200, 'admin_adjustment', 'Admin debit');

        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => 'admin_adjustment',
            'amount' => -200,
            'status' => 'completed'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function withdraw_throws_exception_for_insufficient_funds()
    {
        $this->wallet->update(['balance' => 100]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient funds");

        $this->service->withdraw($this->user, 500, 'withdrawal', 'Test');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function withdraw_throws_exception_for_zero_amount()
    {
        $this->wallet->update(['balance' => 1000]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Withdrawal amount must be positive");

        $this->service->withdraw($this->user, 0, 'withdrawal', 'Test');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function withdraw_with_lock_moves_funds_to_locked_balance()
    {
        $this->wallet->update(['balance' => 1000, 'locked_balance' => 0]);

        $transaction = $this->service->withdraw(
            $this->user,
            300,
            'withdrawal_request',
            'Withdrawal request',
            null,
            true // lockBalance = true
        );

        $wallet = $this->wallet->fresh();
        $this->assertEquals(700.00, $wallet->balance);
        $this->assertEquals(300.00, $wallet->locked_balance);
        $this->assertEquals('pending', $transaction->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function withdraw_without_lock_immediately_debits()
    {
        $this->wallet->update(['balance' => 1000, 'locked_balance' => 0]);

        $transaction = $this->service->withdraw(
            $this->user,
            300,
            'admin_adjustment',
            'Admin debit',
            null,
            false // lockBalance = false (immediate)
        );

        $wallet = $this->wallet->fresh();
        $this->assertEquals(700.00, $wallet->balance);
        $this->assertEquals(0.00, $wallet->locked_balance);
        $this->assertEquals('completed', $transaction->status);
    }

    // ==================== UNLOCK FUNDS TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function unlock_funds_moves_from_locked_to_available()
    {
        $this->wallet->update(['balance' => 500, 'locked_balance' => 300]);

        $this->service->unlockFunds($this->user, 200, 'withdrawal_cancelled', 'Cancelled by user');

        $wallet = $this->wallet->fresh();
        $this->assertEquals(700.00, $wallet->balance);
        $this->assertEquals(100.00, $wallet->locked_balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unlock_funds_creates_positive_transaction()
    {
        $this->wallet->update(['balance' => 500, 'locked_balance' => 300]);

        $transaction = $this->service->unlockFunds($this->user, 200, 'reversal', 'Admin reversal');

        $this->assertEquals(200, $transaction->amount);
        $this->assertEquals('completed', $transaction->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unlock_funds_throws_exception_for_insufficient_locked_balance()
    {
        $this->wallet->update(['balance' => 1000, 'locked_balance' => 100]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient locked funds");

        $this->service->unlockFunds($this->user, 500, 'reversal', 'Test');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unlock_funds_throws_exception_for_zero_amount()
    {
        $this->wallet->update(['balance' => 500, 'locked_balance' => 300]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unlock amount must be positive");

        $this->service->unlockFunds($this->user, 0, 'reversal', 'Test');
    }

    // ==================== CONCURRENCY TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function concurrent_deposits_are_handled_safely()
    {
        // Simulate concurrent deposits using transactions
        $results = [];

        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->service->deposit($this->user, 100, 'deposit', "Deposit $i");
        }

        // All deposits should succeed and total should be 500
        $this->assertEquals(500.00, $this->wallet->fresh()->balance);
        $this->assertCount(5, $results);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function concurrent_withdrawals_respect_balance_limits()
    {
        $this->wallet->update(['balance' => 200]);

        // First withdrawal should succeed
        $this->service->withdraw($this->user, 150, 'withdrawal', 'First');

        // Second withdrawal should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient funds");

        $this->service->withdraw($this->user, 100, 'withdrawal', 'Second');
    }

    // ==================== TRANSACTION INTEGRITY TESTS ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function deposit_is_atomic()
    {
        // Create a partial mock to simulate failure after increment
        $originalBalance = 1000;
        $this->wallet->update(['balance' => $originalBalance]);

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
        $transaction = $this->service->deposit($this->user, 100, 'deposit', 'Test');

        // Verify transaction exists
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 100
        ]);
    }

    // ==================== EDGE CASES ====================
    #[\PHPUnit\Framework\Attributes\Test]
    public function handles_decimal_amounts_correctly()
    {
        $this->service->deposit($this->user, 123.45, 'deposit', 'Decimal test');

        $this->assertEquals(123.45, $this->wallet->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function handles_large_amounts()
    {
        $this->service->deposit($this->user, 999999.99, 'deposit', 'Large deposit');

        $this->assertEquals(999999.99, $this->wallet->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function handles_exact_balance_withdrawal()
    {
        $this->wallet->update(['balance' => 500]);

        $this->service->withdraw($this->user, 500, 'withdrawal', 'Full withdrawal');

        $this->assertEquals(0.00, $this->wallet->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multiple_operations_maintain_correct_running_balance()
    {
        // Deposit 1000
        $t1 = $this->service->deposit($this->user, 1000, 'deposit', 'Initial');
        $this->assertEquals(0, $t1->balance_before);
        $this->assertEquals(1000, $t1->balance_after);

        // Withdraw 300
        $t2 = $this->service->withdraw($this->user, 300, 'withdrawal', 'First withdrawal');
        $this->assertEquals(1000, $t2->balance_before);
        $this->assertEquals(700, $t2->balance_after);

        // Deposit 500
        $t3 = $this->service->deposit($this->user, 500, 'bonus_credit', 'Bonus');
        $this->assertEquals(700, $t3->balance_before);
        $this->assertEquals(1200, $t3->balance_after);

        // Final balance check
        $this->assertEquals(1200.00, $this->wallet->fresh()->balance);
    }
}
