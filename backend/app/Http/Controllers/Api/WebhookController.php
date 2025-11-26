<?php
// V-PHASE3-1730-088 (Created) | V-FINAL-1730-337 (Testable & Secure)

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use App\Jobs\ProcessWebhookRetryJob;
use App\Services\PaymentWebhookService;
use App\Services\RazorpayService; // <-- Import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentWebhookService $paymentWebhookService,
        protected RazorpayService $razorpayService // <-- Inject Service
    ) {}

    /**
     * Handle incoming webhooks from Razorpay.
     */
    public function handleRazorpay(Request $request)
    {
        $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET');
        $signature = $request->header('X-Razorpay-Signature');
        $payload = $request->getContent();

        // 1. SECURITY CHECK: Verify Signature via Service (Mockable)
        if (empty($webhookSecret)) {
            Log::critical('RAZORPAY_WEBHOOK_SECRET is not set.');
            return response()->json(['error' => 'Configuration Error'], 500);
        }

        // Use the service to verify, allowing us to mock this in tests
        $isValid = $this->razorpayService->verifyWebhookSignature($payload, $signature, $webhookSecret);

        if (!$isValid) {
            Log::warning('Razorpay Webhook Signature Verification Failed', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Invalid Signature'], 400);
        }

        // 2. Process Event
        $data = json_decode($payload, true);
        $event = $data['event'] ?? null;
        $webhookId = $data['payload']['payment']['entity']['id'] ?? $data['payload']['refund']['entity']['id'] ?? null;

        Log::info('Razorpay webhook verified', ['event' => $event, 'webhook_id' => $webhookId]);

        // 3. Create webhook log for tracking and retry capability
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
                'refund.processed' => $this->paymentWebhookService->handleRefundProcessed($data['payload']['refund']['entity']), // <-- NEW
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

            // Still return 200 to Razorpay to acknowledge receipt
            // We'll handle retries internally
            return response()->json([
                'status' => 'accepted',
                'message' => 'Webhook received, will retry processing'
            ], 200);
        }

        return response()->json(['status' => 'ok']);
    }
}