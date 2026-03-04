<?php
// V-SECURITY-WEBHOOK - Webhook Signature Verification Middleware

namespace App\Http\Middleware;

use App\Services\Webhooks\WebhookSignatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifyWebhookSignature
{
    public function __construct(
        protected WebhookSignatureService $signatureService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $provider = 'generic'): Response
    {
        // Normalize provider name for service
        $normalizedProvider = match ($provider) {
            'razorpay' => 'razorpay',
            'stripe' => 'stripe',
            'generic', 'hmac' => 'hmac',
            default => $provider,
        };

        $isValid = $this->signatureService->verify(
            $normalizedProvider,
            $request->getContent(),
            $request->headers->all()
        );

        if (!$isValid) {
            Log::warning("Invalid webhook signature for provider: {$provider}", [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
