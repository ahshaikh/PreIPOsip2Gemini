<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookJob;
use App\Models\WebhookEventLedger;
use App\Models\WebhookLog;
use App\Services\Webhooks\WebhookVerifierRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookRetrySystemTest extends TestCase
{
    use RefreshDatabase;

    protected $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = app(WebhookVerifierRegistry::class);
    }

    public function test_webhook_log_is_created_for_incoming_webhook()
    {
        Queue::fake();

        $payload = ['event' => 'payment.captured', 'id' => 'pay_123'];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $response = $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $response->assertStatus(200);
        $this->assertDatabaseHas('webhook_logs', [
            'webhook_id' => 'pay_123',
            'event_type' => 'payment.captured'
        ]);
        
        Queue::assertPushed(ProcessWebhookJob::class);
    }

    public function test_webhook_replay_protection_prevents_duplicate_processing()
    {
        Queue::fake();

        $payload = ['event' => 'payment.captured', 'id' => 'pay_123'];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        // First call
        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);
        
        // Second call (Duplicate)
        $response = $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'duplicate']);
        
        Queue::assertPushed(ProcessWebhookJob::class, 1);
    }

    public function test_invalid_signature_is_rejected()
    {
        $payload = ['event' => 'payment.captured'];
        
        $response = $this->postJson('/api/v1/webhooks/razorpay', $payload, [
            'X-Razorpay-Signature' => 'invalid_sig'
        ]);

        $response->assertStatus(401);
    }

    public function test_stripe_webhook_is_verified_and_logged()
    {
        Queue::fake();

        $payload = ['type' => 'payment_intent.succeeded', 'id' => 'evt_stripe_123'];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('stripe')->generateTestSignature($payloadJson);

        $response = $this->postJson('/api/v1/webhooks/stripe', $payload, $headers);

        $response->assertStatus(200);
        $this->assertDatabaseHas('webhook_logs', [
            'webhook_id' => 'evt_stripe_123',
            'event_type' => 'payment_intent.succeeded'
        ]);
    }

    public function test_webhook_can_be_marked_as_failed_and_scheduled_for_retry()
    {
        $log = WebhookLog::create([
            'event_type' => 'test.event',
            'payload' => ['test' => 'data'],
            'status' => 'pending',
            'max_retries' => 5,
        ]);

        $log->markAsFailed('Something went wrong');

        $this->assertEquals('pending', $log->status);
        $this->assertEquals(1, $log->retry_count);
        $this->assertNotNull($log->next_retry_at);
        $this->assertEquals('Something went wrong', $log->error_message);
    }

    public function test_retry_job_can_be_dispatched()
    {
        Queue::fake();

        $log = WebhookLog::create([
            'event_type' => 'test.event',
            'payload' => ['test' => 'data'],
            'status' => 'failed',
            'retry_count' => 1,
            'next_retry_at' => now()->subMinute()
        ]);

        $ledger = WebhookEventLedger::create([
            'provider' => 'razorpay',
            'event_id' => 'evt_123',
            'payload_hash' => 'hash',
            'payload_size' => 10,
            'processing_status' => 'failed'
        ]);

        ProcessWebhookJob::dispatch($log, $ledger->id);

        Queue::assertPushed(ProcessWebhookJob::class);
    }

    public function test_exponential_backoff_is_calculated_correctly()
    {
        \Illuminate\Support\Carbon::setTestNow(now());
        $log = new WebhookLog();
        
        $log->retry_count = 0;
        $next = $log->calculateNextRetryAt();
        $this->assertEquals(60, abs($next->diffInSeconds(now())), 'Backoff for retry 0 should be 1 min');

        $log->retry_count = 1;
        $next = $log->calculateNextRetryAt();
        $this->assertEquals(120, abs($next->diffInSeconds(now())), 'Backoff for retry 1 should be 2 min');
        
        $log->retry_count = 2;
        $next = $log->calculateNextRetryAt();
        $this->assertEquals(240, abs($next->diffInSeconds(now())), 'Backoff for retry 2 should be 4 min');
        
        \Illuminate\Support\Carbon::setTestNow(); // Reset
    }

    public function test_webhook_is_marked_as_max_retries_reached_after_limit()
    {
        $log = WebhookLog::create([
            'event_type' => 'test.event',
            'payload' => ['test' => 'data'],
            'status' => 'failed',
            'retry_count' => 5,
            'max_retries' => 5
        ]);

        $log->markAsFailed('Final failure');

        $this->assertEquals('max_retries_reached', $log->status);
    }

    public function test_pending_retries_scope_returns_due_webhooks()
    {
        WebhookLog::create([
            'event_type' => 'test.event.1',
            'payload' => ['test' => '1'],
            'status' => 'pending',
            'next_retry_at' => now()->subMinute(),
            'retry_count' => 0,
            'max_retries' => 5
        ]);

        WebhookLog::create([
            'event_type' => 'test.event.2',
            'payload' => ['test' => '2'],
            'status' => 'pending',
            'next_retry_at' => now()->addMinute(),
            'retry_count' => 0,
            'max_retries' => 5
        ]);

        $due = WebhookLog::pendingRetries()->get();
        $this->assertCount(1, $due);
    }

    public function test_webhook_can_be_marked_as_success()
    {
        $log = WebhookLog::create([
            'event_type' => 'test.event',
            'payload' => ['test' => 'data'],
            'status' => 'processing'
        ]);

        $log->markAsSuccess(['result' => 'ok'], 200);

        $this->assertEquals('success', $log->status);
        $this->assertEquals(200, $log->response_code);
        $this->assertEquals(['result' => 'ok'], $log->response);
        $this->assertNotNull($log->processed_at);
    }
}
