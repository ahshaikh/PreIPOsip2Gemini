<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook Secrets
    |--------------------------------------------------------------------------
    |
    | These secrets are used to verify the signature of incoming webhooks
    | from various payment providers.
    |
    */

    'stripe' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'razorpay' => [
        'secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    'hmac' => [
        'secret' => env('GENERIC_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Secrets
    |--------------------------------------------------------------------------
    |
    | These secrets are used in the testing environment to allow tests
    | to generate valid signatures and verify them against the logic.
    |
    */

    'test_secrets' => [
        'stripe' => 'test_stripe_secret',
        'razorpay' => 'test_razorpay_secret',
        'hmac' => 'test_hmac_secret',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Queues
    |--------------------------------------------------------------------------
    |
    | Map resource types to isolated queues.
    |
    */
    'queues' => [
        'payment' => 'webhooks_payments',
        'subscription' => 'webhooks_subscriptions',
        'refund' => 'webhooks_refunds',
        'default' => 'webhooks_default',
    ],
];
