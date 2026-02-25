<?php
// V-FINAL-1730-TEST-06

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\UserKyc;

class KycTest extends FeatureTestCase
{
    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        // Ensure KYC record exists
        UserKyc::create(['user_id' => $this->user->id, 'status' => 'pending']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_upload_kyc_documents()
    {
        Storage::fake('local'); // Fake storage for encryption test

        $response = $this->actingAs($this->user)
                         ->postJson('/api/v1/user/kyc', [
                             'pan_number' => 'ABCDE1234F',
                             'aadhaar_number' => '123456789012',
                             'demat_account' => '12345678',
                             'bank_account' => '9876543210',
                             'bank_ifsc' => 'HDFC0001234',
                             // Fake files
                             'pan' => UploadedFile::fake()->image('pan.jpg'),
                             'aadhaar_front' => UploadedFile::fake()->image('front.jpg'),
                             'aadhaar_back' => UploadedFile::fake()->image('back.jpg'),
                             'bank_proof' => UploadedFile::fake()->pdf('bank.pdf'),
                             'demat_proof' => UploadedFile::fake()->pdf('demat.pdf'),
                         ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('user_kyc', [
            'user_id' => $this->user->id,
            'pan_number' => 'ABCDE1234F',
            'status' => 'submitted'
        ]);
        
        // Ensure documents were created
        $this->assertDatabaseCount('kyc_documents', 5);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_approve_kyc()
    {
        $kyc = $this->user->kyc;
        $kyc->update(['status' => 'submitted', 'submitted_at' => now()]);

        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/kyc-queue/{$kyc->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_kyc', [
            'id' => $kyc->id,
            'status' => 'verified'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_reject_kyc_with_reason()
    {
        $kyc = $this->user->kyc;
        $kyc->update(['status' => 'submitted', 'submitted_at' => now()]);

        $response = $this->actingAs($this->admin)
                         ->postJson("/api/v1/admin/kyc-queue/{$kyc->id}/reject", [
                             'reason' => 'Blurry photos'
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_kyc', [
            'id' => $kyc->id,
            'status' => 'rejected',
            'rejection_reason' => 'Blurry photos'
        ]);
    }
}
