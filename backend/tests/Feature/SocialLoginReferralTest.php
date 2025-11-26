<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Referral;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;

class SocialLoginReferralTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function it_can_process_referral_code_during_google_login()
    {
        // Create a referrer user
        $referrer = User::factory()->create([
            'referral_code' => 'REFER123',
        ]);

        // Mock Socialite
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-id-123');
        $socialiteUser->shouldReceive('getEmail')->andReturn('newuser@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('New User');
        $socialiteUser->shouldReceive('getNickname')->andReturn('newuser');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($socialiteUser);

        // Simulate callback with referral code in state
        $state = base64_encode(json_encode(['referral_code' => 'REFER123']));

        $response = $this->get('/api/v1/auth/google/callback?state=' . $state);

        // Should redirect to frontend with token
        $this->assertTrue($response->isRedirection());

        // Check that user was created
        $newUser = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($newUser);

        // Check that referral was created
        $this->assertEquals($referrer->id, $newUser->referred_by);

        $referral = Referral::where('referrer_id', $referrer->id)
            ->where('referred_id', $newUser->id)
            ->first();

        $this->assertNotNull($referral);
        $this->assertEquals('pending', $referral->status);
    }

    /** @test */
    public function it_handles_invalid_referral_code_gracefully()
    {
        // Mock Socialite
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-id-456');
        $socialiteUser->shouldReceive('getEmail')->andReturn('user2@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('User Two');
        $socialiteUser->shouldReceive('getNickname')->andReturn('user2');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar2.jpg');

        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($socialiteUser);

        // Use invalid referral code
        $state = base64_encode(json_encode(['referral_code' => 'INVALID']));

        $response = $this->get('/api/v1/auth/google/callback?state=' . $state);

        // Should still succeed
        $this->assertTrue($response->isRedirection());

        // User should be created without referral
        $newUser = User::where('email', 'user2@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertNull($newUser->referred_by);

        // No referral record should be created
        $this->assertEquals(0, Referral::where('referred_id', $newUser->id)->count());
    }

    /** @test */
    public function it_prevents_self_referral()
    {
        // Create a user
        $user = User::factory()->create([
            'referral_code' => 'SELFREF',
            'google_id' => 'google-existing',
        ]);

        // Mock Socialite to return the same user
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-existing');
        $socialiteUser->shouldReceive('getEmail')->andReturn($user->email);
        $socialiteUser->shouldReceive('getName')->andReturn('Existing User');
        $socialiteUser->shouldReceive('getNickname')->andReturn($user->username);
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($socialiteUser);

        // Try to use own referral code
        $state = base64_encode(json_encode(['referral_code' => 'SELFREF']));

        $response = $this->get('/api/v1/auth/google/callback?state=' . $state);

        // Should succeed but not create self-referral
        $this->assertTrue($response->isRedirection());

        // No referral should be created
        $this->assertEquals(0, Referral::where('referrer_id', $user->id)
            ->where('referred_id', $user->id)
            ->count());
    }

    /** @test */
    public function google_redirect_includes_referral_code_in_state()
    {
        $response = $this->get('/api/v1/auth/google?referral_code=TEST123');

        $response->assertStatus(200);
        $response->assertJsonStructure(['redirect_url']);

        $data = $response->json();
        $this->assertStringContainsString('google.com', $data['redirect_url']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
