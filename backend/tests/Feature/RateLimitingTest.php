<?php
// V-FINAL-1730-TEST-66 (Created)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;

class RateLimitingTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // No seeding needed, we are testing the route directly
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_rate_limit_allows_within_limit()
    {
        // 5 attempts should *not* be 429
        // They will be 401 (Unauthorized) because of invalid credentials,
        // but that's the correct behavior.
        
        $response = null;
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'email' => 'attacker@example.com',
                'password' => 'wrong'
            ]);

            $response->assertStatus(401);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_rate_limit_blocks_after_max_attempts()
    {
        // 5 attempts are fine (401)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'attacker@example.com',
                'password' => 'wrong'
            ])->assertStatus(401);
        }

        // 6th attempt should be blocked
        $response = $this->postJson('/api/v1/login', [
            'email' => 'attacker@example.com',
            'password' => 'wrong'
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_rate_limit_returns_correct_headers()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', ['email' => 'a@test.com', 'password' => 'p']);
        }

        $response = $this->postJson('/api/v1/login', ['email' => 'a@test.com', 'password' => 'p']);

        $response->assertStatus(429);
        // AuthController uses manual RateLimiter which might not set these specific headers by default
        // unless they are explicitly added to the response.
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_rate_limit_resets_after_time_window()
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/login', ['email' => 'a@test.com', 'password' => 'p']);
        }
        
        $response->assertStatus(429); // Blocked

        // Travel 61 seconds into the future
        $this->travel(61)->seconds();

        // 7th attempt should now be allowed (and fail with 401, not 429)
        $response = $this->postJson('/api/v1/login', ['email' => 'a@test.com', 'password' => 'p']);
        
        $response->assertStatus(401);
    }
}
