<?php
// V-FINAL-1730-TEST-35
// V-PHASE3-PAISE-CANONICAL (Updated for paise-based model)

namespace Tests\Unit;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\CreatesApplication;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Exceptions\Financial\InsufficientBalanceException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * WalletTest - Unit tests for Wallet model domain methods.
 *
 * NOTE: Extends BaseTestCase directly to avoid DatabaseMigrations trait
 * which conflicts with irreversible migrations. Uses DatabaseTransactions
 * to rollback test data between tests.
 *
 * REQUIRES: Database must be pre-migrated before running these tests.
 * Run: php artisan migrate:fresh --seed
 */
class WalletTest extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    protected $user;
    protected $wallet;

    protected function setUp(): void
    {
        parent::setUp();

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

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_wallet_belongs_to_user()
    {
        $this->assertInstanceOf(User::class, $this->wallet->user);
        $this->assertEquals($this->user->id, $this->wallet->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_wallet_has_transactions_relationship()
    {
        // Use deposit method which creates proper transaction records
        // bypass compliance for unit tests
        $this->wallet->deposit(100, TransactionType::BONUS_CREDIT, 'Test', null, true);

        $this->assertTrue($this->wallet->transactions()->exists());
        $this->assertEquals(1, $this->wallet->transactions->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_wallet_balance_paise_stores_integer()
    {
        $this->wallet->update(['balance_paise' => 12346]); // ₹123.46 in paise

        // Canonical storage is integer paise
        $this->assertEquals(12346, $this->wallet->fresh()->balance_paise);
        // Virtual accessor returns rupees
        $this->assertEquals(123.46, $this->wallet->fresh()->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_wallet_deposit_increases_balance()
    {
        // Use BONUS_CREDIT to bypass compliance (deposit type requires KYC)
        $this->wallet->deposit(500, TransactionType::BONUS_CREDIT, 'Test Deposit', null, true);

        $this->assertEquals(500, $this->wallet->fresh()->balance);
        // Assert using paise (500 rupees = 50000 paise)
        $this->assertDatabaseHas('transactions', ['amount_paise' => 50000]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_wallet_withdraw_decreases_balance()
    {
        // Use BONUS_CREDIT to bypass compliance for initial deposit
        $this->wallet->deposit(1000, TransactionType::BONUS_CREDIT, 'Initial', null, true);
        $this->wallet->withdraw(300, TransactionType::WITHDRAWAL, 'Test Withdraw');

        $this->assertEquals(700, $this->wallet->fresh()->balance);
        // Transactions store positive amounts (direction determined by type)
        // 300 rupees = 30000 paise
        $this->assertDatabaseHas('transactions', [
            'amount_paise' => 30000,
            'type' => 'withdrawal'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_wallet_withdraw_validates_sufficient_balance()
    {
        $this->expectException(InsufficientBalanceException::class);

        $this->wallet->withdraw(100, TransactionType::WITHDRAWAL, 'Test');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_wallet_balance_paise_never_negative()
    {
        // Test that withdraw throws exception when trying to create negative balance
        // (Service-level invariant enforcement, not DB constraint)
        $this->wallet->update(['balance_paise' => 1000]); // ₹10

        $this->expectException(InsufficientBalanceException::class);

        // Try to withdraw more than available
        $this->wallet->withdraw(100, TransactionType::WITHDRAWAL, 'Test'); // ₹100
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_wallet_tracks_total_deposited()
    {
        // Use bypass compliance for test deposits
        $this->wallet->deposit(100, TransactionType::BONUS_CREDIT, 'Test 1', null, true);
        $this->wallet->deposit(200, TransactionType::BONUS_CREDIT, 'Test 2', null, true);
        $this->wallet->withdraw(50, TransactionType::WITHDRAWAL, 'Test 3'); // Should be ignored

        // Accessor should sum deposits (credit transactions only)
        $this->assertEquals(300, $this->wallet->fresh()->total_deposited);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_wallet_tracks_total_withdrawn()
    {
        // Use bypass compliance for test deposit
        $this->wallet->deposit(1000, TransactionType::BONUS_CREDIT, 'Test 1', null, true);
        $this->wallet->withdraw(100, TransactionType::WITHDRAWAL, 'Test 2');
        $this->wallet->withdraw(200, TransactionType::WITHDRAWAL, 'Test 3');

        // Accessor should sum withdrawals and return positive value
        $this->assertEquals(300, $this->wallet->fresh()->total_withdrawn);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_wallet_calculates_available_balance()
    {
        $this->wallet->update(['balance_paise' => 50000, 'locked_balance_paise' => 10000]); // ₹500, ₹100 locked

        // available_balance = balance - locked_balance (in rupees)
        $this->assertEquals(400.00, $this->wallet->fresh()->available_balance);
    }
}
