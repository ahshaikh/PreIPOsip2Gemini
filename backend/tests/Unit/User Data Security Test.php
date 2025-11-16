<?php

namespace App\Tests\Security;

use PHPUnit\Framework\TestCase;
// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserDataSecurityTest extends TestCase // Or WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        // Mock client setup
        $this->client = new class {
            private $user = null;
            public function loginUser($user) { $this->user = $user; }
            public function request($method, $uri) {
                // Mock logic: /api/users/123 should only be accessible to user 123
                preg_match('/\/api\/users\/(\d+)/', $uri, $matches);
                $requestedUserId = $matches[1] ?? null;

                if ($this->user === null) {
                    return new class { public function getStatusCode() { return 401; }}; // Unauthorized
                }
                if ($requestedUserId && $this->user->id != $requestedUserId) {
                    return new class { public function getStatusCode() { return 403; }}; // Forbidden
                }
                if ($requestedUserId && $this->user->id == $requestedUserId) {
                     return new class { 
                         public function getStatusCode() { return 200; }
                         public function getContent() { return json_encode(['email' => 'user123@test.com']); }
                     };
                }
                return new class { public function getStatusCode() { return 404; }};
            }
        };

        $this->user123 = (object)['id' => 123, 'role' => 'ROLE_USER'];
        $this->user456 = (object)['id' => 456, 'role' => 'ROLE_USER'];
    }

    /**
     * @test
     * Corresponds to: SecurityEndpointTest::testUnauthorizedAccessToOtherUserDataFails
     */
    public function testUnauthorizedAccessToOtherUserDataFails()
    {
        // Log in as User 123
        $this->client->loginUser($this->user123);

        // Try to access User 456's data
        $response = $this->client->request('GET', '/api/users/456/profile');
        
        // Expecting a 403 Forbidden or 404 Not Found (depending on policy)
        $this->assertContains($response->getStatusCode(), [403, 404]);

        // Now, try to access User 123's own data
        $response = $this->client->request('GET', '/api/users/123/profile');
        
        // This should be allowed
        $this->assertEquals(200, $response->getStatusCode());
    }
}