<?php

namespace App\Services\Webhooks;

use App\Services\Webhooks\Contracts\WebhookEventHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class EventRouter
{
    /**
     * Dispatch the event to the appropriate handler.
     *
     * @param string $provider
     * @param string $eventType
     * @param array $payload
     * @param array $metadata
     * @return void
     */
    public function dispatch(string $provider, string $eventType, array $payload, array $metadata = []): void
    {
        $key = "{$provider}.{$eventType}";
        $registry = Config::get('webhook_events', []);

        if (!isset($registry[$key])) {
            Log::debug("EventRouter: No handler registered for {$key}. Skipping.");
            return;
        }

        $handlerClass = $registry[$key];
        
        try {
            /** @var WebhookEventHandler $handler */
            $handler = App::make($handlerClass);
            
            Log::debug("EventRouter: Dispatching {$key} to " . get_class($handler));
            
            $handler->handle($payload, $metadata);
        } catch (\Exception $e) {
            Log::error("EventRouter Error dispatching {$key}: " . $e->getMessage(), [
                'handler' => $handlerClass,
                'exception' => $e
            ]);
            throw $e;
        }
    }
}
