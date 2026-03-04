<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook Event Handlers Registry
    |--------------------------------------------------------------------------
    |
    | Maps 'provider.event_type' to the handler class responsible for
    | processing the event. Each handler must implement:
    | App\Services\Webhooks\Contracts\WebhookEventHandler
    |
    */

    // Razorpay Handlers
    'razorpay.payment.captured' => \App\Services\Webhooks\Handlers\RazorpayPaymentCapturedHandler::class,
    'razorpay.payment.failed' => \App\Services\Webhooks\Handlers\RazorpayPaymentFailedHandler::class,
    'razorpay.subscription.charged' => \App\Services\Webhooks\Handlers\RazorpaySubscriptionChargedHandler::class,
    'razorpay.refund.processed' => \App\Services\Webhooks\Handlers\RazorpayRefundProcessedHandler::class,
    'razorpay.settlement.processed' => \App\Services\Webhooks\Handlers\RazorpaySettlementProcessedHandler::class,
    'razorpay.dispute.created' => \App\Services\Webhooks\Handlers\RazorpayChargebackInitiatedHandler::class,
    'razorpay.dispute.lost' => \App\Services\Webhooks\Handlers\RazorpayChargebackConfirmedHandler::class,
    'razorpay.dispute.won' => \App\Services\Webhooks\Handlers\RazorpayChargebackResolvedHandler::class,
    
    // Stripe Handlers (Placeholders)
    'stripe.payment_intent.succeeded' => \App\Services\Webhooks\Handlers\StripePaymentSucceededHandler::class,
    'stripe.payment_intent.payment_failed' => \App\Services\Webhooks\Handlers\StripePaymentFailedHandler::class,

];
