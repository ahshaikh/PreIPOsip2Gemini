<?php

namespace App\Http\Middleware;

use App\Models\WebhookEventLedger;
use App\Services\Webhooks\WebhookVerifierRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class WebhookReplayGuard
{
    public function __construct(
        protected WebhookVerifierRegistry $registry
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $provider = null): Response
    {
        // 1. Resolve provider from route if not provided explicitly in middleware param
        $provider = $provider ?? $request->route('provider');

        if (!$provider) {
            Log::error('WebhookReplayGuard: Provider not resolved.');
            return response()->json(['error' => 'Provider not specified'], 400);
        }

        $payload = $request->getContent();
        // Laravel's headers() method returns an array of arrays
        $headersRaw = $request->headers->all();
        $normalizedHeaders = array_change_key_case($headersRaw, CASE_LOWER);
        $headersFlat = array_map(fn($v) => is_array($v) ? ($v[0] ?? null) : $v, $normalizedHeaders);

        try {
            // Get verifier directly from registry
            $verifier = $this->registry->get($provider);
        } catch (\Exception $e) {
            Log::error("WebhookReplayGuard: Unknown provider {$provider}");
            return response()->json(['error' => 'Unknown provider'], 400);
        }

        // 2. Verify Signature
        $signatureVerified = $verifier->verify($payload, $headersFlat);
        if (!$signatureVerified) {
            Log::warning("WebhookReplayGuard: Invalid signature for {$provider}", ['ip' => $request->ip()]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // 3. Timestamp Validation (Replay Protection Window)
        $timestampValid = $verifier->isTimestampValid($headersFlat);
        if (!$timestampValid) {
            Log::warning("WebhookReplayGuard: Expired or invalid timestamp for {$provider}");
            return response()->json(['error' => 'Invalid timestamp'], 401);
        }

        // 4. Atomic Event Ledger Entry (Anti-Replay)
        $eventId = $verifier->extractEventId($payload);
        $payloadHash = hash('sha256', $payload);
        $eventTimestamp = $verifier->extractEventTimestamp($payload);
        $resourceId = $verifier->extractResourceId($payload);
        $resourceType = $verifier->extractResourceType($payload);
        
        // Use atomic firstOrCreate to check/record the event
        $ledgerEntry = WebhookEventLedger::firstOrCreate(
            ['provider' => $provider, 'event_id' => $eventId],
            [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'payload_hash' => $payloadHash,
                'payload_size' => strlen($payload),
                'headers_hash' => hash('sha256', json_encode($headersFlat)),
                'event_timestamp' => $eventTimestamp,
                'signature_verified' => true,
                'timestamp_valid' => true,
                'processing_status' => 'pending',
                'received_at' => now(),
            ]
        );

        // 5. Replay Detection
        if (!$ledgerEntry->wasRecentlyCreated) {
            $ledgerEntry->update(['replay_detected' => true]);
            
            // Check for payload mismatch (Malicious tampering detection)
            if ($ledgerEntry->payload_hash !== $payloadHash) {
                $ledgerEntry->update(['payload_mismatch_detected' => true]);
                Log::critical("SECURITY ALERT: Webhook payload mismatch detected for replay. Possible tampering attempt.", [
                    'provider' => $provider,
                    'event_id' => $eventId,
                    'original_hash' => $ledgerEntry->payload_hash,
                    'new_hash' => $payloadHash,
                    'ip' => $request->ip()
                ]);
            }

            Log::info("WebhookReplayGuard: Duplicate event detected for {$provider}: {$eventId}");
            
            // For idempotency, we return 200 OK for replays but skip processing
            return response()->json(['status' => 'duplicate', 'message' => 'Event already received'], 200);
        }

        // Attach ledger entry and verifier info to request for controller use
        $request->attributes->set('webhook_ledger_id', $ledgerEntry->id);
        $request->attributes->set('webhook_verifier', $verifier);
        $request->attributes->set('webhook_provider', $provider);

        return $next($request);
    }
}
