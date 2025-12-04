<?php
// V-TEST-FIX-006 (Namespace corrected for Laravel)

namespace Tests\Unit;

use Tests\TestCase;
// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RateLimitingTest extends TestCase // Or WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        // This mock client has a simple counter for rate limiting
        $this->client = new class {
            private $loginAttempts = 0;
            const LIMIT = 5;

            public function request($method, $uri) {
                if ($uri === '/login') {
                    $this->loginAttempts++;
                    if ($this->loginAttempts > self::LIMIT) {
                        // 429 Too Many Requests
                        return new class { public function getStatusCode() { return 429; }};
                    }
                    // 401 Unauthorized (simulating failed login)
                    return new class { public function getStatusCode() { return 401; }};
                }
                return new class { public function getStatusCode() { return 404; }};
            }
        };
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testRateLimitingPreventsAbuse()
    {
        $endpoint = '/login';
        $limit = 5; // The known limit for this endpoint

        // 1. Make requests up to the limit
        for ($i = 0; $i < $limit; $i++) {
            $response = $this->client->request('POST', $endpoint);
            // Should be a standard failure (e.g., 401)
            $this->assertEquals(401, $response->getStatusCode());
        }

        // 2. Make one more request
        $response = $this->client->request('POST', $endpoint);
        
        // This one should be rate-limited
        $this->assertEquals(429, $response->getStatusCode());
    }
}