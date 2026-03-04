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
        $data = json_decode($payload, true);
        
        // Prioritize actual Event ID from Razorpay
        if (isset($data['id']) && str_starts_with($data['id'], 'evt_')) {
            return $data['id'];
        }

        // Fallback to entity ID if event ID is not standard
        return $data['id'] ?? 
               $data['payload']['payment']['entity']['id'] ?? 
               $data['payload']['refund']['entity']['id'] ?? 
               'unknown_rzp_' . uniqid();
    }

    public function extractEventType(string $payload): string
    {
        $data = json_decode($payload, true);
        return $data['event'] ?? 'unknown';
    }

    public function extractEventTimestamp(string $payload): int
    {
        $data = json_decode($payload, true);
        return $data['created_at'] ?? time();
    }

    public function extractResourceId(string $payload): ?string
    {
        $data = json_decode($payload, true);
        
        // Priority based on event type if available
        $event = $data['event'] ?? '';
        
        if (str_contains($event, 'payment')) {
            return $data['payload']['payment']['entity']['id'] ?? null;
        }
        
        if (str_contains($event, 'subscription')) {
            return $data['payload']['subscription']['entity']['id'] ?? $data['payload']['payment']['entity']['subscription_id'] ?? null;
        }

        if (str_contains($event, 'refund')) {
            return $data['payload']['refund']['entity']['id'] ?? null;
        }

        return $data['payload']['payment']['entity']['id'] ?? 
               $data['payload']['refund']['entity']['id'] ?? 
               $data['id'] ?? null;
    }

    public function extractResourceType(string $payload): ?string
    {
        $data = json_decode($payload, true);
        $event = $data['event'] ?? '';

        if (str_contains($event, 'payment')) return 'payment';
        if (str_contains($event, 'subscription')) return 'subscription';
        if (str_contains($event, 'refund')) return 'refund';
        if (str_contains($event, 'dispute')) return 'dispute';
        if (str_contains($event, 'settlement')) return 'settlement';

        return 'unknown';
    }
}
