<?php
// V-FINAL-1730-TEST-72 (Created)

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WithdrawalRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->user->kyc->update(['status' => 'verified']);
        $this->user->wallet()->create([
            'balance_paise' => 10000000, // ₹1,00,000 (1 Lakh) in paise
            'locked_balance_paise' => 0
        ]);

        // Set rules
        Setting::create(['key' => 'min_withdrawal_amount', 'value' => 1000]);
        Setting::create(['key' => 'max_withdrawal_amount_per_day', 'value' => 50000]);
    }

    private function getValidData($overrides = [])
    {
        return array_merge([
            'amount' => 5000,
            'bank_details' => ['account' => '123', 'ifsc' => 'ABC']
        ], $overrides);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validates_amount_positive()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData(['amount' => -100])
        );
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors('amount');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validates_amount_minimum()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData(['amount' => 500]) // Min is 1000
        );
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors('amount');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validates_sufficient_balance()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData(['amount' => 200000]) // Balance is 100,000
        );
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors('amount', 'Insufficient wallet balance.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validates_kyc_approved()
    {
        $this->user->kyc->update(['status' => 'pending']); // Un-verify
        
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData()
        );

        $response->assertStatus(403); // 403 Forbidden
        $response->assertJson(['message' => 'KYC must be verified to request a withdrawal.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validates_bank_details_present()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData(['bank_details' => []])
        );
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['bank_details.account', 'bank_details.ifsc']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validates_withdrawal_limit_per_day()
    {
        // 1. First withdrawal (40,000) - OK
        $this->service = new \App\Services\WithdrawalService(); // Need service for setup
        $this->service->requestWithdrawal($this->user, 40000, ['account'=>'123', 'ifsc'=>'ABC']);

        // 2. Second withdrawal (15,000) - Should fail
        // Total (40k + 15k = 55k) > 50k limit
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/wallet/withdraw',
            $this->getValidData(['amount' => 15000])
        );
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors('amount', 'This withdrawal exceeds your daily limit');
    }
}