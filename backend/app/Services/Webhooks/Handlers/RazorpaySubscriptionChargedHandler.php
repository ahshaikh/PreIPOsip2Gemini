<?php

namespace App\Services\Webhooks\Handlers;

use App\Services\Webhooks\Contracts\WebhookEventHandler;
use App\Services\PaymentWebhookService;
use App\Services\Webhooks\Traits\WebhookIdempotency;

class RazorpaySubscriptionChargedHandler implements WebhookEventHandler
{
    use WebhookIdempotency;

    public function __construct(
        protected PaymentWebhookService $paymentWebhookService
    ) {}

    public function handle(array $payload, array $metadata): void
    {
        $this->runIdempotent($metadata, function () use ($payload) {
            $payment = $payload['payload']['payment']['entity'] ?? [];
            $subscription = $payload['payload']['subscription']['entity'] ?? [];
            
            // Ensure keys match what PaymentWebhookService expects
            $data = [
                'subscription_id' => $payment['subscription_id'] ?? $subscription['id'] ?? null,
                'payment_id' => $payment['id'] ?? null,
                'amount' => $payment['amount'] ?? null,
            ];

            $this->paymentWebhookService->handleSubscriptionCharged($data);
        });
    }
}
