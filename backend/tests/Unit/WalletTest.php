<?php
// V-FINAL-1730-TEST-35

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->wallet = Wallet::create(['user_id' => $this->user->id, 'balance' => 0]);
    }

    /** @test */
    public function test_wallet_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->wallet->user);
        $this->assertEquals($this->user->id, $this->wallet->user->id);
    }

    /** @test */
    public function test_wallet_has_transactions_relationship()
    {
        Transaction::create([
            'wallet_id' => $this->wallet->id,
            'user_id' => $this->user->id,
            'type' => 'deposit',
            'amount' => 100
        ]);

        $this->assertTrue($this->wallet->transactions()->exists());
        $this->assertEquals(1, $this->wallet->transactions->count());
    }

    /** @test */
    public function test_wallet_balance_is_decimal_with_2_places()
    {
        $this->wallet->update(['balance' => 123.456]);
        
        // When retrieved, it should be cast to 2 decimal places
        // Note: The accessor casts to float, but the DB stores as decimal.
        $this->assertEquals('123.46', $this->wallet->fresh()->balance); // Casting to 2dp
    }

    /** @test */
    public function test_wallet_deposit_increases_balance()
    {
        $this->wallet->deposit(500, 'deposit', 'Test Deposit');
        
        $this->assertEquals(500, $this->wallet->fresh()->balance);
        $this->assertDatabaseHas('transactions', ['amount' => 500]);
    }

    /** @test */
    public function test_wallet_withdraw_decreases_balance()
    {
        $this->wallet->deposit(1000, 'deposit', 'Initial');
        $this->wallet->withdraw(300, 'withdrawal', 'Test Withdraw');

        $this->assertEquals(700, $this->wallet->fresh()->balance);
        $this->assertDatabaseHas('transactions', ['amount' => -300]);
    }

    /** @test */
    public function test_wallet_withdraw_validates_sufficient_balance()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient funds");

        $this->wallet->withdraw(100, 'withdrawal', 'Test');
    }

    /** @test */
    public function test_wallet_balance_never_negative()
    {
        // This test verifies the DB-level CHECK constraint from our migration
        
        $this->expectException(QueryException::class);

        // Try to force a negative balance, bypassing the model's logic
        DB::table('wallets')->where('id', $this->wallet->id)->update(['balance' => -50]);
    }

    /** @test */
    public function test_wallet_tracks_total_deposited()
    {
        $this->wallet->deposit(100, 'deposit', 'Test 1');
        $this->wallet->deposit(200, 'bonus_credit', 'Test 2');
        $this->wallet->withdraw(50, 'withdrawal', 'Test 3'); // Should be ignored

        // Accessor should sum deposits
        $this->assertEquals(300, $this->wallet->total_deposited);
    }

    /** @test */
    public function test_wallet_tracks_total_withdrawn()
    {
        $this->wallet->deposit(1000, 'deposit', 'Test 1');
        $this->wallet->withdraw(100, 'withdrawal', 'Test 2');
        $this->wallet->withdraw(200, 'withdrawal', 'Test 3');

        // Accessor should sum withdrawals and return positive value
        $this->assertEquals(300, $this->wallet->total_withdrawn);
    }

    /** @test */
    public function test_wallet_calculates_available_balance()
    {
        $this->wallet->update(['balance' => 500, 'locked_balance' => 100]);
        
        // 'available_balance' accessor is an alias for 'balance'
        $this->assertEquals(500, $this->wallet->available_balance);
    }
}