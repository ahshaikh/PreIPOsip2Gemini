<?php

namespace App\Services\Webhooks\Handlers;

use App\Services\Webhooks\Contracts\WebhookEventHandler;
use App\Services\PaymentWebhookService;
use App\Services\Webhooks\Traits\WebhookIdempotency;

class RazorpayPaymentFailedHandler implements WebhookEventHandler
{
    use WebhookIdempotency;

    public function __construct(
        protected PaymentWebhookService $paymentWebhookService
    ) {}

    public function handle(array $payload, array $metadata): void
    {
        $this->runIdempotent($metadata, function () use ($payload) {
            $this->paymentWebhookService->handleFailedPayment($payload['payload']['payment']['entity']);
        });
    }
}
