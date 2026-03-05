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
        
        try {
            // Atomic insertion to prevent race conditions
            $ledgerEntry = WebhookEventLedger::create([
                'provider' => $provider,
                'event_id' => $eventId,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'payload_hash' => $payloadHash,
                'payload_size' => strlen($payload),
                'headers_hash' => hash('sha256', json_encode($headersFlat)),
                'event_timestamp' => $eventTimestamp,
                'signature_verified' => true,
                'timestamp_valid' => true,
                'processing_status' => 'RECEIVED', // Step 1: RECEIVED
                'received_at' => now(),
            ]);
            $isNew = true;
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Duplicate event detection (Atomic)
            // MySQL error code 1062 = Duplicate entry
            if (isset($e->getPrevious()->errorInfo[1]) && $e->getPrevious()->errorInfo[1] != 1062) {
                throw $e;
            }

            $ledgerEntry = WebhookEventLedger::where('provider', $provider)
                ->where('event_id', $eventId)
                ->first();

            if (!$ledgerEntry) {
                // If the constraint violation wasn't for (provider, event_id), re-throw
                throw $e;
            }
            $isNew = false;
        }

        // 5. Replay & Integrity Detection (Payload Integrity Enforcement)
        if (!$isNew) {
            $ledgerEntry->update(['replay_detected' => true]);
            
            // Check for payload mismatch (Malicious tampering detection)
            if ($ledgerEntry->payload_hash !== $payloadHash) {
                $ledgerEntry->update(['payload_mismatch_detected' => true]);
                Log::critical("SECURITY ALERT: Webhook payload mismatch detected for replay. Possible tampering attempt. Event ID: {$eventId}, Provider: {$provider}", [
                    'provider' => $provider,
                    'event_id' => $eventId,
                    'original_hash' => $ledgerEntry->payload_hash,
                    'new_hash' => $payloadHash,
                    'ip' => $request->ip()
                ]);

                // Requirement: Do NOT process the event on payload mismatch
                return response()->json(['error' => 'Security integrity failure', 'message' => 'Payload mismatch detected'], 403);
            }

            Log::info("WebhookReplayGuard: Duplicate event detected for {$provider}: {$eventId}");
            
            // For idempotency, we return 200 OK for replays but skip processing
            return response()->json(['status' => 'duplicate', 'message' => 'Event already received'], 200);
        }

        // Step 2: VALIDATED (Signature and Timestamp verified, and it's a new unique event)
        $ledgerEntry->update(['processing_status' => 'VALIDATED']);

        // Attach ledger entry and verifier info to request for controller use
        $request->attributes->set('webhook_ledger_id', $ledgerEntry->id);
        $request->attributes->set('webhook_verifier', $verifier);
        $request->attributes->set('webhook_provider', $provider);

        return $next($request);
    }
}
