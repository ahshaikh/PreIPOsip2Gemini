<?php

namespace App\Services\Webhooks\Handlers;

use App\Services\Webhooks\Contracts\WebhookEventHandler;
use App\Services\PaymentWebhookService;

class RazorpayPaymentFailedHandler implements WebhookEventHandler
{
    public function __construct(
        protected PaymentWebhookService $paymentWebhookService
    ) {}

    public function handle(array $payload): void
    {
        $this->paymentWebhookService->handleFailedPayment($payload['payload']['payment']['entity']);
    }
}
