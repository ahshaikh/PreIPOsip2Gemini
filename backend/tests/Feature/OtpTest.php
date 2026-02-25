<?php
// V-FINAL-1730-TEST-05

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Otp;
use Carbon\Carbon;

class OtpTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        // We don't need full plans for OTP testing
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_verifies_correct_otp_successfully()
    {
        $user = User::factory()->create();
        
        // Create a valid OTP
        Otp::create([
            'user_id' => $user->id,
            'type' => 'email',
            'otp_code' => '123456',
            'expires_at' => now()->addMinutes(10)
        ]);

        $response = $this->postJson('/api/v1/verify-otp', [
            'user_id' => $user->id,
            'type' => 'email',
            'otp' => '123456'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Email verified successfully.']);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_rejects_incorrect_otp()
    {
        $user = User::factory()->create();
        
        Otp::create([
            'user_id' => $user->id,
            'type' => 'mobile',
            'otp_code' => '123456',
            'expires_at' => now()->addMinutes(10)
        ]);

        $response = $this->postJson('/api/v1/verify-otp', [
            'user_id' => $user->id,
            'type' => 'mobile',
            'otp' => '999999' // Wrong code
        ]);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Invalid or expired OTP.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_rejects_expired_otp()
    {
        $user = User::factory()->create();
        
        Otp::create([
            'user_id' => $user->id,
            'type' => 'email',
            'otp_code' => '123456',
            'expires_at' => now()->subMinute() // Expired
        ]);

        $response = $this->postJson('/api/v1/verify-otp', [
            'user_id' => $user->id,
            'type' => 'email',
            'otp' => '123456'
        ]);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Invalid or expired OTP.']);
    }
}
