<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\WebhookLog;
use App\Jobs\ProcessWebhookRetryJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class WebhookRetrySystemTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function webhook_log_is_created_for_incoming_webhook()
    {
        $this->markTestSkipped('Requires webhook signature verification setup');

        // This test would verify that WebhookLog is created
        // when a webhook is received
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function webhook_can_be_marked_as_failed_and_scheduled_for_retry()
    {
        $webhookLog = WebhookLog::create([
            'event_type' => 'payment.captured',
            'webhook_id' => 'pay_test123',
            'payload' => ['test' => 'data'],
            'status' => 'processing',
            'retry_count' => 0,
        ]);

        $webhookLog->markAsFailed('Test error', 500);

        $this->assertEquals('pending', $webhookLog->fresh()->status);
        $this->assertEquals(1, $webhookLog->fresh()->retry_count);
        $this->assertNotNull($webhookLog->fresh()->next_retry_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function webhook_is_marked_as_max_retries_reached_after_limit()
    {
        $webhookLog = WebhookLog::create([
            'event_type' => 'payment.captured',
            'webhook_id' => 'pay_test456',
            'payload' => ['test' => 'data'],
            'status' => 'processing',
            'retry_count' => 4,
            'max_retries' => 5,
        ]);

        $webhookLog->markAsFailed('Final error', 500);

        $this->assertEquals('max_retries_reached', $webhookLog->fresh()->status);
        $this->assertEquals(5, $webhookLog->fresh()->retry_count);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function webhook_can_be_marked_as_success()
    {
        $webhookLog = WebhookLog::create([
            'event_type' => 'payment.captured',
            'webhook_id' => 'pay_test789',
            'payload' => ['test' => 'data'],
            'status' => 'processing',
        ]);

        $webhookLog->markAsSuccess(['message' => 'Success'], 200);

        $webhook = $webhookLog->fresh();
        $this->assertEquals('success', $webhook->status);
        $this->assertEquals(200, $webhook->response_code);
        $this->assertNotNull($webhook->processed_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pending_retries_scope_returns_due_webhooks()
    {
        // Create a webhook that's due for retry
        WebhookLog::create([
            'event_type' => 'payment.captured',
            'webhook_id' => 'pay_due',
            'payload' => ['test' => 'data'],
            'status' => 'pending',
            'retry_count' => 1,
            'max_retries' => 5,
            'next_retry_at' => now()->subMinute(), // Due 1 minute ago
        ]);

        // Create a webhook that's not due yet
        WebhookLog::create([
            'event_type' => 'payment.captured',
            'webhook_id' => 'pay_future',
            'payload' => ['test' => 'data'],
            'status' => 'pending',
            'retry_count' => 1,
            'max_retries' => 5,
            'next_retry_at' => now()->addHour(), // Due in 1 hour
        ]);

        $dueWebhooks = WebhookLog::pendingRetries()->get();

        $this->assertEquals(1, $dueWebhooks->count());
        $this->assertEquals('pay_due', $dueWebhooks->first()->webhook_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function exponential_backoff_is_calculated_correctly()
    {
        $webhookLog = WebhookLog::create([
            'event_type' => 'payment.captured',
            'webhook_id' => 'pay_backoff',
            'payload' => ['test' => 'data'],
            'status' => 'pending',
            'retry_count' => 3,
        ]);

        $nextRetry = $webhookLog->calculateNextRetryAt();

        // For retry count 3, should be 2^3 = 8 minutes
        $expectedMinutes = 8;
        $expectedTime = now()->addMinutes($expectedMinutes);

        $this->assertEquals(
            $expectedTime->format('Y-m-d H:i'),
            $nextRetry->format('Y-m-d H:i')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function retry_job_can_be_dispatched()
    {
        Queue::fake();

        $webhookLog = WebhookLog::create([
            'event_type' => 'payment.captured',
            'webhook_id' => 'pay_job',
            'payload' => ['test' => 'data'],
            'status' => 'pending',
        ]);

        ProcessWebhookRetryJob::dispatch($webhookLog);

        Queue::assertPushed(ProcessWebhookRetryJob::class);
    }
}
