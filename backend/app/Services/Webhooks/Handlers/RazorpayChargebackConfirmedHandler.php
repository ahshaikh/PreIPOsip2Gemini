<?php

namespace App\Services\Webhooks\Handlers;

use App\Services\Webhooks\Contracts\WebhookEventHandler;
use App\Services\PaymentWebhookService;

class RazorpayChargebackConfirmedHandler implements WebhookEventHandler
{
    public function __construct(
        protected PaymentWebhookService $paymentWebhookService
    ) {}

    public function handle(array $payload): void
    {
        $this->paymentWebhookService->handleChargebackConfirmed($payload['payload']['dispute']['entity'] ?? $payload);
    }
}
