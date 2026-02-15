<?php
// V-PAYMENT-INTEGRITY-2026: Fixed to properly route through PaymentWebhookService

namespace App\Jobs;

use App\Models\Payment;
use App\Models\WebhookLog;
use App\Services\PaymentWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * HandlePaymentWebhook - Process Payment Gateway Webhooks
 *
 * V-PAYMENT-INTEGRITY-2026:
 * - Routes ALL webhook events through PaymentWebhookService
 * - Signature verified BEFORE this job is dispatched (in controller)
 * - Idempotency enforced via gateway_payment_id UNIQUE constraint
 * - State machine enforced in Payment model
 *
 * SUPPORTED EVENTS:
 * - payment.captured → handleSuccessfulPayment
 * - payment.failed → handleFailedPayment
 * - subscription.charged → handleSubscriptionCharged
 * - refund.processed → handleRefundProcessed
 *
 * @package App\Jobs
 */
class HandlePaymentWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 60 seconds between retries

    protected array $payload;
    protected ?string $webhookLogId;

    public function __construct(array $payload, ?string $webhookLogId = null)
    {
        $this->payload = $payload;
        $this->webhookLogId = $webhookLogId;
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentWebhookService $webhookService): void
    {
        $event = $this->payload['event'] ?? null;
        $entity = $this->payload['payload']['payment']['entity'] ?? [];

        Log::info('WEBHOOK PROCESSING START', [
            'event' => $event,
            'payment_id' => $entity['id'] ?? null,
            'order_id' => $entity['order_id'] ?? null,
        ]);

        try {
            // V-PAYMENT-INTEGRITY-2026: Route to appropriate handler
            match ($event) {
                'payment.captured' => $this->handlePaymentCaptured($webhookService, $entity),
                'payment.failed' => $this->handlePaymentFailed($webhookService, $entity),
                'subscription.charged' => $this->handleSubscriptionCharged($webhookService),
                'refund.processed', 'refund.created' => $this->handleRefundProcessed($webhookService),
                default => $this->handleUnknownEvent($event),
            };

            // Mark webhook log as processed
            $this->markWebhookProcessed();

            Log::info('WEBHOOK PROCESSING COMPLETE', [
                'event' => $event,
                'payment_id' => $entity['id'] ?? null,
            ]);

        } catch (\Throwable $e) {
            Log::error('WEBHOOK PROCESSING FAILED', [
                'event' => $event,
                'payment_id' => $entity['id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->markWebhookFailed($e->getMessage());

            throw $e; // Re-throw for retry
        }
    }

    /**
     * Handle payment.captured event
     */
    private function handlePaymentCaptured(PaymentWebhookService $webhookService, array $entity): void
    {
        // V-PAYMENT-INTEGRITY-2026: Validate required fields
        $paymentId = $entity['id'] ?? null;
        $orderId = $entity['order_id'] ?? null;
        $amountPaise = $entity['amount'] ?? null;
        $currency = $entity['currency'] ?? null;

        if (!$paymentId || !$orderId) {
            throw new \InvalidArgumentException('Missing payment_id or order_id in webhook payload');
        }

        // V-PAYMENT-INTEGRITY-2026: Currency validation
        $expectedCurrency = config('services.razorpay.currency', 'INR');
        if ($currency && strtoupper($currency) !== strtoupper($expectedCurrency)) {
            Log::critical('CURRENCY MISMATCH IN WEBHOOK', [
                'payment_id' => $paymentId,
                'expected_currency' => $expectedCurrency,
                'received_currency' => $currency,
            ]);
            throw new \RuntimeException("Currency mismatch: expected {$expectedCurrency}, received {$currency}");
        }

        // Route to service
        $webhookService->handleSuccessfulPayment([
            'id' => $paymentId,
            'order_id' => $orderId,
            'amount' => $amountPaise,
            'currency' => $currency,
        ]);
    }

    /**
     * Handle payment.failed event
     */
    private function handlePaymentFailed(PaymentWebhookService $webhookService, array $entity): void
    {
        $orderId = $entity['order_id'] ?? null;
        $errorDescription = $entity['error_description'] ?? 'Payment failed';

        if (!$orderId) {
            Log::warning('Failed payment webhook missing order_id', ['entity' => $entity]);
            return;
        }

        $webhookService->handleFailedPayment([
            'order_id' => $orderId,
            'error_description' => $errorDescription,
        ]);
    }

    /**
     * Handle subscription.charged event
     */
    private function handleSubscriptionCharged(PaymentWebhookService $webhookService): void
    {
        $subscriptionEntity = $this->payload['payload']['subscription']['entity'] ?? [];
        $paymentEntity = $this->payload['payload']['payment']['entity'] ?? [];

        $subscriptionId = $subscriptionEntity['id'] ?? null;
        $paymentId = $paymentEntity['id'] ?? null;
        $amountPaise = $paymentEntity['amount'] ?? 0;
        $currency = $paymentEntity['currency'] ?? null;

        if (!$subscriptionId || !$paymentId) {
            throw new \InvalidArgumentException('Missing subscription_id or payment_id in webhook');
        }

        // V-PAYMENT-INTEGRITY-2026: Currency validation
        $expectedCurrency = config('services.razorpay.currency', 'INR');
        if ($currency && strtoupper($currency) !== strtoupper($expectedCurrency)) {
            Log::critical('CURRENCY MISMATCH IN SUBSCRIPTION WEBHOOK', [
                'subscription_id' => $subscriptionId,
                'payment_id' => $paymentId,
                'expected_currency' => $expectedCurrency,
                'received_currency' => $currency,
            ]);
            throw new \RuntimeException("Currency mismatch: expected {$expectedCurrency}, received {$currency}");
        }

        $webhookService->handleSubscriptionCharged([
            'subscription_id' => $subscriptionId,
            'payment_id' => $paymentId,
            'amount' => $amountPaise,
            'currency' => $currency,
        ]);
    }

    /**
     * Handle refund.processed event
     */
    private function handleRefundProcessed(PaymentWebhookService $webhookService): void
    {
        $refundEntity = $this->payload['payload']['refund']['entity'] ?? [];
        $paymentId = $refundEntity['payment_id'] ?? null;
        $refundAmount = $refundEntity['amount'] ?? 0;
        $refundId = $refundEntity['id'] ?? null;

        if (!$paymentId) {
            throw new \InvalidArgumentException('Missing payment_id in refund webhook');
        }

        $webhookService->handleRefundProcessed([
            'payment_id' => $paymentId,
            'amount' => $refundAmount,
            'refund_id' => $refundId,
        ]);
    }

    /**
     * Handle unknown event types
     */
    private function handleUnknownEvent(?string $event): void
    {
        Log::info('WEBHOOK: Ignoring unhandled event type', [
            'event' => $event,
        ]);
    }

    /**
     * Mark webhook log as processed
     */
    private function markWebhookProcessed(): void
    {
        if ($this->webhookLogId) {
            DB::table('webhook_logs')
                ->where('id', $this->webhookLogId)
                ->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Mark webhook log as failed
     */
    private function markWebhookFailed(string $errorMessage): void
    {
        if ($this->webhookLogId) {
            DB::table('webhook_logs')
                ->where('id', $this->webhookLogId)
                ->update([
                    'status' => 'failed',
                    'error_message' => substr($errorMessage, 0, 1000),
                    'retry_count' => DB::raw('retry_count + 1'),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('WEBHOOK JOB PERMANENTLY FAILED', [
            'event' => $this->payload['event'] ?? null,
            'error' => $exception->getMessage(),
        ]);

        $this->markWebhookFailed('PERMANENT FAILURE: ' . $exception->getMessage());
    }
}
