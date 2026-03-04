<?php

namespace App\Services\Webhooks\Handlers;

use App\Services\Webhooks\Contracts\WebhookEventHandler;
use Illuminate\Support\Facades\Log;

class StripePaymentFailedHandler implements WebhookEventHandler
{
    public function handle(array $payload): void
    {
        Log::info("StripePaymentFailedHandler: Event processed.");
        // Logic for stripe will go here
    }
}
