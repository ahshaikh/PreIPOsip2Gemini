<?php
// V-FINAL-1730-TEST-13

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\UserProfile;
use App\Jobs\SendOtpJob;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    // --- REGISTRATION TESTS ---
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_register_creates_user_with_hashed_password()
    {
        $response = $this->postJson('/api/v1/register', [
            'username' => 'newuser',
            'email' => 'new@example.com',
            'mobile' => '9000000001',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!'
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'new@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotEquals('Secret123!', $user->password);
        $this->assertTrue(Hash::check('Secret123!', $user->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_register_creates_wallet_for_new_user()
    {
        $this->postJson('/api/v1/register', [
            'username' => 'walletuser',
            'email' => 'wallet@example.com',
            'mobile' => '9000000002',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!'
        ]);

        $user = User::where('email', 'wallet@example.com')->first();
        $this->assertTrue($user->wallet()->exists());
        $this->assertEquals(0, $user->wallet->balance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_register_creates_profile_for_new_user()
    {
        $this->postJson('/api/v1/register', [
            'username' => 'profileuser',
            'email' => 'profile@example.com',
            'mobile' => '9000000003',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!'
        ]);

        $user = User::where('email', 'profile@example.com')->first();
        $this->assertTrue($user->profile()->exists());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_register_generates_unique_referral_code()
    {
        $this->postJson('/api/v1/register', [
            'username' => 'refuser',
            'email' => 'ref@example.com',
            'mobile' => '9000000004',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!'
        ]);

        $user = User::where('email', 'ref@example.com')->first();
        $this->assertNotNull($user->referral_code);
        $this->assertEquals(10, strlen($user->referral_code));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_register_sends_otp_email_and_sms()
    {
        Queue::fake();

        $this->postJson('/api/v1/register', [
            'username' => 'otpuser',
            'email' => 'otp@example.com',
            'mobile' => '9000000005',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!'
        ]);

        // Assert that the job was pushed twice (once for email, once for mobile)
        Queue::assertPushed(SendOtpJob::class, 2);
        
        Queue::assertPushed(SendOtpJob::class, function ($job) {
            return $job->type === 'email';
        });
        
        Queue::assertPushed(SendOtpJob::class, function ($job) {
            return $job->type === 'mobile';
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_register_validates_referral_code_if_provided()
    {
        // Create a referrer
        $referrer = User::factory()->create(['referral_code' => 'VALID123']);

        $response = $this->postJson('/api/v1/register', [
            'username' => 'refjoiner',
            'email' => 'joiner@example.com',
            'mobile' => '9000000006',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'referral_code' => 'VALID123'
        ]);

        $response->assertStatus(201);
        
        $user = User::where('email', 'joiner@example.com')->first();
        $this->assertEquals($referrer->id, $user->referred_by);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_register_rejects_invalid_referral_code()
    {
        $response = $this->postJson('/api/v1/register', [
            'username' => 'fakejoiner',
            'email' => 'fake@example.com',
            'mobile' => '9000000007',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'referral_code' => 'INVALID999'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['referral_code']);
    }

    // --- LOGIN TESTS ---
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_login_validates_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPassword'),
            'status' => 'active'
        ]);

        // 1. Wrong Password
        $response = $this->postJson('/api/v1/login', [
            'login' => $user->email,
            'password' => 'WrongPassword'
        ]);
        $response->assertStatus(422); // Validation error for credentials

        // 2. Correct Password
        $response = $this->postJson('/api/v1/login', [
            'login' => $user->email,
            'password' => 'CorrectPassword'
        ]);
        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_login_generates_jwt_token()
    {
        $user = User::factory()->create(['status' => 'active', 'password' => Hash::make('password')]);

        $response = $this->postJson('/api/v1/login', [
            'login' => $user->email,
            'password' => 'password'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);
                 
        $this->assertNotEmpty($response->json('token'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_login_updates_last_login_timestamp()
    {
        $user = User::factory()->create(['status' => 'active', 'password' => Hash::make('password')]);
        
        $this->assertNull($user->last_login_at);

        $this->postJson('/api/v1/login', [
            'login' => $user->email,
            'password' => 'password'
        ]);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_login_rejects_suspended_account()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'status' => 'suspended'
        ]);

        $response = $this->postJson('/api/v1/login', [
            'login' => $user->email,
            'password' => 'password'
        ]);

        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'Your account is not active. Please verify or contact support.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_login_rejects_blocked_account()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'status' => 'blocked'
        ]);

        $response = $this->postJson('/api/v1/login', [
            'login' => $user->email,
            'password' => 'password'
        ]);

        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'Your account is not active. Please verify or contact support.']);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_logout_revokes_token()
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/v1/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged out successfully.']);

        // Ensure the token is deleted from the database
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}