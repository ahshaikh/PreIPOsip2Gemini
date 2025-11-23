<?php
// V-SECURITY-WEBHOOK - Webhook Signature Verification Middleware

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifyWebhookSignature
{
    /**
     * Supported webhook providers and their signature verification methods
     */
    protected array $providers = [
        'razorpay' => 'verifyRazorpaySignature',
        'stripe' => 'verifyStripeSignature',
        'paytm' => 'verifyPaytmSignature',
        'generic' => 'verifyGenericHmacSignature',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $provider = 'generic'): Response
    {
        if (!isset($this->providers[$provider])) {
            Log::error("Unknown webhook provider: {$provider}");
            return response()->json(['error' => 'Invalid webhook provider'], 400);
        }

        $method = $this->providers[$provider];
        $isValid = $this->$method($request);

        if (!$isValid) {
            Log::warning("Invalid webhook signature for provider: {$provider}", [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'headers' => $request->headers->all(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }

    /**
     * Verify Razorpay webhook signature
     */
    protected function verifyRazorpaySignature(Request $request): bool
    {
        $webhookSecret = config('services.razorpay.webhook_secret');

        if (empty($webhookSecret)) {
            Log::warning('Razorpay webhook secret not configured');
            return false;
        }

        $signature = $request->header('X-Razorpay-Signature');
        if (empty($signature)) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Stripe webhook signature
     */
    protected function verifyStripeSignature(Request $request): bool
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        if (empty($webhookSecret)) {
            Log::warning('Stripe webhook secret not configured');
            return false;
        }

        $signature = $request->header('Stripe-Signature');
        if (empty($signature)) {
            return false;
        }

        // Parse Stripe signature header
        $elements = explode(',', $signature);
        $timestamp = null;
        $signatureValue = null;

        foreach ($elements as $element) {
            $parts = explode('=', $element, 2);
            if (count($parts) === 2) {
                if ($parts[0] === 't') {
                    $timestamp = $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatureValue = $parts[1];
                }
            }
        }

        if (empty($timestamp) || empty($signatureValue)) {
            return false;
        }

        // Check timestamp tolerance (5 minutes)
        $tolerance = 300;
        if (abs(time() - (int) $timestamp) > $tolerance) {
            Log::warning('Stripe webhook timestamp outside tolerance');
            return false;
        }

        $payload = $request->getContent();
        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);

        return hash_equals($expectedSignature, $signatureValue);
    }

    /**
     * Verify Paytm webhook signature (checksum)
     */
    protected function verifyPaytmSignature(Request $request): bool
    {
        $merchantKey = config('services.paytm.merchant_key');

        if (empty($merchantKey)) {
            Log::warning('Paytm merchant key not configured');
            return false;
        }

        $checksum = $request->input('CHECKSUMHASH');
        if (empty($checksum)) {
            return false;
        }

        $params = $request->except('CHECKSUMHASH');
        ksort($params);

        $paramString = implode('|', array_values($params));
        $expectedChecksum = hash_hmac('sha256', $paramString, $merchantKey);

        return hash_equals($expectedChecksum, $checksum);
    }

    /**
     * Verify generic HMAC signature
     */
    protected function verifyGenericHmacSignature(Request $request): bool
    {
        $secret = config('services.webhook.secret');

        if (empty($secret)) {
            Log::warning('Generic webhook secret not configured');
            return false;
        }

        // Check common signature headers
        $signatureHeaders = [
            'X-Webhook-Signature',
            'X-Signature',
            'X-Hub-Signature-256',
            'X-HMAC-Signature',
        ];

        $signature = null;
        foreach ($signatureHeaders as $header) {
            if ($request->hasHeader($header)) {
                $signature = $request->header($header);
                break;
            }
        }

        if (empty($signature)) {
            return false;
        }

        // Remove sha256= prefix if present
        if (str_starts_with($signature, 'sha256=')) {
            $signature = substr($signature, 7);
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
