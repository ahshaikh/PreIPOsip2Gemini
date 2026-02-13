<?php
// V-CONTRACT-HARDENING-FINAL: Override schema violation exception
// Thrown when an override payload violates schema constraints.
// This is a HARD FAILURE - no silent fallbacks allowed.

namespace App\Exceptions;

use Exception;

/**
 * OverrideSchemaViolationException
 *
 * Thrown when a regulatory override payload violates schema rules.
 * This exception MUST NOT be caught and silently ignored.
 * It indicates a contract violation that must be surfaced to the admin.
 *
 * ALERT LEVEL: MEDIUM
 * LOG CHANNEL: financial_contract
 */
class OverrideSchemaViolationException extends Exception
{
    protected $code = 422;

    protected ?string $scope;
    protected ?array $payload;
    protected ?string $fieldName;

    public function __construct(
        string $message,
        ?string $scope = null,
        ?array $payload = null,
        ?string $fieldName = null
    ) {
        $this->scope = $scope;
        $this->payload = $payload;
        $this->fieldName = $fieldName;

        parent::__construct("[OVERRIDE SCHEMA VIOLATION] " . $message);
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    /**
     * V-CONTRACT-HARDENING-FINAL: Return structured context for logging
     */
    public function reportContext(): array
    {
        return [
            'exception_type' => 'OverrideSchemaViolationException',
            'alert_level' => 'MEDIUM',
            'scope' => $this->scope,
            'field_name' => $this->fieldName,
            'payload_keys' => $this->payload ? array_keys($this->payload) : null,
            'action_required' => 'Admin attempted invalid override configuration',
            'user_impact' => 'Override creation blocked',
        ];
    }
}
