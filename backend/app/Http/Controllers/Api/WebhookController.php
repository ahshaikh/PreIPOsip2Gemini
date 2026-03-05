<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use App\Jobs\ProcessWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Institutional-Grade Webhook Entry Point
     * ReplayGuard middleware handles:
     * - Signature verification
     * - Timestamp tolerance (for Stripe)
     * - Forensic ledger entry
     * - Replay detection (Anti-Replay)
     */
    public function handle(Request $request, string $provider)
    {
        $payload = $request->getContent();
        $data = json_decode($payload, true);

        // Retrieve attributes set by ReplayGuard middleware
        $ledgerId = $request->attributes->get('webhook_ledger_id');
        $verifier = $request->attributes->get('webhook_verifier');

        if (!$ledgerId || !$verifier) {
            Log::error("WebhookController: Missing ledger or verifier for {$provider}. Middleware bypass?");
            return response()->json(['error' => 'Security check failure'], 400);
        }

        // 1. Extract event metadata
        $event = $verifier->extractEventType($payload);
        $eventId = $verifier->extractEventId($payload);

        // 2. Create WebhookLog for backward-compatible forensic trace & retry capability
        $webhookLog = WebhookLog::create([
            'event_type' => $event,
            'webhook_id' => $eventId,
            'payload' => $data,
            'headers' => [
                'provider' => $provider,
                'user_agent' => $request->header('User-Agent'),
                'ip' => $request->ip(),
                'ledger_id' => $ledgerId,
            ],
            'status' => 'pending',
        ]);

        // 3. Resolve isolation queue based on resource type (Stripe-style isolation)
        $resourceType = $verifier->extractResourceType($payload);
        $queue = match($resourceType) {
            'payment' => 'webhooks_payments',
            'subscription' => 'webhooks_subscriptions',
            'refund' => 'webhooks_refunds',
            default => 'webhooks_default',
        };

        // 4. Dispatch isolated processing job
        ProcessWebhookJob::dispatch($webhookLog, $ledgerId)->onQueue($queue);

        // 5. Return immediate 200 OK as per institutional standards
        return response()->json([
            'status' => 'accepted',
            'message' => 'Webhook received and recorded'
        ], 200);
    }

    /**
     * Backward compatibility for Razorpay specific route.
     */
    public function handleRazorpay(Request $request)
    {
        return $this->handle($request, 'razorpay');
    }
}
