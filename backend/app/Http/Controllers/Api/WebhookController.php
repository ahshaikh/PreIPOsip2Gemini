// V-PHASE3-1730-088
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(protected PaymentWebhookService $paymentWebhookService)
    {
    }

    /**
     * Handle incoming webhooks from Razorpay.
     */
    public function handleRazorpay(Request $request)
    {
        // 1. Validate the webhook signature (CRITICAL)
        // ... (Logic to verify X-Razorpay-Signature)
        
        $payload = $request->input();
        $event = $payload['event'];

        Log::info('Razorpay webhook received', ['event' => $event]);

        try {
            match ($event) {
                'payment.captured' => $this->paymentWebhookService->handleSuccessfulPayment($payload['payload']['payment']['entity']),
                // 'payment.failed' => $this->paymentWebhookService->handleFailedPayment($payload),
                // 'refund.processed' => $this->paymentWebhookService->handleRefund($payload),
                default => Log::warning('Unhandled Razorpay event', ['event' => $event]),
            };
        } catch (\Exception $e) {
            Log::error('Error processing Razorpay webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
        
        return response()->json(['status' => 'ok']);
    }
}