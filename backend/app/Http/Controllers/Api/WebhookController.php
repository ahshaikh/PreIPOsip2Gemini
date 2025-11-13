<?php
// V-FINAL-1730-211 (Events Wired Up)

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

    public function handleRazorpay(Request $request)
    {
        // ... (Signature Verification logic remains same) ...
        $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET');
        $signature = $request->header('X-Razorpay-Signature');
        $payload = $request->getContent();

        try {
            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
            $api->utility->verifyWebhookSignature($payload, $signature, $webhookSecret);
        } catch (SignatureVerificationError $e) {
            Log::error('Razorpay Webhook Signature Verification Failed');
            return response()->json(['error' => 'Invalid Signature'], 400);
        }

        $data = json_decode($payload, true);
        $event = $data['event'] ?? null;

        Log::info('Razorpay webhook received', ['event' => $event]);

        try {
            match ($event) {
                // One-time payment success
                'payment.captured' => $this->paymentWebhookService->handleSuccessfulPayment($data['payload']['payment']['entity']),
                
                // --- NEW: Recurring payment success ---
                'subscription.charged' => $this->paymentWebhookService->handleSubscriptionCharged($data['payload']['payment']['entity']),
                
                // --- NEW: Payment failure ---
                'payment.failed' => $this->paymentWebhookService->handleFailedPayment($data['payload']['payment']['entity']),
                
                default => Log::info('Unhandled Razorpay event', ['event' => $event]),
            };
        } catch (\Exception $e) {
            Log::error('Error processing webhook', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
        
        return response()->json(['status' => 'ok']);
    }
}