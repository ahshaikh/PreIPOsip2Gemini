// V-PHASE3-1730-081
<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Jobs\ProcessSuccessfulPaymentJob;
use Illuminate\Support\Facades\Log;

class PaymentWebhookService
{
    /**
     * Handle a successful payment from a gateway (e.g., Razorpay).
     */
    public function handleSuccessfulPayment(array $payload)
    {
        // 1. Verify webhook signature (using gateway-specific logic)
        // ... (Skipped for brevity)

        // 2. Find the payment record
        $orderId = $payload['order_id'];
        $payment = Payment::where('gateway_order_id', $orderId)
                          ->where('status', 'pending')
                          ->firstOrFail();

        // 3. Mark payment as paid
        $payment->update([
            'status' => 'paid',
            'gateway_payment_id' => $payload['payment_id'],
            'paid_at' => now(),
            'is_on_time' => $this->checkIfOnTime($payment->subscription),
        ]);
        
        // 4. Update subscription
        $sub = $payment->subscription;
        $sub->next_payment_date = $sub->next_payment_date->addMonth();
        if ($payment->is_on_time) {
            $sub->increment('consecutive_payments_count');
        } else {
            $sub->consecutive_payments_count = 0; // Reset streak
        }
        $sub->save();

        // 5. Dispatch the job to handle all heavy lifting
        ProcessSuccessfulPaymentJob::dispatch($payment);
        
        Log::info("Payment {$payment->id} processed successfully.");
    }

    private function checkIfOnTime(Subscription $subscription): bool
    {
        // Logic to check if payment was made before or on next_payment_date
        return now()->lte($subscription->next_payment_date->addDays(setting('payment_grace_period_days', 2)));
    }
}