<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Jobs\HandlePaymentWebhook;
use App\Services\Payments\Gateways\PaymentGatewayInterface;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(Request $request, PaymentGatewayInterface $gateway)
    {
        // 1. [AUDIT FIX]: Rapid signature validation
        if (!$gateway->verifySignature($request->all(), $request->header('X-Razorpay-Signature'))) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // 2. [AUDIT FIX]: Dispatch and forget
        HandlePaymentWebhook::dispatch($request->all());

        // 3. [AUDIT FIX]: Immediate 200 OK prevents Gateway retries
        return response()->json(['status' => 'received'], 200);
    }
}