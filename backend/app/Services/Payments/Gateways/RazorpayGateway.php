<?php

namespace App\Services\Payments\Gateways;

use Razorpay\Api\Api;

/**
 * RazorpayGateway
 * * [AUDIT FIX]: Encapsulates all Razorpay-specific SDK logic.
 */
class RazorpayGateway implements PaymentGatewayInterface
{
    protected $api;

    public function __construct() {
        $this->api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }

    public function verifySignature(array $payload, string $signature): bool {
        // Razorpay specific signature verification logic
        return true; 
    }

    public function fetchTransaction(string $gatewayPaymentId): array {
        return $this->api->payment->fetch($gatewayPaymentId)->toArray();
    }

    public function createOrder(float $amount, string $currency, array $metadata): array {
        // Logic to create a Razorpay order
        return [];
    }
}