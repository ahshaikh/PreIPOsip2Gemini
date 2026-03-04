<?php

namespace App\Services\Webhooks\Contracts;

interface WebhookEventHandler
{
    /**
     * Handle the webhook event payload.
     *
     * @param array $payload
     * @return void
     */
    public function handle(array $payload): void;
}
