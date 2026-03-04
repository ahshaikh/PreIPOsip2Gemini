<?php

namespace App\Services\Webhooks\Traits;

use App\Models\ProcessedWebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait WebhookIdempotency
{
    /**
     * Run the handler business logic with idempotency protection.
     */
    protected function runIdempotent(array $metadata, callable $callback): void
    {
        DB::transaction(function () use ($metadata, $callback) {
            // Attempt to record the event as processed (Business-Level Idempotency)
            try {
                ProcessedWebhookEvent::create([
                    'provider' => $metadata['provider'],
                    'event_id' => $metadata['event_id'],
                    'resource_type' => $metadata['resource_type'],
                    'resource_id' => $metadata['resource_id'],
                    'event_type' => $metadata['event_type'],
                    'event_timestamp' => $metadata['timestamp'],
                    'payload_hash' => $metadata['payload_hash'] ?? null,
                    'processed_at' => now(),
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // If it's a duplicate, we need to check if the payload_hash matches for forensics
                $existing = ProcessedWebhookEvent::where('provider', $metadata['provider'])
                    ->where('event_id', $metadata['event_id'])
                    ->first();

                if ($existing && $existing->payload_hash !== ($metadata['payload_hash'] ?? null)) {
                    Log::critical("SECURITY ALERT: Business-level payload mismatch for event {$metadata['event_id']}. Forensics: Hash in DB differs from incoming payload hash.", [
                        'provider' => $metadata['provider'],
                        'event_id' => $metadata['event_id'],
                        'db_hash' => $existing->payload_hash,
                        'incoming_hash' => $metadata['payload_hash'] ?? null
                    ]);
                }

                Log::info("WebhookIdempotency: Business logic already executed for event {$metadata['event_id']}. Skipping.", [
                    'provider' => $metadata['provider'],
                    'event_type' => $metadata['event_type']
                ]);
                return;
            } catch (\Exception $e) {
                // For older Laravel versions or different drivers, check message if needed
                if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), '1062 Duplicate entry')) {
                    Log::info("WebhookIdempotency: Business logic already executed for event {$metadata['event_id']} (legacy check). Skipping.");
                    return;
                }
                throw $e;
            }

            // Execute the actual business logic
            $callback();
        });
    }
}
