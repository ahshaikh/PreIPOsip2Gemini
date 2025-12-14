<?php
// V-PHASE3-1730-088 (Created) | V-FINAL-1730-337 (Testable & Secure) | V-FIX-1730-605 (Middleware Verification)

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use App\Jobs\ProcessWebhookRetryJob;
use App\Services\PaymentWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentWebhookService $paymentWebhookService
    ) {}

    /**
     * Handle incoming webhooks from Razorpay.
     * * Security Note:* Signature verification is handled by the 
     * 'webhook.verify:razorpay' middleware defined in routes/api.php.
     */
    public function handleRazorpay(Request $request)
    {
        // 1. Prepare Payload (Verification already done by middleware)
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        
        $data = json_decode($payload, true);
        $event = $data['event'] ?? null;
        $webhookId = $data['payload']['payment']['entity']['id'] ?? $data['payload']['refund']['entity']['id'] ?? null;

        Log::info('Razorpay webhook processed', ['event' => $event, 'webhook_id' => $webhookId]);

        // 2. Create webhook log for tracking and retry capability
        $webhookLog = WebhookLog::create([
            'event_type' => $event,
            'webhook_id' => $webhookId,
            'payload' => $data,
            'headers' => [
                'signature' => $signature,
                'user_agent' => $request->header('User-Agent'),
                'ip' => $request->ip(),
            ],
            'status' => 'processing',
        ]);

        try {
            match ($event) {
                'payment.captured' => $this->paymentWebhookService->handleSuccessfulPayment($data['payload']['payment']['entity']),
                'subscription.charged' => $this->paymentWebhookService->handleSubscriptionCharged($data['payload']['payment']['entity']),
                'payment.failed' => $this->paymentWebhookService->handleFailedPayment($data['payload']['payment']['entity']),
                'refund.processed' => $this->paymentWebhookService->handleRefundProcessed($data['payload']['refund']['entity']),
                default => Log::info('Unhandled Razorpay event', ['event' => $event]),
            };

            // Mark webhook as successful
            $webhookLog->markAsSuccess(['message' => 'Processed successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error processing webhook', [
                'event' => $event,
                'webhook_id' => $webhookId,
                'error' => $e->getMessage()
            ]);

            // Mark webhook as failed and schedule retry
            $webhookLog->markAsFailed($e->getMessage(), 500);

            // Queue retry job with delay
            ProcessWebhookRetryJob::dispatch($webhookLog)
                ->delay($webhookLog->next_retry_at);

            // Return 200 to Razorpay to prevent them from retrying immediately
            return response()->json([
                'status' => 'accepted',
                'message' => 'Webhook received, will retry processing'
            ], 200);
        }

        return response()->json(['status' => 'ok']);
    }
}