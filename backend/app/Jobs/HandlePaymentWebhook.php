<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\Payments\Gateways\PaymentGatewayInterface;
use Illuminate\Support\Facades\DB;

class HandlePaymentWebhook implements ShouldQueue
{
    public function __construct(protected array $payload) {}

    public function handle(PaymentGatewayInterface $gateway)
    {
        // [AUDIT FIX]: Use updateOrCreate for Idempotency. 
        // If the gateway_payment_id exists, it won't create a second record.
        DB::transaction(function () {
            Payment::updateOrCreate(
                ['gateway_payment_id' => $this->payload['payment_id']],
                [
                    'amount_paise' => $this->payload['amount'],
                    'status' => 'completed',
                    'processed_at' => now(),
                ]
            );
        });
    }
}