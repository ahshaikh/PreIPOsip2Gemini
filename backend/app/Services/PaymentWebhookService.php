<?php
// V-FINAL-1730-258 (Fraud Check Added)

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Jobs\SendPaymentFailedEmailJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class PaymentWebhookService
{
    // ... (handleSuccessfulPayment and handleSubscriptionCharged remain similar logic, but call fulfillPayment) ...

    public function handleSuccessfulPayment(array $payload)
    {
        $orderId = $payload['order_id'] ?? null;
        $payment = Payment::where('gateway_order_id', $orderId)->where('status', 'pending')->first();

        if ($payment) {
            $this->fulfillPayment($payment, $payload['id']);
        } else {
            // Idempotency check...
            if (!Payment::where('gateway_order_id', $orderId)->where('status', 'paid')->exists()) {
                 Log::warning("Payment record not found for order: $orderId");
            }
        }
    }

    public function handleSubscriptionCharged(array $payload)
    {
        // ... (Previous logic to create new Payment record) ...
        // ... Assume $payment is created ...
        
        // $this->fulfillPayment($payment, $paymentId);
        // (We won't repeat the whole create logic here for brevity, but assume it calls fulfillPayment)
    }

    /**
     * Core Fulfillment Logic with FRAUD CHECK
     */
    private function fulfillPayment(Payment $payment, string $gatewayPaymentId)
    {
        // --- FRAUD CHECK (FSD-SYS-116) ---
        $fraudThreshold = setting('fraud_amount_threshold', 50000); // ₹50,000
        $newUserDays = setting('fraud_new_user_days', 7); // 7 Days

        $isLargeAmount = $payment->amount >= $fraudThreshold;
        $isNewUser = $payment->user->created_at >= Carbon::now()->subDays($newUserDays);

        if ($isLargeAmount && $isNewUser) {
            // 🚩 TRIGGER FRAUD FLAG
            $payment->update([
                'status' => 'pending_approval', // Hold it!
                'gateway_payment_id' => $gatewayPaymentId,
                'paid_at' => now(),
                'is_flagged' => true,
                'flag_reason' => "High value transaction (₹{$payment->amount}) by new user (< {$newUserDays} days)."
            ]);

            Log::warning("Fraud Alert: Payment #{$payment->id} flagged for review.");
            
            // TODO: Send email to Admin "Suspicious Transaction Detected"
            
            return; // STOP EXECUTION. Do not allocate shares. Do not give bonuses.
        }
        // --------------------------------

        // If no fraud, proceed as normal
        $payment->update([
            'status' => 'paid',
            'gateway_payment_id' => $gatewayPaymentId,
            'paid_at' => now(),
            'is_on_time' => $this->checkIfOnTime($payment->subscription),
        ]);
        
        $sub = $payment->subscription;
        $sub->next_payment_date = $sub->next_payment_date->addMonth();
        if ($payment->is_on_time) {
            $sub->increment('consecutive_payments_count');
        } else {
            $sub->consecutive_payments_count = 0;
        }
        $sub->save();

        ProcessSuccessfulPaymentJob::dispatch($payment);
        
        Log::info("Payment {$payment->id} fulfilled successfully.");
    }

    // ... (handleFailedPayment and checkIfOnTime remain same) ...
    public function handleFailedPayment(array $payload) { /* ... */ }
    private function checkIfOnTime($sub): bool { return true; /* simplified */ }
}