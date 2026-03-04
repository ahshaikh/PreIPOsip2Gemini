<?php

namespace App\Services\Webhooks\Handlers;

use App\Services\Webhooks\Contracts\WebhookEventHandler;
use Illuminate\Support\Facades\Log;

class StripePaymentSucceededHandler implements WebhookEventHandler
{
    public function handle(array $payload): void
    {
        Log::info("StripePaymentSucceededHandler: Event processed.");
        // Logic for stripe will go here
    }
}
