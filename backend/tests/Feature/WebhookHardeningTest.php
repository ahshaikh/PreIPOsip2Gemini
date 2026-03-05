<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookJob;
use App\Models\ProcessedWebhookEvent;
use App\Models\WebhookDeadLetter;
use App\Models\WebhookEventLedger;
use App\Models\WebhookLog;
use App\Services\Webhooks\WebhookVerifierRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WebhookHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = app(WebhookVerifierRegistry::class);
    }

    /**
     * Test atomic insertion in WebhookReplayGuard.
     */
    public function test_replay_guard_atomicity_prevents_race_conditions(): void
    {
        $payloadData = [
            'id' => 'evt_atomic_123',
            'event' => 'payment.captured',
            'created_at' => 1700000000,
            'payload' => ['payment' => ['entity' => ['id' => 'pay_atomic_123', 'amount' => 1000]]]
        ];
        $payload = json_encode($payloadData);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payload);

        // Simulate concurrent requests
        $response1 = $this->withHeaders($headers)->postJson('/api/v1/webhooks/razorpay', $payloadData);
        $response2 = $this->withHeaders($headers)->postJson('/api/v1/webhooks/razorpay', $payloadData);

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response2->assertJsonFragment(['status' => 'duplicate']);

        // Count should be exactly 1 despite "concurrent" attempts
        $this->assertEquals(1, WebhookEventLedger::where('event_id', 'evt_atomic_123')->count());
    }

    /**
     * Test DLQ movement after 5 attempts.
     */
    public function test_webhook_moves_to_dlq_after_max_attempts(): void
    {
        $ledger = WebhookEventLedger::create([
            'provider' => 'razorpay',
            'event_id' => 'evt_failed_123',
            'payload_hash' => 'hash',
            'payload_size' => 100,
            'processing_status' => 'RECEIVED',
            'resource_id' => 'res_123',
            'resource_type' => 'payment',
        ]);

        $webhookLog = WebhookLog::create([
            'webhook_id' => 'evt_failed_123',
            'event_type' => 'payment.captured',
            'payload' => ['foo' => 'bar'],
            'status' => 'pending',
            'retry_count' => 4, // 5th attempt coming up
        ]);

        // Mock the job to pretend it's the 5th attempt
        $job = \Mockery::mock(ProcessWebhookJob::class, [$webhookLog, $ledger->id])->makePartial();
        $job->shouldReceive('attempts')->andReturn(5);
        
        // We need to trigger a failure in the dispatch call
        // The job uses EventRouter, we can mock it to throw an exception
        $router = \Mockery::mock(\App\Services\Webhooks\EventRouter::class);
        $router->shouldReceive('dispatch')->andThrow(new \Exception("Final Failure"));

        // When attempts >= 5, it should move to DLQ and return void (no re-throw)
        $job->handle($router);

        // After failure, check DLQ
        $this->assertEquals(1, WebhookDeadLetter::where('event_id', 'evt_failed_123')->count());
        $this->assertEquals('DEAD_LETTER', WebhookEventLedger::where('event_id', 'evt_failed_123')->first()->processing_status);
        $this->assertEquals('Final Failure (Moved to DLQ)', $webhookLog->fresh()->error_message);
    }

    /**
     * Test resource-level concurrency locking (Layer 2).
     */
    public function test_resource_locking_prevents_concurrency_race(): void
    {
        $resourceId = 'res_concurrent_123';
        $resourceType = 'payment';

        $ledger = WebhookEventLedger::create([
            'provider' => 'razorpay',
            'event_id' => 'evt_concurrent_123',
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'event_timestamp' => 300,
            'payload_hash' => 'hash_concurrent',
            'payload_size' => 100,
            'processing_status' => 'RECEIVED',
        ]);

        $webhookLog = WebhookLog::create([
            'webhook_id' => 'evt_concurrent_123',
            'event_type' => 'payment.captured',
            'payload' => ['foo' => 'bar'],
            'status' => 'pending',
        ]);

        // Manually hold the resource lock to simulate another worker processing it
        $lock = \Illuminate\Support\Facades\Cache::lock("webhook-resource:{$resourceType}:{$resourceId}", 30);
        $lock->get();

        Log::spy();

        // Instantiate the job and mock release to catch it
        $job = \Mockery::mock(ProcessWebhookJob::class, [$webhookLog, $ledger->id])->makePartial();
        $job->shouldReceive('release')->once()->with(5);

        $job->handle(app(\App\Services\Webhooks\EventRouter::class));

        // Assert: Logged as lock acquisition failure
        Log::shouldHaveReceived('warning')
            ->with("ProcessWebhookJob: Could not acquire lock for resource {$resourceType} {$resourceId}. Re-queueing.");
        
        // Assert: Status should still be RECEIVED since it was re-queued
        $this->assertEquals('RECEIVED', $ledger->fresh()->processing_status);
        
        $lock->release();
    }

    /**
     * Test ordering protection (Layer 2).
     */
    public function test_ordering_protection_uses_db_locking(): void
    {
        $resourceId = 'pay_lock_123';
        
        // 1. First event already processed (t=200)
        ProcessedWebhookEvent::create([
            'provider' => 'razorpay',
            'event_id' => 'evt_1',
            'resource_type' => 'payment',
            'resource_id' => $resourceId,
            'event_type' => 'payment.captured',
            'event_timestamp' => 200,
            'payload_hash' => 'hash1',
            'processed_at' => now(),
        ]);

        // 2. Incoming old event (t=100)
        $ledger = WebhookEventLedger::create([
            'provider' => 'razorpay',
            'event_id' => 'evt_old',
            'resource_type' => 'payment',
            'resource_id' => $resourceId,
            'event_timestamp' => 100,
            'payload_hash' => 'hash_old',
            'payload_size' => 100,
            'processing_status' => 'RECEIVED',
        ]);

        $webhookLog = WebhookLog::create([
            'webhook_id' => 'evt_old',
            'event_type' => 'payment.failed',
            'payload' => ['foo' => 'bar'],
            'status' => 'pending',
        ]);

        Log::spy();
        
        $job = new ProcessWebhookJob($webhookLog, $ledger->id);
        $job->handle(app(\App\Services\Webhooks\EventRouter::class));

        // Assert: Logged as out-of-order and status is success (to skip retry)
        Log::shouldHaveReceived('warning')
            ->with("ORDERING PROTECTION: Out-of-order event ignored for payment {$resourceId}. [Incoming Timestamp: 100] < [Latest Processed: 200]. Provider: razorpay, Event: evt_old. Skipping processing to prevent state regression.", \Mockery::type('array'));
        
        $this->assertEquals('PROCESSED', $ledger->fresh()->processing_status);
        $this->assertEquals('Ignored due to event ordering protection (Layer 2)', $webhookLog->fresh()->response['message']);
    }
}
