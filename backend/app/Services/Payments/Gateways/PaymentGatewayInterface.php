<?php

namespace App\Services\Payments\Gateways;

/**
 * PaymentGatewayInterface
 * * [AUDIT FIX]: Defines the contract for all payment providers (Razorpay, Stripe, etc).
 */
interface PaymentGatewayInterface
{
    public function createOrder(float $amount, string $currency, array $metadata): array;
    public function verifySignature(array $payload, string $signature): bool;
    public function fetchTransaction(string $gatewayPaymentId): array;
}