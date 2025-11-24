<?php
// V-PHASE3-1730-081 (Created) | V-FINAL-1730-338 | V-FINAL-1730-454 (Idempotent & WalletService)

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Jobs\SendPaymentFailedEmailJob;
use App\Notifications\PaymentFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PaymentWebhookService
{
    protected $walletService;
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Handle a standard one-time payment success.
     */
    public function handleSuccessfulPayment(array $payload)
    {
        $orderId = $payload['order_id'] ?? null;
        $paymentId = $payload['id'] ?? null;

        // --- IDEMPOTENCY FIX (SEC-8) ---
        // Check if we already processed this exact payment_id
        if (Payment::where('gateway_payment_id', $paymentId)->exists()) {
            Log::info("Duplicate webhook: Payment $paymentId already processed. Skipping.");
            return;
        }
        // -----------------------------

        $payment = Payment::where('gateway_order_id', $orderId)
                          ->where('status', 'pending')
                          ->first();

        if ($payment) {
            $this->fulfillPayment($payment, $paymentId);
        } else {
            Log::warning("Payment record not found for order: $orderId");
        }
    }

    /**
     * Handle a Recurring Subscription Charge (Auto-Debit).
     */
    public function handleSubscriptionCharged(array $payload)
    {
        $subscriptionId = $payload['subscription_id'];
        $paymentId = $payload['payment_id'];
        $amount = $payload['amount'] / 100;

        // --- IDEMPOTENCY FIX (SEC-8) ---
        if (Payment::where('gateway_payment_id', $paymentId)->exists()) {
            Log::info("Duplicate subscription.charged webhook: $paymentId already processed. Skipping.");
            return;
        }
        // -----------------------------

        $subscription = Subscription::where('razorpay_subscription_id', $subscriptionId)->first();
        if (!$subscription) {
            Log::error("Recurring payment received for unknown subscription: $subscriptionId");
            return;
        }

        $payment = Payment::create([
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'status' => 'pending', // Will be set to 'paid' by fulfillPayment
            'gateway' => 'razorpay_auto',
            'gateway_payment_id' => $paymentId,
            'gateway_order_id' => $subscriptionId,
            'paid_at' => now(),
            'is_on_time' => true,
        ]);

        $this->fulfillPayment($payment, $paymentId);
    }

    /**
     * Handle Payment Failure.
     */
    public function handleFailedPayment(array $payload)
    {
        $orderId = $payload['order_id'] ?? null;
        $description = $payload['error_description'] ?? 'Payment Failed';

        if ($orderId) {
            $payment = Payment::where('gateway_order_id', $orderId)->first();
            if ($payment && $payment->status === 'pending') {
                $payment->update(['status' => 'failed']);
                SendPaymentFailedEmailJob::dispatch($payment, $description);
                $payment->user->notify(new PaymentFailed($payment->amount, $description));
                Log::info("Payment {$payment->id} marked as failed: $description");
            }
        }
    }
    
    /**
     * Handle Refund Processed
     * This is tricky, as it might credit the user, or just be an external refund.
     * For now, we just log it.
     */
    public function handleRefundProcessed(array $payload)
    {
        $paymentId = $payload['payment_id'];
        $payment = Payment::where('gateway_payment_id', $paymentId)->first();

        if ($payment && $payment->status !== 'refunded') {
            $payment->update(['status' => 'refunded']);
            
            // NOTE: We do *not* credit the wallet here.
            // Our *Admin* refund logic credits the wallet. This webhook just
            // confirms that the *Gateway* processed it.
            Log::info("Payment {$payment->id} marked as refunded via webhook.");
        }
    }

    private function fulfillPayment(Payment $payment, string $gatewayPaymentId)
    {
        DB::transaction(function () use ($payment, $gatewayPaymentId) {
            $payment->update([
                'status' => 'paid',
                'gateway_payment_id' => $gatewayPaymentId,
                'paid_at' => now(),
                'is_on_time' => $this->checkIfOnTime($payment->subscription),
            ]);

            $sub = $payment->subscription;

            // V-SECURITY-FIX: Activate pending subscription after first payment
            if ($sub->status === 'pending') {
                $sub->status = 'active';
                Log::info("Subscription #{$sub->id} activated after first payment");
            }

            $sub->next_payment_date = $sub->next_payment_date->addMonth();
            if ($payment->is_on_time) {
                $sub->increment('consecutive_payments_count');
            } else {
                $sub->consecutive_payments_count = 0;
            }
            $sub->save();

            // Dispatch job for bonuses, allocation, etc.
            ProcessSuccessfulPaymentJob::dispatch($payment);
        });
    }

    private function checkIfOnTime(Subscription $subscription): bool
    {
        return now()->lte($subscription->next_payment_date->addDays(setting('payment_grace_period_days', 2)));
    }
}