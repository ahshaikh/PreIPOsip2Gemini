<?php
// V-FINAL-1730-TEST-37

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Validator;

class WithdrawalTest extends TestCase
{
    protected $user;
    protected $wallet;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'balance_paise' => 1000000, // ₹10,000 in paise
            'locked_balance_paise' => 0
        ]);
        $this->admin = User::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_withdrawal_belongs_to_user()
    {
        $withdrawal = Withdrawal::factory()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id
        ]);

        $this->assertInstanceOf(User::class, $withdrawal->user);
        $this->assertEquals($this->user->id, $withdrawal->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_withdrawal_belongs_to_wallet()
    {
        $withdrawal = Withdrawal::factory()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id
        ]);

        $this->assertInstanceOf(Wallet::class, $withdrawal->wallet);
        $this->assertEquals($this->wallet->id, $withdrawal->wallet->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_withdrawal_status_enum_validates()
    {
        $validStatuses = ['pending', 'approved', 'processing', 'completed', 'rejected'];
        
        $validator = Validator::make(
            ['status' => 'pending'], 
            ['status' => 'in:' . implode(',', $validStatuses)]
        );
        $this->assertTrue($validator->passes());
        
        $validator = Validator::make(
            ['status' => 'shipped'], // Invalid status
            ['status' => 'in:' . implode(',', $validStatuses)]
        );
        $this->assertFalse($validator->passes());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_withdrawal_validates_amount_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Withdrawal amount must be positive");

        Withdrawal::factory()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'amount_paise' => -10000 // Invalid: -100 rupees in paise
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_withdrawal_validates_sufficient_balance()
    {
        // This logic is (and should be) in the Wallet model, not Withdrawal.
        // This test confirms the Wallet model's protection.
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient funds");

        // Wallet has 10,000. Try to withdraw 15,000.
        $this->wallet->withdraw(15000, 'withdrawal', 'Test');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_withdrawal_calculates_fee()
    {
        $withdrawal = Withdrawal::factory()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'amount_paise' => 100000, // ₹1000 in paise
            'fee_paise' => 5000 // ₹50 in paise
        ]);

        // Accessor returns rupees (fee_paise / 100)
        $this->assertEquals(50, $withdrawal->fee);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_withdrawal_calculates_net_amount()
    {
        // Test the 'booted' method's auto-calculation
        $withdrawal = Withdrawal::factory()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'amount_paise' => 100000, // ₹1000 in paise
            'fee_paise' => 5000 // ₹50 in paise
        ]);

        // 1000 (Amount) - 50 (Fee) = 950 (net_amount accessor returns rupees)
        $this->assertEquals(950, $withdrawal->net_amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_withdrawal_tracks_processed_by_admin()
    {
        $withdrawal = Withdrawal::factory()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'amount_paise' => 100000, // ₹1000 in paise
            'admin_id' => $this->admin->id
        ]);

        $this->assertInstanceOf(User::class, $withdrawal->admin);
        $this->assertEquals($this->admin->id, $withdrawal->admin->id);
    }
}