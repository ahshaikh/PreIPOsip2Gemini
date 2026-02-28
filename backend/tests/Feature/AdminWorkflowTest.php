<?php
// V-FINAL-1730-TEST-03

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\UserKyc;
use App\Models\Withdrawal;
use App\Models\Wallet;

class AdminWorkflowTest extends FeatureTestCase
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
        $kyc = \App\Models\UserKyc::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'processing',
            'pan_number' => 'ABCDE1234F',
        ]);

        // Create required documents to pass potential document checks
        foreach (['pan', 'aadhaar_front', 'aadhaar_back', 'bank_proof'] as $type) {
            \App\Models\KycDocument::factory()->create([
                'user_kyc_id' => $kyc->id,
                'doc_type' => $type,
                'status' => 'pending'
            ]);
        }

        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/kyc-queue/{$kyc->id}/approve", [
                             'verification_checklist' => ['pan' => true, 'aadhaar' => true]
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_kyc', [
            'id' => $kyc->id,
            'status' => 'verified'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_approve_and_complete_withdrawal()
    {
        // 1. Setup user wallet and KYC
        $amountPaise = 100000; // ₹1000
        $wallet = Wallet::updateOrCreate(
            ['user_id' => $this->user->id],
            [
                'balance_paise' => 400000,
                'locked_balance_paise' => $amountPaise,
            ]
        );
        
        $wallet->refresh();

        // 2. Create withdrawal as draft first (to avoid triggering WithdrawalObserver on 'pending' status)
        $withdrawal = \App\Models\Withdrawal::factory()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'amount_paise' => $amountPaise,
            'net_amount_paise' => $amountPaise,
            'status' => 'draft' // Bypasses created observer for 'pending'
        ]);
        
        // Now set to pending directly in DB
        \Illuminate\Support\Facades\DB::table('withdrawals')
            ->where('id', $withdrawal->id)
            ->update(['status' => 'pending']);
            
        $withdrawal->refresh();
        $withdrawal = \App\Models\Withdrawal::factory()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'amount_paise' => $amountPaise,
            'net_amount_paise' => $amountPaise,
            'status' => 'pending'
        ]);

        // 3. Approve
        $this->actingAs($this->admin)
             ->postJson("/api/v1/admin/withdrawal-queue/{$withdrawal->id}/approve")
             ->assertStatus(200);

        $this->assertDatabaseHas('withdrawals', ['id' => $withdrawal->id, 'status' => 'approved']);

        // 4. Complete
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
