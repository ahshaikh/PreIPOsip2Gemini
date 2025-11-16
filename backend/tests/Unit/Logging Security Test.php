<?php

namespace App\Tests\Security;

use PHPUnit\Framework\TestCase;
// use Psr\Log\LoggerInterface;
// use Monolog\Handler\TestHandler;

class LoggingSecurityTest extends TestCase
{
    /**
     * @test
     * Corresponds to: SecurityEndpointTest::testSensitiveDataNotInLogs
     */
    public function testSensitiveDataNotInLogs()
    {
        // Sensitive data
        $password = 'MyS3cretPa$$w0rd!';
        $creditCard = '4111222233334444';
        
        // Use Monolog's TestHandler to capture log messages
        // $testHandler = new \Monolog\Handler\TestHandler();
        // $logger = new \Monolog\Logger('test', [$testHandler]);
        
        // For this example, we'll mock a simple logger
        $logger = new class {
            public $messages = [];
            public function info($message, $context = []) {
                $this->messages[] = $message . ' ' . json_encode($context);
            }
        };

        // Create a service (e.g., a user service) that logs
        $userService = new class($logger) {
            private $logger;
            public function __construct($logger) { $this->logger = $logger; }
            
            public function login($username, $password) {
                // This is the code under test.
                // It should *not* log the password.
                $this->logger->info('Login attempt', ['user' => $username]);
                // A bad implementation might be:
                // $this->logger->info('Login attempt', ['user' => $username, 'pass' => $password]);
            }
        };
        
        // Run the code that triggers the log
        $userService->login('testUser', $password);

        // Get all log messages
        $logMessages = $logger->messages;

        // Check each log message
        foreach ($logMessages as $message) {
            $this->assertStringNotContainsStringIgnoringCase($password, $message);
            $this->assertStringNotContainsStringIgnoringCase($creditCard, $message);
        }
    }
}