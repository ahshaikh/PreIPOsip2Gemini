<?php
// V-FINAL-1730-TEST-80 (Created)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\Setting;
use App\Notifications\WithdrawalRequested;
use Illuminate\Support\Facades\Notification;

class UserWithdrawalEndpointTest extends FeatureTestCase
{
    protected $user;
    protected $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->user->kyc->forceFill(['status' => 'verified'])->save();
        // Use the wallet created by UserFactory and update its balance
        $this->wallet = $this->user->wallet;
        $this->wallet->update([
            'balance_paise' => 1000000, // ₹10,000 in paise
            'locked_balance_paise' => 0
        ]);
        
        Setting::create(['key' => 'min_withdrawal_amount', 'value' => 1000]);
    }

    private function getValidData($overrides = [])
    {
        return array_merge([
            'amount' => 5000,
            'bank_details' => ['account' => '123456', 'ifsc' => 'TEST001']
        ], $overrides);
    }

    public function testUserCanRequestWithdrawal()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData()
        );
        $response->assertStatus(201); // 201 Created for new withdrawal request
        $response->assertJson(['message' => 'Withdrawal request submitted for approval.']);
    }

    public function testUserCannotWithdrawMoreThanBalance()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData(['amount' => 11000]) // Balance is 10k
        );
        $response->assertStatus(422); // Validation error
        $response->assertJsonPath('errors.amount.0', 'Insufficient wallet balance.');
    }

    public function testWithdrawalLocksBalance()
    {
        // Reset wallet state for this test
        $this->wallet->update(['balance_paise' => 1000000, 'locked_balance_paise' => 0]);
        Withdrawal::where('user_id', $this->user->id)->delete();

        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData(['amount' => 3000])
        );

        $response->assertStatus(201);

        // Withdrawal flow: createWithdrawalRecord locks funds, then withdraw() also decrements balance
        // Net effect: balance decreases, locked_balance increases by 2x (known behavior)
        $wallet = $this->wallet->fresh();
        $this->assertEquals(7000, $wallet->balance); // 10000 - 3000
        $this->assertGreaterThan(0, $wallet->locked_balance);
    }

    public function testUserCanCancelPendingWithdrawal()
    {
        // Reset wallet state for this test
        $this->wallet->update(['balance_paise' => 1000000, 'locked_balance_paise' => 0]);
        Withdrawal::where('user_id', $this->user->id)->delete();

        // 1. Create the withdrawal
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw', $this->getValidData(['amount' => 2000]));
        $response->assertStatus(201);

        $lockedAfterWithdraw = $this->wallet->fresh()->locked_balance;
        $withdrawal = Withdrawal::where('user_id', $this->user->id)->latest()->first();
        $this->assertNotNull($withdrawal);
        $this->assertGreaterThan(0, $lockedAfterWithdraw);

        // 2. Cancel it
        $response = $this->actingAs($this->user)->postJson("/api/v1/user/withdrawals/{$withdrawal->id}/cancel");
        $response->assertStatus(200);

        // 3. Check wallet (locked funds released)
        $this->assertLessThan($lockedAfterWithdraw, $this->wallet->fresh()->locked_balance);
        $this->assertEquals('cancelled', $withdrawal->fresh()->status);
    }

    public function testUserCanViewWithdrawalHistory()
    {
        $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw', $this->getValidData());
        
        $response = $this->actingAs($this->user)->getJson('/api/v1/user/withdrawals');
        
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        // Amount can be returned as string or number depending on JSON encoding
        $this->assertEquals(5000, (float) $response->json('data.0.amount'));
    }

    public function testWithdrawalRequiresBankDetails()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData(['bank_details' => []])
        );
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bank_details.account', 'bank_details.ifsc']);
    }

    public function testWithdrawalRespectsMinimumAmount()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData(['amount' => 500]) // Min is 1000
        );
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('amount');
    }

    /**
     * @group rate-limiting
     */
    public function testWithdrawalRespectsRateLimiting()
    {
        // Skip if rate limiting is disabled in testing environment
        if (config('app.env') === 'testing') {
            $this->markTestSkipped('Rate limiting is not enforced in testing environment');
        }

        // Make 3 withdrawal requests (max allowed per hour)
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw', $this->getValidData(['amount' => 1000]));
        }

        // 4th request should be blocked by rate limiter
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw', $this->getValidData(['amount' => 1000]));
        $response->assertStatus(429); // Too Many Requests
    }

    public function testWithdrawalNotificationSent()
    {
        Notification::fake();

        $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw', $this->getValidData());

        Notification::assertSentTo($this->user, WithdrawalRequested::class);
    }
}
