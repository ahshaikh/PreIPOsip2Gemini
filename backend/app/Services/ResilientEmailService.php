<?php
// V-PERFORMANCE-CIRCUIT-BREAKER - Email service with circuit breaker protection

namespace App\Services;

use App\Models\User;
use App\Services\Traits\HasCircuitBreaker;
use Illuminate\Support\Facades\Log;

/**
 * Resilient Email Service with Circuit Breaker Protection
 *
 * Wraps EmailService with circuit breaker to prevent cascading failures when
 * email service (SMTP, SES, Mailgun, etc.) is down or slow.
 *
 * Configuration:
 * - failureThreshold: 5 failures (email can be more reliable than SMS)
 * - successThreshold: 2 successes to close circuit
 * - timeout: 180 seconds (3 minutes)
 * - halfOpenAttempts: 3 retry attempts
 *
 * Fallback Behavior:
 * - send(): Returns null and logs warning (emails are queued, can retry later)
 * - sendBatch(): Continues with other sends, logs failures
 */
class ResilientEmailService
{
    use HasCircuitBreaker;

    private EmailService $email;

    public function __construct(EmailService $email)
    {
        $this->email = $email;
    }

    /**
     * Send email with circuit breaker protection
     *
     * @param User $user
     * @param string $templateSlug
     * @param array $variables
     * @return mixed EmailLog or null
     */
    public function send(User $user, string $templateSlug, array $variables = [])
    {
        return $this->withCircuitBreaker(
            'email-service',
            fn() => $this->email->send($user, $templateSlug, $variables),
            fallback: function() use ($user, $templateSlug) {
                Log::warning("Email circuit breaker open - email not queued for {$user->email}, template {$templateSlug}");
                return null;
            },
            options: [
                'failureThreshold' => 5,
                'successThreshold' => 2,
                'timeout' => 180, // 3 minutes
                'halfOpenAttempts' => 3,
            ]
        );
    }

    /**
     * Send batch emails with circuit breaker protection
     *
     * @param array $userIds
     * @param string $templateSlug
     * @param array $variables
     * @return void
     */
    public function sendBatch(array $userIds, string $templateSlug, array $variables = [])
    {
        // Check circuit breaker state before processing batch
        $status = $this->getCircuitBreakerStatus('email-service');
        if ($status && $status['state'] === 'open') {
            Log::warning("Email circuit breaker open - batch send aborted for " . count($userIds) . " users");
            return;
        }

        // Process batch with circuit breaker protection
        $this->withCircuitBreaker(
            'email-service',
            fn() => $this->email->sendBatch($userIds, $templateSlug, $variables),
            fallback: function() use ($userIds) {
                Log::warning("Email circuit breaker opened during batch send - " . count($userIds) . " emails not queued");
            },
            options: [
                'failureThreshold' => 5,
                'successThreshold' => 2,
                'timeout' => 180,
                'halfOpenAttempts' => 3,
            ]
        );
    }

    /**
     * Get circuit breaker status
     *
     * @return array|null
     */
    public function getCircuitStatus(): ?array
    {
        return $this->getCircuitBreakerStatus('email-service');
    }

    /**
     * Reset email circuit breaker (admin tool)
     *
     * @return void
     */
    public function resetCircuit(): void
    {
        $this->resetCircuitBreaker('email-service');
        Log::info('Email service circuit breaker reset');
    }

    /**
     * Pass through any other methods to underlying EmailService
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return $this->email->$method(...$arguments);
    }
}
