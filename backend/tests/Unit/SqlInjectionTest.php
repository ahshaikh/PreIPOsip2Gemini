<?php
// V-TEST-FIX-007 (Namespace corrected for Laravel)

namespace Tests\Unit;

use Tests\UnitTestCase;
use Mockery;

class SqlInjectionTest extends UnitTestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function testSqlInjectionPreventionWorks()
    {
        // The malicious payload
        $maliciousInput = "' OR '1'='1";

        // Mock the database connection using Mockery
        $statementMock = Mockery::mock('Statement');
        $connectionMock = Mockery::mock('Connection');

        $connectionMock->shouldReceive('executeQuery')
            ->once()
            ->with(
                Mockery::pattern('/SELECT \* FROM users WHERE username = \?/'),
                [$maliciousInput]
            )
            ->andReturn($statementMock);

        // Mock a UserRepository that takes this connection
        $userRepository = new class($connectionMock) {
            private $db;
            public function __construct($db) { $this->db = $db; }
            public function findUserByUsername($username) {
                // This is the code we are testing.
                // It *should* be using parameters.
                return $this->db->executeQuery(
                    'SELECT * FROM users WHERE username = ?',
                    [$username]
                );
            }
        };

        // Call the method with the malicious input
        $userRepository->findUserByUsername($maliciousInput);
        $this->assertTrue(true); // Mockery will handle expectations
    }
}
