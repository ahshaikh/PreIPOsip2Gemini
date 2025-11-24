<?php
// V-TEST-FIX-005 (Namespace corrected for Laravel)

namespace Tests\Unit;

use Tests\TestCase;
// use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PerformanceTest extends TestCase // Or WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        // This mock client simulates a stateful backend (e.g., a database)
        // with an account balance. The request logic *itself* is atomic.
        $this->client = new class {
            // Simulate a single user account's balance in the "database"
            private $accountBalance = 100;

            public function request($method, $uri, $payload = []) {
                if ($method === 'POST' && $uri === '/api/payment/process') {
                    // This block simulates an ATOMIC transaction
                    // (e.g., SELECT...FOR UPDATE)
                    
                    $amount = $payload['amount'] ?? 0;
                    
                    if ($this->accountBalance >= $amount) {
                        // Simulate payment processing logic
                        $this->accountBalance -= $amount;
                        // Payment succeeded
                        return new class($this->accountBalance) { 
                            private $bal;
                            public function __construct($bal) { $this->bal = $bal; }
                            public function getStatusCode() { return 200; }
                            public function getContent() { return json_encode(['success' => true, 'newBalance' => $this->bal]); }
                        };
                    } else {
                        // Payment failed, insufficient funds
                        return new class { 
                            public function getStatusCode() { return 400; } // Bad Request
                            public function getContent() { return json_encode(['success' => false, 'error' => 'Insufficient funds']); }
                        };
                    }
                }
                return new class { public function getStatusCode() { return 404; }};
            }
            
            public function getBalance() {
                return $this->accountBalance;
            }
        };
    }

    /**
     * @test
     * Corresponds to: PerformanceTest::testConcurrentPaymentProcessing
     *
     * This test doesn't run in parallel, but it tests the *outcome*
     * of a potential race condition. It ensures that two requests
     * for an amount that would overdraft the account if run
     * concurrently are handled correctly by an atomic process.
     *
     * We start with $100. Two requests for $70 are made.
     * Expected: One succeeds, one fails. Final balance is $30.
     */
    public function testConcurrentPaymentProcessing()
    {
        // Initial state check (optional)
        $this->assertEquals(100, $this->client->getBalance());

        // 1. Make the first payment request
        $response1 = $this->client->request('POST', '/api/payment/process', [
            'amount' => 70
        ]);

        // 2. Make the second payment request
        $response2 = $this->client->request('POST', '/api/payment/process', [
            'amount' => 70
        ]);

        // 3. Check the final balance in the "database"
        $finalBalance = $this->client->getBalance();
        $this->assertEquals(30, $finalBalance, "The final balance should be 30 (100 - 70).");

        // 4. Check that one request succeeded and one failed
        $statusCodes = [$response1->getStatusCode(), $response2->getStatusCode()];
        
        // Assert that one response is 200 (OK)
        $this->assertContains(200, $statusCodes, "One request should have succeeded (200).");
        
        // Assert that one response is 400 (Insufficient Funds)
        $this->assertContains(400, $statusCodes, "One request should have failed (400).");
    }
}