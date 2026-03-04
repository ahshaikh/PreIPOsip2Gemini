<?php

namespace App\Services\Webhooks\Handlers;

use App\Services\Webhooks\Contracts\WebhookEventHandler;
use App\Services\PaymentWebhookService;

class RazorpaySettlementProcessedHandler implements WebhookEventHandler
{
    public function __construct(
        protected PaymentWebhookService $paymentWebhookService
    ) {}

    public function handle(array $payload): void
    {
        // Razorpay settlement payload structure might differ, 
        // but PaymentWebhookService expects specific fields.
        $this->paymentWebhookService->handleSettlementProcessed($payload['payload']['settlement']['entity'] ?? $payload);
    }
}
