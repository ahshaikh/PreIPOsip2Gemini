<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookJob;
use App\Models\WebhookDeadLetter;
use App\Models\WebhookEventLedger;
use App\Models\WebhookLog;
use App\Services\Webhooks\WebhookVerifierRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class WebhookReliabilityFinalHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = app(WebhookVerifierRegistry::class);
    }

    /**
     * Test full state machine lifecycle transitions.
     */
    public function test_webhook_lifecycle_state_machine_transitions(): void
    {
        Bus::fake();

        $payloadData = [
            'id' => 'evt_lifecycle_123',
            'event' => 'payment.captured',
            'created_at' => 1700000000,
            'payload' => ['payment' => ['entity' => ['id' => 'pay_lifecycle_123', 'amount' => 1000]]]
        ];
        $payload = json_encode($payloadData);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payload);

        // 1. Controller execution (RECEIVED -> VALIDATED -> ENQUEUED)
        $response = $this->withHeaders($headers)->postJson('/api/v1/webhooks/razorpay', $payloadData);
        $response->assertStatus(200);

        $ledger = WebhookEventLedger::where('event_id', 'evt_lifecycle_123')->first();
        $this->assertEquals('ENQUEUED', $ledger->processing_status);

        Bus::assertDispatched(ProcessWebhookJob::class);

        // 2. Job execution (ENQUEUED -> PROCESSING -> PROCESSED)
        $webhookLog = WebhookLog::where('webhook_id', 'evt_lifecycle_123')->first();
        $job = new ProcessWebhookJob($webhookLog, $ledger->id);
        
        $job->handle(app(\App\Services\Webhooks\EventRouter::class));

        $this->assertEquals('PROCESSED', $ledger->fresh()->processing_status);
        $this->assertNotNull($ledger->fresh()->processed_at);
    }

    /**
     * Test payload mismatch detection (Integrity Protection).
     */
    public function test_payload_mismatch_detection_blocks_processing(): void
    {
        // 1. Initial event recorded
        $ledger = WebhookEventLedger::create([
            'provider' => 'razorpay',
            'event_id' => 'evt_integrity_123',
            'payload_hash' => 'hash_original',
            'payload_size' => 100,
            'processing_status' => 'PROCESSED',
        ]);

        // 2. Replay with DIFFERENT payload
        $payloadData = [
            'id' => 'evt_integrity_123',
            'event' => 'payment.captured',
            'payload' => ['tampered' => true]
        ];
        $payload = json_encode($payloadData);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payload);

        Log::spy();

        $response = $this->withHeaders($headers)->postJson('/api/v1/webhooks/razorpay', $payloadData);

        // Assert: 403 Forbidden (Integrity Failure)
        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Payload mismatch detected']);

        // Assert: SECURITY ALERT logged
        Log::shouldHaveReceived('critical')
            ->with(\Mockery::pattern('/SECURITY ALERT: Webhook payload mismatch detected/'), \Mockery::any());

        $this->assertEquals(1, $ledger->fresh()->payload_mismatch_detected);
    }

    /**
     * Test stuck event recovery command.
     */
    public function test_stuck_event_recovery_reenqueues_jobs(): void
    {
        Bus::fake();

        $id = \Illuminate\Support\Facades\DB::table('webhook_event_ledger')->insertGetId([
            'provider' => 'razorpay',
            'event_id' => 'evt_stuck_123',
            'resource_type' => 'payment',
            'payload_hash' => 'hash',
            'payload_size' => 100,
            'processing_status' => 'PROCESSING',
            'updated_at' => now()->subMinutes(15),
            'created_at' => now()->subMinutes(15),
        ]);

        $ledger = WebhookEventLedger::find($id);

        \Illuminate\Support\Facades\DB::table('webhook_logs')->insert([
            'webhook_id' => 'evt_stuck_123',
            'event_type' => 'payment.captured',
            'provider' => 'razorpay',
            'resource_type' => 'payment',
            'payload' => json_encode([]),
            'headers' => json_encode([]),
            'status' => 'processing',
            'updated_at' => now()->subMinutes(15),
            'created_at' => now()->subMinutes(15),
        ]);

        $webhookLog = WebhookLog::where('webhook_id', 'evt_stuck_123')->first();

        // 2. Run recovery command
        Artisan::call('webhook:recover-stuck');

        // Assert: Status reset to ENQUEUED
        $this->assertEquals('ENQUEUED', $ledger->fresh()->processing_status);

        // Assert: Job re-dispatched to correct queue
        Bus::assertDispatched(ProcessWebhookJob::class, function ($job) {
            return $job->queue === 'webhooks_payments';
        });
    }

    /**
     * Test DLQ replay tool.
     */
    public function test_dlq_replay_tool_reenqueues_jobs(): void
    {
        Bus::fake();

        // 1. Setup DLQ entry and failed ledger
        $ledger = WebhookEventLedger::create([
            'provider' => 'razorpay',
            'event_id' => 'evt_dlq_123',
            'resource_type' => 'subscription',
            'payload_hash' => 'hash',
            'payload_size' => 100,
            'processing_status' => 'DEAD_LETTER',
        ]);

        WebhookDeadLetter::create([
            'provider' => 'razorpay',
            'event_id' => 'evt_dlq_123',
            'resource_type' => 'subscription',
            'resource_id' => 'sub_123',
            'payload' => [],
            'error_message' => 'Final exhaustion',
            'attempts' => 5,
        ]);

        $webhookLog = WebhookLog::create([
            'webhook_id' => 'evt_dlq_123',
            'event_type' => 'subscription.created',
            'provider' => 'razorpay',
            'resource_type' => 'subscription',
            'payload' => [],
            'headers' => [],
            'status' => 'max_retries_reached',
            'retry_count' => 5,
        ]);

        // 2. Run replay command
        $exitCode = Artisan::call('webhook:replay', ['event_id' => 'evt_dlq_123']);
        if ($exitCode !== 0) {
            dump(Artisan::output());
        }
        $this->assertEquals(0, $exitCode);

        // Assert: Status reset to ENQUEUED
        $this->assertEquals('ENQUEUED', $ledger->fresh()->processing_status);

        // Assert: WebhookLog reset
        $this->assertEquals('pending', $webhookLog->fresh()->status);
        $this->assertEquals(0, $webhookLog->fresh()->retry_count);

        // Assert: Job re-dispatched to correct queue
        Bus::assertDispatched(ProcessWebhookJob::class, function ($job) {
            return $job->queue === 'webhooks_subscriptions';
        });
    }
}
