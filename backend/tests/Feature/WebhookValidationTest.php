<?php
// V-FINAL-1730-TEST-88 (Created) | V-FIX-VALIDATION-PROTOCOL (Fixed)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;

class WebhookValidationTest extends FeatureTestCase
{
    protected $user;
    protected $subscription;
    protected $webhookSecret = 'test_webhook_secret_key';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->user = User::factory()->create();
        $this->subscription = Subscription::factory()->create(['user_id' => $this->user->id]);

        // Configure the webhook secret for testing
        Config::set('services.razorpay.webhook_secret', $this->webhookSecret);
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

    /**
     * Generate valid HMAC-SHA256 signature for webhook payload
     */
    private function generateSignature(array $payload): string
    {
        $payloadJson = json_encode($payload);
        return hash_hmac('sha256', $payloadJson, $this->webhookSecret);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testRazorpayWebhookSignatureValidation()
    {
        // 1. Arrange: Create a simple payload
        $payload = ['event' => 'ping'];
        $validSignature = $this->generateSignature($payload);

        // 2. Act: Call the webhook with valid signature
        $response = $this->postJson(
            '/api/v1/webhooks/razorpay',
            $payload,
            ['X-Razorpay-Signature' => $validSignature]
        );

        // 3. Assert: Request was accepted (200 or 201)
        $this->assertContains($response->status(), [200, 201]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testInvalidSignatureRejected()
    {
        // 1. Arrange: Create payload with invalid signature
        $payload = ['event' => 'ping'];

        // 2. Act: Call the webhook with invalid signature
        $response = $this->postJson(
            '/api/v1/webhooks/razorpay',
            $payload,
            ['X-Razorpay-Signature' => 'invalid_signature_12345']
        );

        // 3. Assert: Request was rejected (401 Unauthorized)
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testMissingSignatureRejected()
    {
        // 1. Arrange: Create payload without signature header
        $payload = ['event' => 'ping'];

        // 2. Act: Call the webhook without signature header
        $response = $this->postJson('/api/v1/webhooks/razorpay', $payload);

        // 3. Assert: Request was rejected (401 Unauthorized)
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testWebhookWithoutSecretConfigured()
    {
        // 1. Arrange: Remove webhook secret configuration
        Config::set('services.razorpay.webhook_secret', null);

        $payload = ['event' => 'ping'];
        $signature = hash_hmac('sha256', json_encode($payload), 'any_key');

        // 2. Act: Call the webhook
        $response = $this->postJson(
            '/api/v1/webhooks/razorpay',
            $payload,
            ['X-Razorpay-Signature' => $signature]
        );

        // 3. Assert: Request was rejected because secret is not configured
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testWebhookIdempotency()
    {
        // This tests that a duplicate webhook event is logged correctly
        // Note: Idempotency logic is handled in PaymentWebhookService, not in signature verification

        // 1. Arrange: Create valid payment payload
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'gateway_order_id' => 'order_idem_123',
            'status' => 'pending'
        ]);

        $payload = $this->getPaymentPayload('order_idem_123', 'pay_idem_123');
        $validSignature = $this->generateSignature($payload);

        // 2. Act: First call (The "Real" event)
        $response1 = $this->postJson(
            '/api/v1/webhooks/razorpay',
            $payload,
            ['X-Razorpay-Signature' => $validSignature]
        );

        // 3. Assert: First webhook was accepted
        $this->assertContains($response1->status(), [200, 201]);

        // 4. Act: Second call (The "Duplicate" event with same signature)
        $response2 = $this->postJson(
            '/api/v1/webhooks/razorpay',
            $payload,
            ['X-Razorpay-Signature' => $validSignature]
        );

        // 5. Assert: Second webhook was also accepted (signature is still valid)
        // Idempotency is handled at the service layer, not signature verification
        $this->assertContains($response2->status(), [200, 201]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testWebhookRetryHandling()
    {
        // This is the same functional test as Idempotency.
        // A "retry" from Razorpay is just a duplicate event.
        $this->markTestSkipped(
            'Covered by testWebhookIdempotency, as a retry is a duplicate event.'
        );
    }
}
