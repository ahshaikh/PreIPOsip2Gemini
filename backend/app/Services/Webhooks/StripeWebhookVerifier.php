<?php

namespace App\Services\Webhooks;

use App\Services\Webhooks\Contracts\WebhookVerifier;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class StripeWebhookVerifier implements WebhookVerifier
{
    protected string $secret;

    public function __construct()
    {
        $this->secret = App::environment('testing')
            ? config('webhooks.test_secrets.stripe')
            : config('webhooks.stripe.secret');
    }

    public function verify(string $payload, array $headers): bool
    {
        $header = $headers['stripe-signature'] ?? null;
        if (!$header) return false;

        $parts = explode(',', $header);
        $timestamp = null;
        $signature = null;

        foreach ($parts as $part) {
            if (str_contains($part, '=')) {
                [$key, $value] = explode('=', $part, 2);
                $key = trim($key);
                if ($key === 't') {
                    $timestamp = trim($value);
                } elseif ($key === 'v1') {
                    $signature = trim($value);
                }
            }
        }

        if (!$timestamp || !$signature) return false;

        $signedPayload = "{$timestamp}.{$payload}";
        $expected = hash_hmac('sha256', $signedPayload, $this->secret);

        return hash_equals($expected, $signature);
    }

    public function isTimestampValid(array $headers): bool
    {
        $header = $headers['stripe-signature'] ?? null;
        if (!$header) return false;

        $parts = explode(',', $header);
        $timestamp = null;

        foreach ($parts as $part) {
            if (str_contains($part, '=')) {
                [$key, $value] = explode('=', $part, 2);
                if (trim($key) === 't') {
                    $timestamp = trim($value);
                    break;
                }
            }
        }

        if (!$timestamp) return false;

        return abs(time() - (int) $timestamp) <= 300;
    }

    public function generateTestSignature(string $payload): array
    {
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $hmac = hash_hmac('sha256', $signedPayload, $this->secret);

        return [
            'stripe-signature' => "t={$timestamp},v1={$hmac}",
        ];
    }

    public function extractEventId(string $payload): string
    {
        $data = json_decode($payload, true);
        return $data['id'] ?? 'unknown_stripe_' . uniqid();
    }

    public function extractEventType(string $payload): string
    {
        $data = json_decode($payload, true);
        return $data['type'] ?? 'unknown';
    }

    public function extractEventTimestamp(string $payload): int
    {
        $data = json_decode($payload, true);
        return $data['created'] ?? time();
    }

    public function extractResourceId(string $payload): ?string
    {
        $data = json_decode($payload, true);
        return $data['data']['object']['id'] ?? null;
    }

    public function extractResourceType(string $payload): ?string
    {
        $data = json_decode($payload, true);
        return $data['data']['object']['object'] ?? 'unknown';
    }
}
