<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

/**
 * SubscriptionNotFoundException
 *
 * Thrown when an operation requires a subscription but none exists.
 *
 * @package App\Exceptions\Domain
 */
final class SubscriptionNotFoundException extends DomainException
{
    private int $userId;

    /**
     * Create exception for missing subscription
     *
     * @param int $userId User ID
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;

        parent::__construct("No active subscription found for user ID {$userId}");
    }

    /**
     * Get error code for API responses
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return 'SUBSCRIPTION_NOT_FOUND';
    }

    /**
     * Get user ID
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Get additional context for API response
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return [
            'user_id' => $this->userId,
        ];
    }
}
