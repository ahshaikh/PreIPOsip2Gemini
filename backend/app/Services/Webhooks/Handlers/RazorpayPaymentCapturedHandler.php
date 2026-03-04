<?php

namespace App\Services\Webhooks\Handlers;

use App\Services\Webhooks\Contracts\WebhookEventHandler;
use App\Services\PaymentWebhookService;
use App\Services\Webhooks\Traits\WebhookIdempotency;

class RazorpayPaymentCapturedHandler implements WebhookEventHandler
{
    use WebhookIdempotency;

    public function __construct(
        protected PaymentWebhookService $paymentWebhookService
    ) {}

    public function handle(array $payload, array $metadata): void
    {
        $this->runIdempotent($metadata, function () use ($payload) {
            $this->paymentWebhookService->handleSuccessfulPayment($payload['payload']['payment']['entity']);
        });
    }
}
