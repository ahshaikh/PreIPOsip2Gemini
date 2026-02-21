<?php
// V-FINAL-1730-TEST-36

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\WalletService;
use App\Enums\TransactionType;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionTest extends TestCase
{
    protected $user;
    protected $wallet;
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->walletService = app(WalletService::class);

        $this->user = User::factory()->create();

        // V-FIX-TRANSACTION-TEST-2026: UserFactory automatically creates a wallet
        // with balance 0 via afterCreating hook. Use that wallet and update its balance
        // instead of creating a new wallet (which would create duplicate wallets).
        $this->wallet = $this->user->wallet;
        $this->wallet->update([
            'balance_paise' => 100000, // ₹1000 in paise
            'locked_balance_paise' => 0
        ]);
        $this->wallet->refresh();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_transaction_belongs_to_wallet()
    {
        $txn = $this->walletService->deposit(
            $this->user,
            10000, // 100 rupees in paise (WalletService expects paise for int)
            TransactionType::DEPOSIT,
            'test deposit',
            bypassComplianceCheck: true
        );

        $this->assertInstanceOf(Wallet::class, $txn->wallet);
        $this->assertEquals($this->wallet->id, $txn->wallet->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_transaction_type_enum_validates()
    {
        // This confirms we can store valid types
        $txn = $this->walletService->deposit(
            $this->user,
            1000, // 10 rupees in paise
            TransactionType::DEPOSIT,
            'test deposit',
            bypassComplianceCheck: true
        );
        $this->assertEquals('deposit', $txn->fresh()->type);

        $txn2 = $this->walletService->withdraw(
            $this->user,
            1000, // 10 rupees in paise
            TransactionType::WITHDRAWAL,
            'test withdrawal'
        );
        $this->assertEquals('withdrawal', $txn2->fresh()->type);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_transaction_status_enum_validates()
    {
        // Test default status from deposit
        $txn = $this->wallet->deposit(10, 'deposit', 'test');
        $this->assertEquals('completed', $txn->fresh()->status);

        // V-FIX-TRANSACTION-TEST-2026: Transaction model is immutable.
        // Cannot test non-default status via update(). Use factory instead.
        $pendingTxn = Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);
        $this->assertEquals('pending', $pendingTxn->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_transaction_tracks_before_balance()
    {
        $this->assertEquals(1000, $this->wallet->balance);

        $txn = $this->walletService->deposit(
            $this->user,
            50000, // 500 rupees in paise
            TransactionType::DEPOSIT,
            'test deposit',
            bypassComplianceCheck: true
        );

        // Balance *before* this transaction was 1000 (100000 paise / 100)
        $this->assertEquals(1000, $txn->balance_before);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_transaction_tracks_after_balance()
    {
        $this->assertEquals(1000, $this->wallet->balance);

        $txn = $this->walletService->deposit(
            $this->user,
            50000, // 500 rupees in paise
            TransactionType::DEPOSIT,
            'test deposit',
            bypassComplianceCheck: true
        );

        // Balance *after* this transaction is 1500 (150000 paise / 100)
        $this->assertEquals(1500, $txn->balance_after);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_transaction_has_unique_transaction_id()
    {
        $txn1 = $this->walletService->deposit(
            $this->user,
            1000, // 10 rupees in paise
            TransactionType::DEPOSIT,
            'test deposit 1',
            bypassComplianceCheck: true
        );
        $this->assertNotNull($txn1->transaction_id);
        $this->assertTrue(Str::isUuid($txn1->transaction_id));

        // V-FIX-TRANSACTION-TEST-2026: Test uniqueness constraint via raw SQL
        // since Transaction model is immutable. Use try-catch to verify constraint.
        $txn2 = $this->walletService->deposit(
            $this->user,
            1000, // 10 rupees in paise
            TransactionType::DEPOSIT,
            'test deposit 2',
            bypassComplianceCheck: true
        );

        // Verify both transactions have different UUIDs
        $this->assertNotEquals($txn1->transaction_id, $txn2->transaction_id);

        // Attempt to force duplicate UUID via raw SQL (bypasses model immutability)
        $exceptionThrown = false;
        try {
            DB::table('transactions')
                ->where('id', $txn2->id)
                ->update(['transaction_id' => $txn1->transaction_id]);
        } catch (QueryException $e) {
            $exceptionThrown = true;
            // Verify it's a duplicate key error (SQLSTATE 23000)
            $this->assertStringContainsString('23000', $e->getCode());
        }

        $this->assertTrue($exceptionThrown, 'Expected QueryException for duplicate transaction_id');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_transaction_scope_completed_filters_correctly()
    {
        $this->walletService->deposit(
            $this->user,
            1000,
            TransactionType::DEPOSIT,
            'test',
            bypassComplianceCheck: true
        ); // Status: completed

        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $this->assertEquals(1, Transaction::completed()->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_transaction_scope_pending_filters_correctly()
    {
        $this->walletService->deposit(
            $this->user,
            1000,
            TransactionType::DEPOSIT,
            'test',
            bypassComplianceCheck: true
        ); // Status: completed

        Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $this->assertEquals(1, Transaction::pending()->count());
    }
}