<?php
// V-PERFORMANCE-CIRCUIT-BREAKER - SMS service with circuit breaker protection

namespace App\Services;

use App\Models\User;
use App\Services\Traits\HasCircuitBreaker;
use Illuminate\Support\Facades\Log;

/**
 * Resilient SMS Service with Circuit Breaker Protection
 *
 * Wraps SmsService with circuit breaker to prevent cascading failures when
 * SMS gateway (MSG91, Twilio, etc.) is down or slow.
 *
 * Configuration:
 * - failureThreshold: 3 failures (SMS gateways can be flaky)
 * - successThreshold: 2 successes to close circuit
 * - timeout: 300 seconds (5 minutes - SMS is not critical path)
 * - halfOpenAttempts: 2 retry attempts
 *
 * Fallback Behavior:
 * - send(): Returns null and logs warning (SMS is non-critical)
 * - sendBatch(): Continues with other sends, logs failures
 */
class ResilientSmsService
{
    use HasCircuitBreaker;

    private SmsService $sms;

    public function __construct(SmsService $sms)
    {
        $this->sms = $sms;
    }

    /**
     * Send SMS with circuit breaker protection
     *
     * @param User $user
     * @param string $message
     * @param string|null $templateSlug
     * @param string|null $dltTemplateId
     * @return mixed SmsLog or null
     */
    public function send(User $user, string $message, ?string $templateSlug = null, ?string $dltTemplateId = null)
    {
        return $this->withCircuitBreaker(
            'sms-gateway',
            fn() => $this->sms->send($user, $message, $templateSlug, $dltTemplateId),
            fallback: function() use ($user, $templateSlug) {
                Log::warning("SMS circuit breaker open - message not sent to {$user->mobile} for template {$templateSlug}");
                return null;
            },
            options: [
                'failureThreshold' => 3, // SMS gateways can be flaky
                'successThreshold' => 2,
                'timeout' => 300, // 5 minutes - SMS is non-critical
                'halfOpenAttempts' => 2,
            ]
        );
    }

    /**
     * Send batch SMS with circuit breaker protection
     *
     * @param array $userIds
     * @param string $templateSlug
     * @param array $variables
     * @return void
     */
    public function sendBatch(array $userIds, string $templateSlug, array $variables = [])
    {
        // Check circuit breaker state before processing batch
        $status = $this->getCircuitBreakerStatus('sms-gateway');
        if ($status && $status['state'] === 'open') {
            Log::warning("SMS circuit breaker open - batch send aborted for " . count($userIds) . " users");
            return;
        }

        // Process batch with circuit breaker protection
        $this->withCircuitBreaker(
            'sms-gateway',
            fn() => $this->sms->sendBatch($userIds, $templateSlug, $variables),
            fallback: function() use ($userIds) {
                Log::warning("SMS circuit breaker opened during batch send - " . count($userIds) . " messages not sent");
            },
            options: [
                'failureThreshold' => 3,
                'successThreshold' => 2,
                'timeout' => 300,
                'halfOpenAttempts' => 2,
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
        return $this->getCircuitBreakerStatus('sms-gateway');
    }

    /**
     * Reset SMS circuit breaker (admin tool)
     *
     * @return void
     */
    public function resetCircuit(): void
    {
        $this->resetCircuitBreaker('sms-gateway');
        Log::info('SMS gateway circuit breaker reset');
    }

    /**
     * Pass through any other methods to underlying SmsService
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return $this->sms->$method(...$arguments);
    }
}
