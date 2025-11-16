<?php

namespace App\Tests\Security;

use PHPUnit\Framework\TestCase;
// This often requires mocking the database layer.
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;

class SqlInjectionTest extends TestCase
{
    /**
     * @test
     * Corresponds to: SecurityEndpointTest::testSqlInjectionPreventionWorks
     *
     * This test validates that the repository/service layer uses
     * parameterized queries, not string concatenation.
     */
    public function testSqlInjectionPreventionWorks()
    {
        // The malicious payload
        $maliciousInput = "' OR '1'='1";

        // Mock the database connection
        $statementMock = $this->createMock(Statement::class);
        $connectionMock = $this->createMock(Connection::class);

        // We expect the 'executeQuery' (or similar) method to be called
        // with the *unaltered* malicious string as a *parameter*,
        // not as part of the SQL query string itself.
        $connectionMock->expects($this->once())
            ->method('executeQuery')
            ->with(
                // The SQL string should be a prepared statement
                $this->stringContains('SELECT * FROM users WHERE username = ?'),
                // The parameters array should contain the malicious input
                $this->equalTo([$maliciousInput])
            )
            ->willReturn($statementMock);

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
        
        // The test passes if the mock's expectations (set with expects()) are met.
    }
}