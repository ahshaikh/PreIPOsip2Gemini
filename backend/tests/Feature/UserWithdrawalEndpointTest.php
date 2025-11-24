<?php
// V-FINAL-1730-TEST-80 (Created)

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\Setting;
use App\Notifications\WithdrawalRequested;
use Illuminate\Support\Facades\Notification;

class UserWithdrawalEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->user->kyc->update(['status' => 'verified']);
        $this->wallet = Wallet::create(['user_id' => $this->user->id, 'balance' => 10000]); // 10k balance
        
        Setting::create(['key' => 'min_withdrawal_amount', 'value' => 1000]);
    }

    private function getValidData($overrides = [])
    {
        return array_merge([
            'amount' => 5000,
            'bank_details' => ['account' => '123456', 'ifsc' => 'TEST001']
        ], $overrides);
    }

    /** @test */
    public function testUserCanRequestWithdrawal()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData()
        );
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Withdrawal request submitted for approval.']);
    }

    /** @test */
    public function testUserCannotWithdrawMoreThanBalance()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData(['amount' => 11000]) // Balance is 10k
        );
        $response->assertStatus(422); // Validation error
        $response->assertJsonValidationErrors('amount', 'Insufficient funds');
    }

    /** @test */
    public function testWithdrawalLocksBalance()
    {
        $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData(['amount' => 3000])
        );
        
        $this->assertEquals(7000, $this->wallet->fresh()->balance);
        $this->assertEquals(3000, $this->wallet->fresh()->locked_balance);
    }

    /** @test */
    public function testUserCanCancelPendingWithdrawal()
    {
        // 1. Create the withdrawal
        $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw', $this->getValidData(['amount' => 2000]));
        $this->assertEquals(8000, $this->wallet->fresh()->balance);
        $this->assertEquals(2000, $this->wallet->fresh()->locked_balance);
        $withdrawal = Withdrawal::first();

        // 2. Cancel it
        $response = $this->actingAs($this->user)->postJson("/api/v1/user/withdrawals/{$withdrawal->id}/cancel");
        $response->assertStatus(200);

        // 3. Check wallet (funds returned)
        $this->assertEquals(10000, $this->wallet->fresh()->balance);
        $this->assertEquals(0, $this->wallet->fresh()->locked_balance);
        $this->assertEquals('cancelled', $withdrawal->fresh()->status);
    }

    /** @test */
    public function testUserCanViewWithdrawalHistory()
    {
        $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw', $this->getValidData());
        
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/withdrawals');
        
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.amount', '5000.00');
    }

    /** @test */
    public function testWithdrawalRequiresBankDetails()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData(['bank_details' => []])
        );
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bank_details.account', 'bank_details.ifsc']);
    }

    /** @test */
    public function testWithdrawalRespectsMinimumAmount()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData(['amount' => 500]) // Min is 1000
        );
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('amount');
    }

    /** @test */
    public function testWithdrawalRespectsRateLimiting()
    {
        // 5 requests should be OK (422 for balance, but not 429)
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw', $this->getValidData(['amount' => 10001]));
        }
        
        // 6th request should be blocked
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw', $this->getValidData());
        $response->assertStatus(429); // Too Many Requests
    }

    /** @test */
    public function testWithdrawalNotificationSent()
    {
        Notification::fake();

        $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw', $this->getValidData());

        Notification::assertSentTo($this->user, WithdrawalRequested::class);
    }

    /** @test */
    public function testWithdrawalStatusTracking()
    {
        $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw', $this->getValidData());
        
        $this-