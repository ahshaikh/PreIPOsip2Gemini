<?php

namespace App\Services\Orchestration\Operations;

/**
 * OperationResult - Standardized Result Object
 *
 * PROTOCOL:
 * - Immutable (created via static factory methods)
 * - Contains success/failure status + message + data
 * - Used by ALL operations for consistent error handling
 */
class OperationResult
{
    private bool $success;
    private string $message;
    private array $data;

    private function __construct(bool $success, string $message, array $data = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
    }

    public static function success(string $message = 'Operation completed', array $data = []): self
    {
        return new self(true, $message, $data);
    }

    public static function failure(string $message, array $data = []): self
    {
        return new self(false, $message, $data);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
