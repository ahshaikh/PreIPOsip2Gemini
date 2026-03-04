<?php

namespace App\Services\Webhooks;

use App\Services\Webhooks\Contracts\WebhookVerifier;
use InvalidArgumentException;

class WebhookVerifierRegistry
{
    protected array $verifiers = [];

    public function __construct()
    {
        // Register default verifiers
        $this->register('stripe', new StripeWebhookVerifier());
        $this->register('razorpay', new RazorpayWebhookVerifier());
        $this->register('hmac', new GenericHmacWebhookVerifier());
    }

    /**
     * Register a verifier for a provider.
     */
    public function register(string $provider, WebhookVerifier $verifier): void
    {
        $this->verifiers[$provider] = $verifier;
    }

    /**
     * Get the verifier for a provider.
     */
    public function get(string $provider): WebhookVerifier
    {
        if (!isset($this->verifiers[$provider])) {
            throw new InvalidArgumentException("No verifier registered for provider: {$provider}");
        }

        return $this->verifiers[$provider];
    }
}
