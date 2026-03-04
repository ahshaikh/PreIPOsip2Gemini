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

    /**
     * Extract the event creation timestamp from the payload.
     * Returns Unix timestamp.
     */
    public function extractEventTimestamp(string $payload): int;

    /**
     * Extract the resource ID (e.g. payment ID, subscription ID) from the payload.
     */
    public function extractResourceId(string $payload): ?string;

    /**
     * Extract the resource type (e.g. 'payment', 'subscription') from the payload.
     */
    public function extractResourceType(string $payload): ?string;
}
