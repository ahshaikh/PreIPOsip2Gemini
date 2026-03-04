<?php

namespace App\Services\Webhooks;

use App\Services\Webhooks\Contracts\WebhookVerifier;
use Illuminate\Support\Facades\App;

class GenericHmacWebhookVerifier implements WebhookVerifier
{
    protected string $secret;

    public function __construct()
    {
        $this->secret = App::environment('testing')
            ? config('webhooks.test_secrets.hmac')
            : config('webhooks.hmac.secret');
    }

    public function verify(string $payload, array $headers): bool
    {
        $signature = $headers['x-signature'] ?? null;
        if (!$signature) return false;

        $expected = hash_hmac('sha256', $payload, $this->secret);
        return hash_equals($expected, $signature);
    }

    public function isTimestampValid(array $headers): bool
    {
        return true;
    }

    public function generateTestSignature(string $payload): array
    {
        $hmac = hash_hmac('sha256', $payload, $this->secret);
        return [
            'x-signature' => $hmac,
        ];
    }

    public function extractEventId(string $payload): string
    {
        $data = json_decode($payload, true);
        return $data['id'] ?? 'unknown_hmac_' . uniqid();
    }

    public function extractEventType(string $payload): string
    {
        $data = json_decode($payload, true);
        return $data['event'] ?? $data['type'] ?? 'unknown';
    }

    public function extractEventTimestamp(string $payload): int
    {
        $data = json_decode($payload, true);
        return $data['timestamp'] ?? $data['created_at'] ?? time();
    }

    public function extractResourceId(string $payload): ?string
    {
        $data = json_decode($payload, true);
        return $data['resource_id'] ?? $data['id'] ?? null;
    }

    public function extractResourceType(string $payload): ?string
    {
        $data = json_decode($payload, true);
        return $data['resource_type'] ?? 'unknown';
    }
}
