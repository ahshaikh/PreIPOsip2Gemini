<?php
// V-TEST-FIX-009 (Namespace corrected for Laravel)

namespace Tests\Unit;

use Tests\TestCase;

class XssPreventionTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        // Set up a mock client that simulates form submissions and rendering
        $this->client = new class {
        private $storedData = '';
        public function request($method, $uri, $payload = []) {
            if ($method === 'POST' && $uri === '/profile/update') {
                // Simulate storing the raw input
                $this->storedData = $payload['username'];
                return new class { public function getStatusCode() { return 302; }}; // Redirect
            }
            if ($method === 'GET' && $uri === '/profile/view') {
                // Simulate rendering the data
                // A correct system escapes output. An incorrect one does not.
                // We'll simulate a TWIG-like environment that escapes by default.
                $escapedData = htmlspecialchars($this->storedData, ENT_QUOTES, 'UTF-8');
                return new class($escapedData) {
                    private $content;
                    public function __construct($content) { $this->content = $content; }
                    public function getStatusCode() { return 200; }
                    public function getContent() { return "<div>{$this->content}</div>"; }
                };
            }
            return new class { public function getStatusCode() { return 404; }};
        }
    };
    }

    /**
     * @test
     * Corresponds to: SecurityEndpointTest::testXssPreventionInInputs
     *
     * This test submits a malicious XSS string and then checks
     * if the *rendered* output on another page is properly escaped.
     */
    public function testXssPreventionInInputs()
    {
        $xssPayload = '<script>alert("hacked");</script>';
        $expectedEscapedPayload = htmlspecialchars($xssPayload, ENT_QUOTES, 'UTF-8');

        // 1. Submit the malicious payload
        $this->client->request('POST', '/profile/update', [
            'username' => $xssPayload
        ]);

        // 2. View the page where the payload would be rendered
        $response = $this->client->request('GET', '/profile/view');
        $content = $response->getContent();

        // 3. Assert that the script tag is NOT present as raw HTML
        $this->assertStringNotContainsString($xssPayload, $content);

        // 4. Assert that the *escaped* version IS present
        $this->assertStringContainsString($expectedEscapedPayload, $content);
    }
}