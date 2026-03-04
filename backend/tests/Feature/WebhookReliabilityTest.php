<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookJob;
use App\Models\ProcessedWebhookEvent;
use App\Models\WebhookEventLedger;
use App\Models\WebhookLog;
use App\Services\Webhooks\WebhookVerifierRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = app(WebhookVerifierRegistry::class);
    }

    /**
     * Test business-level idempotency.
     * Logic: Handler should only run logic once per event_id.
     */
    public function test_business_logic_executes_only_once_for_same_event_id(): void
    {
        $payloadData = [
            'id' => 'evt_idem_123',
            'event' => 'payment.captured',
            'created_at' => 1700000000,
            'payload' => ['payment' => ['entity' => ['id' => 'pay_idem_123', 'amount' => 1000]]]
        ];
        $payload = json_encode($payloadData);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payload);

        // 1. First execution
        $response1 = $this->withHeaders($headers)->postJson('/api/v1/webhooks/razorpay', $payloadData);
        $response1->assertStatus(200);

        $this->assertEquals(1, ProcessedWebhookEvent::where('event_id', 'evt_idem_123')->count());

        // 2. Second execution (simulating job retry or manual replay bypass)
        $ledger = WebhookEventLedger::where('event_id', 'evt_idem_123')->first();
        $webhookLog = WebhookLog::where('webhook_id', 'evt_idem_123')->first();
        
        $ledger->update(['processing_status' => 'pending']);
        
        Log::spy();
        
        $job = new ProcessWebhookJob($webhookLog, $ledger->id);
        app()->call([$job, 'handle']);

        // Assert: Count remains 1, and we see an info log about skipping
        $this->assertEquals(1, ProcessedWebhookEvent::where('event_id', 'evt_idem_123')->count());
        Log::shouldHaveReceived('info')
            ->with('WebhookIdempotency: Business logic already executed for event evt_idem_123. Skipping.', \Mockery::type('array'));
    }

    /**
     * Test event ordering protection.
     * Logic: Older events for the same resource should be ignored.
     */
    public function test_out_of_order_events_are_ignored(): void
    {
        $resourceId = 'pay_order_123';
        
        // 1. Process a NEW event (t=200)
        $payloadDataNew = [
            'id' => 'evt_new_200',
            'event' => 'payment.captured',
            'created_at' => 200,
            'payload' => ['payment' => ['entity' => ['id' => $resourceId, 'amount' => 1000]]]
        ];
        $payloadNew = json_encode($payloadDataNew);
        $headersNew = $this->registry->get('razorpay')->generateTestSignature($payloadNew);
        
        $this->withHeaders($headersNew)->postJson('/api/v1/webhooks/razorpay', $payloadDataNew);
        
        $this->assertEquals(1, ProcessedWebhookEvent::where('resource_id', $resourceId)->count());
        $this->assertEquals(200, ProcessedWebhookEvent::where('resource_id', $resourceId)->first()->event_timestamp);

        // 2. Try to process an OLD event (t=100) for same resource
        $payloadDataOld = [
            'id' => 'evt_old_100',
            'event' => 'payment.failed', 
            'created_at' => 100,
            'payload' => ['payment' => ['entity' => ['id' => $resourceId, 'amount' => 1000]]]
        ];
        $payloadOld = json_encode($payloadDataOld);
        $headersOld = $this->registry->get('razorpay')->generateTestSignature($payloadOld);
        
        Log::spy();
        $responseOld = $this->withHeaders($headersOld)->postJson('/api/v1/webhooks/razorpay', $payloadDataOld);
        $responseOld->assertStatus(200);

        // Assert: ProcessedWebhookEvent count is still 1 (the t=200 one)
        $this->assertEquals(1, ProcessedWebhookEvent::where('resource_id', $resourceId)->count());
        $this->assertEquals(200, ProcessedWebhookEvent::where('resource_id', $resourceId)->first()->event_timestamp);
        
        // Verify warning log
        Log::shouldHaveReceived('warning')
            ->with("ORDERING PROTECTION: Out-of-order event ignored for payment {$resourceId}. [Incoming Timestamp: 100] < [Latest Processed: 200]. Provider: razorpay, Event: evt_old_100. Skipping processing to prevent state regression.", \Mockery::type('array'));
    }
}
