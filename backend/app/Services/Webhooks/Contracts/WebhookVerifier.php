<?php

namespace App\Services\Webhooks\Contracts;

interface WebhookVerifier
{
    /**
     * Verify the webhook signature.
     */
    public function verify(string $payload, array $headers): bool;

    /**
     * Generate a valid test signature for this provider.
     */
    public function generateTestSignature(string $payload): array;

    /**
     * Extract the unique event ID from the payload.
     */
    public function extractEventId(string $payload): string;

    /**
     * Check if the timestamp in headers is valid.
     */
    public function isTimestampValid(array $headers): bool;

    /**
     * Get the event type from the payload.
     */
    public function extractEventType(string $payload): string;
}
