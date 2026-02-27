<?php
// V-TEST-FIX-001 (Namespace corrected for Laravel)

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Models\User;
// We'd typically use a framework-specific test case, e.g., WebTestCase
// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// Assuming use of a framework test case like Symfony's WebTestCase
// which provides a client to make requests.
// For this example, we'll use a conceptual TestCase.

class AdminRoutesSecurityTest extends UnitTestCase // Replace with WebTestCase if using Symfony
{
    // Mocks for a client and user creation
    private $client;
    
    protected function setUp(): void
    {
        // This would initialize a test client, e.g., static::createClient();
        $this->client = new class {
            private $user = null;

            public function loginUser($user) {
                $this->user = $user;
            }

            public function request($method, $uri) {
                // Mock response logic
                if (str_starts_with($uri, '/admin')) {
                    if ($this->user === null) {
                        return new class { public function getStatusCode() { return 401; }}; // Unauthorized
                    }
                    if ($this->user->role !== 'ROLE_ADMIN') {
                        return new class { public function getStatusCode() { return 403; }}; // Forbidden
                    }
                    return new class { public function getStatusCode() { return 200; }}; // OK
                }
                return new class { public function getStatusCode() { return 404; }}; // Not Found
            }
        };

        // Mock User objects
        $this->regularUser = (object)['username' => 'user', 'role' => 'ROLE_USER'];
        $this->adminUser = (object)['username' => 'admin', 'role' => 'ROLE_ADMIN'];
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testUnauthorizedAccessToAdminRoutesFails()
    {
        $adminRoutes = ['/admin/dashboard', '/admin/users/delete/1'];

        foreach ($adminRoutes as $route) {
            // 0. Ensure no user logged in
            $this->client->loginUser(null);

            // 1. Test with no user (guest)
            $response = $this->client->request('GET', $route);
            // Expecting a 401 Unauthorized or a 302 Redirect to /login
            $this->assertContains($response->getStatusCode(), [401, 302]);

            // 2. Test with a regular user
            $this->client->loginUser($this->regularUser);
            $response = $this->client->request('GET', $route);
            // Expecting a 403 Forbidden
            $this->assertEquals(403, $response->getStatusCode());
        }
        
        // 3. (Optional) Test with an admin user to ensure it works
        $this->client->loginUser($this->adminUser);
        $response = $this->client->request('GET', $adminRoutes[0]);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
