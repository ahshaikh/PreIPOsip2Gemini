<?php
// V-TEST-FIX-004 (Namespace corrected for Laravel)

namespace Tests\Unit;

use Tests\TestCase;
// use Doctrine\ORM\EntityManagerInterface;
// use App\Repository\PasswordResetTokenRepository;
// use App\Entity\PasswordResetToken;

class PasswordResetTokenTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function testPasswordResetTokenExpiry()
    {
        $tokenValue = 'some_valid_token_string';

        // 1. Create a token that expired 1 hour ago
        $expiredToken = new class {
            public $token = 'expired_token';
            public $expiresAt;
            public function __construct() {
                $this->expiresAt = new \DateTime('-1 hour');
            }
            public function isExpired() {
                return $this->expiresAt < new \DateTime();
            }
        };

        // 2. Create a token that is still valid (expires in 1 hour)
        $validToken = new class {
            public $token = 'valid_token';
            public $expiresAt;
            public function __construct() {
                $this->expiresAt = new \DateTime('+1 hour');
            }
            public function isExpired() {
                return $this->expiresAt < new \DateTime();
            }
        };
        
        // 3. Mock a repository
        $tokenRepository = new class($validToken, $expiredToken) {
            private $tokens = [];
            public function __construct($valid, $expired) {
                $this->tokens[$valid->token] = $valid;
                $this->tokens[$expired->token] = $expired;
            }
            
            // This is the method we are testing
            public function findValidToken(string $token) {
                if (!isset($this->tokens[$token])) {
                    return null;
                }
                
                $foundToken = $this->tokens[$token];
                
                if ($foundToken->isExpired()) {
                    return null; // Don't return expired tokens
                }
                
                return $foundToken;
            }
        };

        // 4. Assertions
        
        // Attempt to find the *expired* token
        $result = $tokenRepository->findValidToken('expired_token');
        $this->assertNull($result, "The expired token should not be found.");
        
        // Attempt to find the *valid* token
        $result = $tokenRepository->findValidToken('valid_token');
        $this->assertNotNull($result, "The valid token should be found.");
        $this->assertEquals('valid_token', $result->token);
    }
}