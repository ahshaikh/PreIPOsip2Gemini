<?php
// V-FINAL-1730-TEST-36

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->wallet = Wallet::create(['user_id' => $this->user->id, 'balance' => 1000]);
    }

    /** @test */
    public function test_transaction_belongs_to_wallet()
    {
        $txn = $this->wallet->deposit(100, 'deposit', 'test');
        
        $this->assertInstanceOf(Wallet::class, $txn->wallet);
        $this->assertEquals($this->wallet->id, $txn->wallet->id);
    }

    /** @test */
    public function test_transaction_type_enum_validates()
    {
        // This confirms we can store valid types
        $txn = $this->wallet->deposit(10, 'deposit', 'test');
        $this->assertEquals('deposit', $txn->fresh()->type);

        $txn2 = $this->wallet->withdraw(10, 'withdrawal', 'test');
        $this->assertEquals('withdrawal', $txn2->fresh()->type);
    }

    /** @test */
    public function test_transaction_status_enum_validates()
    {
        // Test default
        $txn = $this->wallet->deposit(10, 'deposit', 'test');
        $this->assertEquals('completed', $txn->fresh()->status);

        // Test non-default
        $txn->update(['status' => 'pending']);
        $this->assertEquals('pending', $txn->fresh()->status);
    }

    /** @test */
    public function test_transaction_tracks_before_balance()
    {
        $this->assertEquals(1000, $this->wallet->balance);
        
        $txn = $this->wallet->deposit(500, 'deposit', 'test');

        // Balance *before* this transaction was 1000
        $this->assertEquals(1000, $txn->balance_before);
    }

    /** @test */
    public function test_transaction_tracks_after_balance()
    {
        $this->assertEquals(1000, $this->wallet->balance);
        
        $txn = $this->wallet->deposit(500, 'deposit', 'test');

        // Balance *after* this transaction is 1500
        $this->assertEquals(1500, $txn->balance_after);
    }

    /** @test */
    public function test_transaction_has_unique_transaction_id()
    {
        $txn1 = $this->wallet->deposit(10, 'deposit', 'test');
        $this->assertNotNull($txn1->transaction_id);
        $this->assertTrue(Str::isUuid($txn1->transaction_id));

        // Test uniqueness constraint
        $txn2 = $this->wallet->deposit(10, 'deposit', 'test');

        $this->expectException(QueryException::class);
        // Force a duplicate UUID
        DB::table('transactions')->where('id', $txn2->id)->update(['transaction_id' => $txn1->transaction_id]);
    }

    /** @test */
    public function test_transaction_scope_completed_filters_correctly()
    {
        $this->wallet->deposit(10, 'deposit', 'test'); // Status: completed
        Transaction::factory()->create(['wallet_id' => $this->wallet->id, 'status' => 'pending']);
        
        $this->assertEquals(1, Transaction::completed()->count());
    }

    /** @test */
    public function test_transaction_scope_pending_filters_correctly()
    {
        $this->wallet->deposit(10, 'deposit', 'test'); // Status: completed
        Transaction::factory()->create(['wallet_id' => $this->wallet->id, 'status' => 'pending']);
        
        $this->assertEquals(1, Transaction::pending()->count());
    }
}