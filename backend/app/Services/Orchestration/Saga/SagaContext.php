<?php

namespace App\Services\Orchestration\Saga;

use App\Models\SagaExecution;

/**
 * SagaContext - Carries State Through Saga Execution
 *
 * Immutable context object passed to all operations in a saga.
 * Contains metadata and provides access to saga execution record.
 */
class SagaContext
{
    private string $sagaId;
    private array $metadata;
    private SagaExecution $sagaExecution;
    private array $sharedState = [];

    public function __construct(string $sagaId, array $metadata, SagaExecution $sagaExecution)
    {
        $this->sagaId = $sagaId;
        $this->metadata = $metadata;
        $this->sagaExecution = $sagaExecution;
    }

    public function getId(): string
    {
        return $this->sagaId;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function get(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    public function getSagaExecution(): SagaExecution
    {
        return $this->sagaExecution;
    }

    /**
     * Share data between saga steps
     * (e.g., TDS calculation result needed by wallet debit step)
     */
    public function setShared(string $key, $value): void
    {
        $this->sharedState[$key] = $value;
    }

    public function getShared(string $key, $default = null)
    {
        return $this->sharedState[$key] ?? $default;
    }

    public function hasShared(string $key): bool
    {
        return isset($this->sharedState[$key]);
    }
}
