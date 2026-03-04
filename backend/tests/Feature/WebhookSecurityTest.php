<?php

namespace Tests\Feature;

use App\Models\WebhookEventLedger;
use App\Services\Webhooks\WebhookVerifierRegistry;
use App\Jobs\ProcessWebhookJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test duplicate webhook with different payload.
     * Expected behavior:
     * - 200 response
     * - ledger entry flagged payload_mismatch_detected
     * - security log generated
     */
    public function test_duplicate_webhook_with_different_payload_is_flagged(): void
    {
        Log::spy();
        Queue::fake();

        /** @var WebhookVerifierRegistry $registry */
        $registry = app(WebhookVerifierRegistry::class);

        $payloadData1 = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_123', 'amount' => 1000]]]
        ];
        $payload1 = json_encode($payloadData1);
        
        // 1. Send first webhook
        $headers1 = $registry->get('razorpay')->generateTestSignature($payload1);
        $response1 = $this->withHeaders($headers1)
            ->postJson('/api/v1/webhooks/razorpay', $payloadData1);

        $response1->assertStatus(200);
        $this->assertDatabaseHas('webhook_event_ledger', [
            'provider' => 'razorpay',
            'event_id' => 'pay_123',
            'payload_mismatch_detected' => false,
        ]);

        Queue::assertPushed(ProcessWebhookJob::class);

        // 2. Send second webhook with same event_id but DIFFERENT payload (amount changed)
        $payloadData2 = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_123', 'amount' => 999999]]] // Tampered!
        ];
        $payload2 = json_encode($payloadData2);

        // Valid signature for NEW tampered payload
        $headers2_valid_tampered = $registry->get('razorpay')->generateTestSignature($payload2);
        
        $response2 = $this->withHeaders($headers2_valid_tampered)
            ->postJson('/api/v1/webhooks/razorpay', $payloadData2);

        $response2->assertStatus(200);
        $response2->assertJson(['status' => 'duplicate']);

        // 3. Verify ledger entry
        $ledger = WebhookEventLedger::where('event_id', 'pay_123')->first();
        $this->assertTrue($ledger->replay_detected);
        $this->assertTrue($ledger->payload_mismatch_detected);

        // 4. Verify security log was generated
        Log::shouldHaveReceived('critical')
            ->once()
            ->with('SECURITY ALERT: Webhook payload mismatch detected for replay. Possible tampering attempt.', \Mockery::type('array'));
    }
}
