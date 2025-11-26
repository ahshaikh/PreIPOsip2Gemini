<?php
// V-PERFORMANCE-CIRCUIT-BREAKER - Trait for adding circuit breaker protection to services

namespace App\Services\Traits;

use App\Services\CircuitBreakerService;

/**
 * Trait for adding circuit breaker protection to service classes
 *
 * Usage:
 * ```php
 * class MyExternalService
 * {
 *     use HasCircuitBreaker;
 *
 *     public function callExternalApi($data)
 *     {
 *         return $this->withCircuitBreaker('my-service', function() use ($data) {
 *             // Your external API call here
 *             return Http::post('https://api.example.com', $data);
 *         }, $fallbackValue);
 *     }
 * }
 * ```
 */
trait HasCircuitBreaker
{
    /**
     * Circuit breaker instances cache
     */
    private array $circuitBreakers = [];

    /**
     * Execute a callable with circuit breaker protection
     *
     * @param string $serviceName Unique identifier for the service
     * @param callable $callback The external API call to protect
     * @param mixed $fallback Value to return when circuit is open
     * @param array $options Circuit breaker configuration options
     * @return mixed
     */
    protected function withCircuitBreaker(
        string $serviceName,
        callable $callback,
        $fallback = null,
        array $options = []
    ) {
        $breaker = $this->getCircuitBreaker($serviceName, $options);
        return $breaker->call($callback, $fallback);
    }

    /**
     * Get or create a circuit breaker instance for a service
     *
     * @param string $serviceName
     * @param array $options Configuration options:
     *                       - failureThreshold: int (default: 5)
     *                       - successThreshold: int (default: 2)
     *                       - timeout: int (default: 60)
     *                       - halfOpenAttempts: int (default: 3)
     * @return CircuitBreakerService
     */
    protected function getCircuitBreaker(string $serviceName, array $options = []): CircuitBreakerService
    {
        if (!isset($this->circuitBreakers[$serviceName])) {
            $this->circuitBreakers[$serviceName] = new CircuitBreakerService(
                $serviceName,
                $options['failureThreshold'] ?? 5,
                $options['successThreshold'] ?? 2,
                $options['timeout'] ?? 60,
                $options['halfOpenAttempts'] ?? 3
            );
        }

        return $this->circuitBreakers[$serviceName];
    }

    /**
     * Get circuit breaker status for a service
     *
     * @param string $serviceName
     * @return array|null
     */
    protected function getCircuitBreakerStatus(string $serviceName): ?array
    {
        if (isset($this->circuitBreakers[$serviceName])) {
            return $this->circuitBreakers[$serviceName]->getStatus();
        }

        return null;
    }

    /**
     * Reset a circuit breaker
     *
     * @param string $serviceName
     * @return void
     */
    protected function resetCircuitBreaker(string $serviceName): void
    {
        if (isset($this->circuitBreakers[$serviceName])) {
            $this->circuitBreakers[$serviceName]->reset();
        }
    }
}
