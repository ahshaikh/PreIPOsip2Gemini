<?php
// V-FINAL-1730-TEST-03

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserKyc;
use App\Models\Withdrawal;
use App\Models\Wallet;

class AdminWorkflowTest extends TestCase
{
    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_approve_kyc()
    {
        $kyc = UserKyc::create([
            'user_id' => $this->user->id,
            'status' => 'submitted',
            'pan_number' => 'ABCDE1234F',
            'submitted_at' => now()
        ]);

        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/kyc-queue/{$kyc->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_kyc', [
            'id' => $kyc->id,
            'status' => 'verified'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_approve_and_complete_withdrawal()
    {
        $wallet = Wallet::create(['user_id' => $this->user->id, 'balance_paise' => 500000, 'locked_balance_paise' => 0]); // ₹5000
        
        // Create pending withdrawal
        $withdrawal = Withdrawal::create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'amount_paise' => 100000, // ₹1000 in paise
            'net_amount_paise' => 100000, // ₹1000 in paise
            'status' => 'pending',
            'bank_details' => ['acc' => '123']
        ]);

        // 1. Approve
        $this->actingAs($this->admin)
             ->postJson("/api/v1/admin/withdrawal-queue/{$withdrawal->id}/approve")
             ->assertStatus(200);

        $this->assertDatabaseHas('withdrawals', ['id' => $withdrawal->id, 'status' => 'approved']);

        // 2. Complete
        $this->actingAs($this->admin)
             ->postJson("/api/v1/admin/withdrawal-queue/{$withdrawal->id}/complete", ['utr_number' => 'UTR12345'])
             ->assertStatus(200);

        $this->assertDatabaseHas('withdrawals', ['id' => $withdrawal->id, 'status' => 'completed', 'utr_number' => 'UTR12345']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_suspend_user()
    {
        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/users/{$this->user->id}/suspend", ['reason' => 'Fraud']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'status' => 'suspended']);
    }
}