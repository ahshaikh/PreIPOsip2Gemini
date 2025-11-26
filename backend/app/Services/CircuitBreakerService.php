<?php
// V-PERFORMANCE-CIRCUIT-BREAKER - Circuit breaker pattern for external API resilience

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker Pattern Implementation
 *
 * Prevents cascading failures by tracking external service failures and automatically
 * "opening" the circuit when failure threshold is reached.
 *
 * States:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Too many failures, requests fail immediately
 * - HALF_OPEN: Testing if service recovered, limited requests allowed
 *
 * @see https://martinfowler.com/bliki/CircuitBreaker.html
 */
class CircuitBreakerService
{
    // Circuit states
    const STATE_CLOSED = 'closed';
    const STATE_OPEN = 'open';
    const STATE_HALF_OPEN = 'half_open';

    // Configuration
    private int $failureThreshold;      // Failures before opening circuit
    private int $successThreshold;      // Successes to close circuit from half-open
    private int $timeout;               // Seconds before attempting to close
    private int $halfOpenAttempts;      // Max attempts in half-open state

    // Service name for cache keys
    private string $serviceName;

    /**
     * @param string $serviceName Unique identifier for the service
     * @param int $failureThreshold Number of failures before opening circuit (default: 5)
     * @param int $successThreshold Number of successes to close circuit (default: 2)
     * @param int $timeout Seconds before retry (default: 60)
     * @param int $halfOpenAttempts Max attempts in half-open state (default: 3)
     */
    public function __construct(
        string $serviceName,
        int $failureThreshold = 5,
        int $successThreshold = 2,
        int $timeout = 60,
        int $halfOpenAttempts = 3
    ) {
        $this->serviceName = $serviceName;
        $this->failureThreshold = $failureThreshold;
        $this->successThreshold = $successThreshold;
        $this->timeout = $timeout;
        $this->halfOpenAttempts = $halfOpenAttempts;
    }

    /**
     * Execute a callable with circuit breaker protection
     *
     * @param callable $callback The external API call to protect
     * @param mixed $fallback Value to return when circuit is open
     * @return mixed Result of callback or fallback value
     * @throws \Exception If callback fails and no fallback provided
     */
    public function call(callable $callback, $fallback = null)
    {
        $state = $this->getState();

        // If circuit is open, return fallback immediately
        if ($state === self::STATE_OPEN) {
            if ($this->shouldAttemptReset()) {
                $this->transitionToHalfOpen();
            } else {
                Log::warning("Circuit breaker OPEN for {$this->serviceName}, failing fast");
                if ($fallback !== null) {
                    return is_callable($fallback) ? $fallback() : $fallback;
                }
                throw new \RuntimeException("Service {$this->serviceName} is unavailable (circuit open)");
            }
        }

        // If half-open, check if we've exceeded attempt limit
        if ($state === self::STATE_HALF_OPEN) {
            $attempts = $this->getHalfOpenAttempts();
            if ($attempts >= $this->halfOpenAttempts) {
                $this->transitionToOpen();
                Log::warning("Circuit breaker reopened for {$this->serviceName} after {$attempts} failed attempts");
                if ($fallback !== null) {
                    return is_callable($fallback) ? $fallback() : $fallback;
                }
                throw new \RuntimeException("Service {$this->serviceName} is unavailable (circuit reopened)");
            }
        }

        try {
            // Execute the callback
            $result = $callback();

            // Record success
            $this->recordSuccess();

            return $result;
        } catch (\Exception $e) {
            // Record failure
            $this->recordFailure();

            Log::error("Circuit breaker recorded failure for {$this->serviceName}: {$e->getMessage()}");

            // Re-throw or return fallback
            if ($fallback !== null) {
                return is_callable($fallback) ? $fallback() : $fallback;
            }
            throw $e;
        }
    }

    /**
     * Get current circuit state
     */
    private function getState(): string
    {
        return Cache::get($this->getStateKey(), self::STATE_CLOSED);
    }

    /**
     * Set circuit state
     */
    private function setState(string $state): void
    {
        Cache::put($this->getStateKey(), $state, 3600); // 1 hour TTL
    }

    /**
     * Get failure count
     */
    private function getFailureCount(): int
    {
        return (int) Cache::get($this->getFailureCountKey(), 0);
    }

    /**
     * Increment failure count
     */
    private function incrementFailureCount(): int
    {
        $key = $this->getFailureCountKey();
        $count = $this->getFailureCount() + 1;
        Cache::put($key, $count, 3600);
        return $count;
    }

    /**
     * Reset failure count
     */
    private function resetFailureCount(): void
    {
        Cache::forget($this->getFailureCountKey());
    }

    /**
     * Get success count (for half-open state)
     */
    private function getSuccessCount(): int
    {
        return (int) Cache::get($this->getSuccessCountKey(), 0);
    }

    /**
     * Increment success count
     */
    private function incrementSuccessCount(): int
    {
        $key = $this->getSuccessCountKey();
        $count = $this->getSuccessCount() + 1;
        Cache::put($key, $count, 3600);
        return $count;
    }

    /**
     * Reset success count
     */
    private function resetSuccessCount(): void
    {
        Cache::forget($this->getSuccessCountKey());
    }

    /**
     * Get half-open attempts count
     */
    private function getHalfOpenAttempts(): int
    {
        return (int) Cache::get($this->getHalfOpenAttemptsKey(), 0);
    }

    /**
     * Increment half-open attempts
     */
    private function incrementHalfOpenAttempts(): void
    {
        $key = $this->getHalfOpenAttemptsKey();
        $count = $this->getHalfOpenAttempts() + 1;
        Cache::put($key, $count, 3600);
    }

    /**
     * Reset half-open attempts
     */
    private function resetHalfOpenAttempts(): void
    {
        Cache::forget($this->getHalfOpenAttemptsKey());
    }

    /**
     * Record a successful call
     */
    private function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            $successCount = $this->incrementSuccessCount();

            if ($successCount >= $this->successThreshold) {
                $this->transitionToClosed();
                Log::info("Circuit breaker CLOSED for {$this->serviceName} after {$successCount} successful attempts");
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure count on success in closed state
            $this->resetFailureCount();
        }
    }

    /**
     * Record a failed call
     */
    private function recordFailure(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            $this->incrementHalfOpenAttempts();
            $this->transitionToOpen();
        } elseif ($state === self::STATE_CLOSED) {
            $failureCount = $this->incrementFailureCount();

            if ($failureCount >= $this->failureThreshold) {
                $this->transitionToOpen();
                Log::warning("Circuit breaker OPENED for {$this->serviceName} after {$failureCount} failures");
            }
        }
    }

    /**
     * Check if enough time has passed to attempt reset
     */
    private function shouldAttemptReset(): bool
    {
        $openedAt = Cache::get($this->getOpenedAtKey());

        if (!$openedAt) {
            return true;
        }

        return (time() - $openedAt) >= $this->timeout;
    }

    /**
     * Transition to OPEN state
     */
    private function transitionToOpen(): void
    {
        $this->setState(self::STATE_OPEN);
        Cache::put($this->getOpenedAtKey(), time(), 3600);
        $this->resetSuccessCount();
        $this->resetHalfOpenAttempts();
    }

    /**
     * Transition to HALF_OPEN state
     */
    private function transitionToHalfOpen(): void
    {
        $this->setState(self::STATE_HALF_OPEN);
        $this->resetSuccessCount();
        $this->resetHalfOpenAttempts();
        Log::info("Circuit breaker transitioned to HALF_OPEN for {$this->serviceName}");
    }

    /**
     * Transition to CLOSED state
     */
    private function transitionToClosed(): void
    {
        $this->setState(self::STATE_CLOSED);
        $this->resetFailureCount();
        $this->resetSuccessCount();
        $this->resetHalfOpenAttempts();
        Cache::forget($this->getOpenedAtKey());
    }

    /**
     * Manually reset the circuit breaker
     */
    public function reset(): void
    {
        $this->transitionToClosed();
        Log::info("Circuit breaker manually reset for {$this->serviceName}");
    }

    /**
     * Get current circuit breaker status
     */
    public function getStatus(): array
    {
        return [
            'service' => $this->serviceName,
            'state' => $this->getState(),
            'failure_count' => $this->getFailureCount(),
            'success_count' => $this->getSuccessCount(),
            'half_open_attempts' => $this->getHalfOpenAttempts(),
            'thresholds' => [
                'failure' => $this->failureThreshold,
                'success' => $this->successThreshold,
                'timeout' => $this->timeout,
            ],
        ];
    }

    // Cache key helpers
    private function getStateKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:state";
    }

    private function getFailureCountKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:failures";
    }

    private function getSuccessCountKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:successes";
    }

    private function getHalfOpenAttemptsKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:half_open_attempts";
    }

    private function getOpenedAtKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:opened_at";
    }
}
