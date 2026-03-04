<?php

namespace App\Services\Webhooks\Handlers;

use App\Services\Webhooks\Contracts\WebhookEventHandler;
use App\Services\Webhooks\Traits\WebhookIdempotency;
use Illuminate\Support\Facades\Log;

class StripePaymentFailedHandler implements WebhookEventHandler
{
    use WebhookIdempotency;

    public function handle(array $payload, array $metadata): void
    {
        $this->runIdempotent($metadata, function () use ($payload) {
            Log::info("StripePaymentFailedHandler: Event processed.");
            // Logic for stripe will go here
        });
    }
}
