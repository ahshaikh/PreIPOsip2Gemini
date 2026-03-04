<?php

namespace App\Services\Webhooks;

use App\Services\Webhooks\Contracts\WebhookVerifier;
use Illuminate\Support\Facades\App;

class RazorpayWebhookVerifier implements WebhookVerifier
{
    protected string $secret;

    public function __construct()
    {
        $this->secret = App::environment('testing')
            ? config('webhooks.test_secrets.razorpay')
            : config('webhooks.razorpay.secret');
    }

    public function verify(string $payload, array $headers): bool
    {
        $signature = $headers['x-razorpay-signature'] ?? null;
        if (!$signature) return false;

        $expected = hash_hmac('sha256', $payload, $this->secret);
        return hash_equals($expected, $signature);
    }

    public function isTimestampValid(array $headers): bool
    {
        // Razorpay doesn't include timestamp in signature header like Stripe
        return true;
    }

    public function generateTestSignature(string $payload): array
    {
        $hmac = hash_hmac('sha256', $payload, $this->secret);
        return [
            'x-razorpay-signature' => $hmac,
        ];
    }

    public function extractEventId(string $payload): string
    {
        // For Razorpay, we can use the entity ID or the event ID
        $data = json_decode($payload, true);
        return $data['payload']['payment']['entity']['id'] ?? $data['payload']['refund']['entity']['id'] ?? $data['id'] ?? 'unknown_rzp_' . uniqid();
    }

    public function extractEventType(string $payload): string
    {
        $data = json_decode($payload, true);
        return $data['event'] ?? 'unknown';
    }
}
