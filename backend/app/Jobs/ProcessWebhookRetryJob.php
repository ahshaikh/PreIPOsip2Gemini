<?php

namespace App\Jobs;

use App\Models\WebhookLog;
use App\Services\PaymentWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookRetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1; // We handle retries in the model
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public WebhookLog $webhookLog
    ) {
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentWebhookService $webhookService): void
    {
        // Check if webhook can still be retried
        if (!$this->webhookLog->canRetry()) {
            Log::warning('Webhook cannot be retried', [
                'webhook_id' => $this->webhookLog->id,
                'status' => $this->webhookLog->status,
                'retry_count' => $this->webhookLog->retry_count,
            ]);
            return;
        }

        // Mark as processing
        $this->webhookLog->markAsProcessing();

        Log::info('Processing webhook retry', [
            'webhook_id' => $this->webhookLog->id,
            'event_type' => $this->webhookLog->event_type,
            'retry_count' => $this->webhookLog->retry_count,
        ]);

        try {
            // Process the webhook based on event type
            $payload = $this->webhookLog->payload;

            match ($this->webhookLog->event_type) {
                'payment.captured' => $webhookService->handleSuccessfulPayment($payload),
                'subscription.charged' => $webhookService->handleSubscriptionCharged($payload),
                'payment.failed' => $webhookService->handleFailedPayment($payload),
                'refund.processed' => $webhookService->handleRefundProcessed($payload),
                default => throw new \Exception('Unknown webhook event type: ' . $this->webhookLog->event_type),
            };

            // Mark as success
            $this->webhookLog->markAsSuccess(
                response: ['message' => 'Webhook processed successfully'],
                responseCode: 200
            );

            Log::info('Webhook retry successful', [
                'webhook_id' => $this->webhookLog->id,
                'event_type' => $this->webhookLog->event_type,
            ]);
        } catch (\Exception $e) {
            // Mark as failed and schedule next retry
            $this->webhookLog->markAsFailed(
                errorMessage: $e->getMessage(),
                responseCode: 500,
                response: [
                    'error' => $e->getMessage(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                ]
            );

            Log::error('Webhook retry failed', [
                'webhook_id' => $this->webhookLog->id,
                'event_type' => $this->webhookLog->event_type,
                'retry_count' => $this->webhookLog->retry_count,
                'error' => $e->getMessage(),
                'next_retry_at' => $this->webhookLog->next_retry_at,
            ]);

            // Re-queue if more retries available
            if ($this->webhookLog->canRetry()) {
                self::dispatch($this->webhookLog)
                    ->delay($this->webhookLog->next_retry_at);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook retry job failed fatally', [
            'webhook_id' => $this->webhookLog->id,
            'error' => $exception->getMessage(),
        ]);

        $this->webhookLog->markAsFailed(
            errorMessage: 'Job failed fatally: ' . $exception->getMessage(),
            responseCode: 500
        );
    }
}
