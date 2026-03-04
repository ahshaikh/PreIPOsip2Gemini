<?php

namespace App\Services\Webhooks\Contracts;

interface WebhookEventHandler
{
    /**
     * Handle the webhook event payload.
     *
     * @param array $payload
     * @param array $metadata
     * @return void
     */
    public function handle(array $payload, array $metadata): void;
}
