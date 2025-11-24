<?php
// V-FINAL-1730-TEST-88 (Created)

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\RazorpayService;
use App\Jobs\ProcessSuccessfulPaymentJob;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

class WebhookValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $subscription;
    protected $razorpayMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        
        $this->user = User::factory()->create();
        $this->subscription = Subscription::factory()->create(['user_id' => $this->user->id]);

        // Mock the RazorpayService in the service container
        // This is used by the WebhookController to verify signatures
        $this->razorpayMock = $this->mock(RazorpayService::class);
    }

    /**
     * Helper to create a standard test payload
     */
    private function getPaymentPayload($orderId, $paymentId)
    {
        return [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => $paymentId,
                        'order_id' => $orderId,
                        'amount' => 100000, // 1000.00
                        'status' => 'captured'
                    ]
                ]
            ]
        ];
    }

    /** @test */
    public function testRazorpayWebhookSignatureValidation()
    {
        // 1. Arrange: Tell the mock service to return TRUE
        $this->razorpayMock->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(true);

        // 2. Act: Call the webhook
        $response = $this->postJson(
            '/api/v1/webhooks/razorpay', 
            ['event' => 'ping'], // A simple payload
            ['X-Razorpay-Signature' => 'valid_signature']
        );

        // 3. Assert: Request was accepted
        $response->assertStatus(200);
    }

    /** @test */
    public function testInvalidSignatureRejected()
    {
        // 1. Arrange: Tell the mock service to return FALSE
        $this->razorpayMock->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(false);

        // 2. Act: Call the webhook
        $response = $this->postJson(
            '/api/v1/webhooks/razorpay', 
            ['event' => 'ping'], 
            ['X-Razorpay-Signature' => 'invalid_signature']
        );

        // 3. Assert: Request was forbidden
        $response->assertStatus(400); // 400 Bad Request
        $response->assertJson(['error' => 'Invalid Signature']);
    }

    /** @test */
    public function testWebhookIdempotency()
    {
        // This tests that a duplicate webhook event is ignored
        Queue::fake(); // We will check if the job is pushed
        
        // 1. Arrange
        $this->razorpayMock->shouldReceive('verifyWebhookSignature')
            ->twice() // We will call the webhook twice
            ->andReturn(true);

        // Create the pending payment in the DB
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'gateway_order_id' => 'order_idem_123',
            'status' => 'pending'
        ]);
        
        $payload = $this->getPaymentPayload('order_idem_123', 'pay_idem_123');

        // 2. Act: First call (The "Real" event)
        $this->postJson('/api/v1/webhooks/razorpay', $payload, ['X-Razorpay-Signature' => 'valid']);

        // 3. Assert: Job was pushed, payment is 'paid'
        Queue::assertPushed(ProcessSuccessfulPaymentJob::class, 1);
        $this->assertEquals('paid', $payment->fresh()->status);
        
        // 4. Act: Second call (The "Duplicate" event)
        $this->postJson('/api/v1/webhooks/razorpay', $payload, ['X-Razorpay-Signature' => 'valid']);

        // 5. Assert: No *new* job was pushed
        // The service should have seen the payment was already 'paid' and stopped.
        Queue::assertPushed(ProcessSuccessfulPaymentJob::class, 1); // Still 1
    }

    /** @test */
    public function testWebhookRetryHandling()
    {
        // This is the same functional test as Idempotency.
        // A "retry" from Razorpay is just a duplicate event.
        $this->markTestSkipped(
            'Covered by testWebhookIdempotency, as a retry is a duplicate event.'
        );
    }
}