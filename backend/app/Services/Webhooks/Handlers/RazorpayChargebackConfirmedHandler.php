<?php

namespace App\Services\Webhooks\Handlers;

use App\Services\Webhooks\Contracts\WebhookEventHandler;
use App\Services\PaymentWebhookService;
use App\Services\Webhooks\Traits\WebhookIdempotency;

class RazorpayChargebackConfirmedHandler implements WebhookEventHandler
{
    use WebhookIdempotency;

    public function __construct(
        protected PaymentWebhookService $paymentWebhookService
    ) {}

    public function handle(array $payload, array $metadata): void
    {
        $this->runIdempotent($metadata, function () use ($payload) {
            $this->paymentWebhookService->handleChargebackConfirmed($payload['payload']['dispute']['entity'] ?? $payload);
        });
    }
}
