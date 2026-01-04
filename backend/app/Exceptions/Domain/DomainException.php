<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Exception;

/**
 * Base Domain Exception
 *
 * All domain-level exceptions inherit from this base class.
 * These are NOT HTTP exceptions - they represent domain rule violations.
 *
 * Controllers should catch these and convert to appropriate HTTP responses.
 *
 * @package App\Exceptions\Domain
 */
abstract class DomainException extends Exception
{
    /**
     * Get error code for API responses
     *
     * @return string
     */
    abstract public function getErrorCode(): string;

    /**
     * Get additional context data
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return [];
    }
}
