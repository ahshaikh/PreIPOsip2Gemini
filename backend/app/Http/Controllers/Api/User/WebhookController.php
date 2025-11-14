<?php
// V-FINAL-1730-297 (SEC-7: Signature Verification Enforced)

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

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
        $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET');
        $signature = $request->header('X-Razorpay-Signature');
        $payload = $request->getContent(); // Get raw body for verification

        // --- SEC-7: CRITICAL SECURITY CHECK ---
        if (empty($webhookSecret)) {
            Log::critical('RAZORPAY_WEBHOOK_SECRET is not set in .env. Webhook unsafe.');
            return response()->json(['error' => 'Configuration Error'], 500);
        }

        try {
            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
            // This throws an exception if the signature is invalid
            $api->utility->verifyWebhookSignature($payload, $signature, $webhookSecret);
        } catch (SignatureVerificationError $e) {
            Log::warning('Razorpay Webhook Signature Verification Failed', [
                'ip' => $request->ip(),
                'signature' => $signature
            ]);
            return response()->json(['error' => 'Invalid Signature'], 400);
        }
        // ---------------------------------------

        $data = json_decode($payload, true);
        $event = $data['event'] ?? null;

        Log::info('Razorpay webhook verified and received', ['event' => $event]);

        try {
            match ($event) {
                // One-time payment success
                'payment.captured' => $this->paymentWebhookService->handleSuccessfulPayment($data['payload']['payment']['entity']),
                
                // Recurring payment success
                'subscription.charged' => $this->paymentWebhookService->handleSubscriptionCharged($data['payload']['payment']['entity']),
                
                // Payment failure
                'payment.failed' => $this->paymentWebhookService->handleFailedPayment($data['payload']['payment']['entity']),
                
                default => Log::info('Unhandled Razorpay event', ['event' => $event]),
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