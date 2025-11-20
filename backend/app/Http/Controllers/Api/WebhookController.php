<?php
// V-PHASE3-1730-088 (Created) | V-FINAL-1730-337 (Testable & Secure)

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        Log::info('Razorpay webhook verified', ['event' => $event]);

        try {
            match ($event) {
                'payment.captured' => $this->paymentWebhookService->handleSuccessfulPayment($data['payload']['payment']['entity']),
                'subscription.charged' => $this->paymentWebhookService->handleSubscriptionCharged($data['payload']['payment']['entity']),
                'payment.failed' => $this->paymentWebhookService->handleFailedPayment($data['payload']['payment']['entity']),
                'refund.processed' => $this->paymentWebhookService->handleRefundProcessed($data['payload']['refund']['entity']), // <-- NEW
                default => Log::info('Unhandled Razorpay event', ['event' => $event]),
            };
        } catch (\Exception $e) {
            Log::error('Error processing webhook', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
        
        return response()->json(['status' => 'ok']);
    }
}