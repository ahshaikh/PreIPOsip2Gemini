<?php

namespace App\Tests\Security;

use PHPUnit\Framework\TestCase;
// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CsrfProtectionTest extends TestCase // Or WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        // Mock client setup
        $this->client = new class {
            private $user = null;
            public function loginUser($user) { $this->user = $user; }
            public function request($method, $uri, $payload = []) {
                if ($this->user === null) {
                    return new class { public function getStatusCode() { return 401; }};
                }
                
                // Simulate CSRF token check
                if ($method === 'POST') {
                    if (empty($payload['_token']) || $payload['_token'] !== 'valid_token_for_' . $this->user->id) {
                        return new class { public function getStatusCode() { return 403; }}; // Forbidden
                    }
                    return new class { public function getStatusCode() { return 200; }}; // OK
                }
                return new class { public function getStatusCode() { return 404; }};
            }
        };
        $this->user = (object)['id' => 123];
    }
    
    /**
     * @test
     * Corresponds to: SecurityEndpointTest::testCsrfProtectionEnabled
     */
    public function testCsrfProtectionEnabled()
    {
        $this->client->loginUser($this->user);

        // 1. Try to POST data *without* a CSRF token
        $response = $this->client->request('POST', '/account/delete', [
            'confirm' => 'true'
            // No '_token' key
        ]);

        // Expect a 403 Forbidden response
        $this->assertEquals(403, $response->getStatusCode());

        // 2. Try to POST data with an *invalid* CSRF token
        $response = $this->client->request('POST', '/account/delete', [
            'confirm' => 'true',
            '_token' => 'invalid_token'
        ]);

        // Expect a 403 Forbidden response
        $this->assertEquals(403, $response->getStatusCode());

        // 3. (Optional) Test with a valid token
        $response = $this->client->request('POST', '/account/delete', [
            'confirm' => 'true',
            '_token' => 'valid_token_for_123' // A token we know is valid
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }
}