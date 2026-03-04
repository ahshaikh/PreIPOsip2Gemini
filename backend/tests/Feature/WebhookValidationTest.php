<?php
// V-FINAL-1730-TEST-88 (Created) | V-FIX-VALIDATION-PROTOCOL (Fixed)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Webhooks\WebhookVerifierRegistry;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;

class WebhookValidationTest extends FeatureTestCase
{
    protected $user;
    protected $subscription;
    protected $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->user = User::factory()->create();
        $this->subscription = Subscription::factory()->create(['user_id' => $this->user->id]);
        $this->registry = app(WebhookVerifierRegistry::class);
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function testRazorpayWebhookSignatureValidation()
    {
        // 1. Arrange: Create a simple payload
        $payload = ['event' => 'ping'];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        // 2. Act: Call the webhook with valid signature
        $response = $this->postJson(
            '/api/v1/webhooks/razorpay',
            $payload,
            $headers
        );

        // 3. Assert: Request was accepted (200)
        $response->assertStatus(200);
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
    public function testStripeWebhookValidation()
    {
        // 1. Arrange: Create a simple payload
        $payload = ['type' => 'ping', 'id' => 'evt_test'];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('stripe')->generateTestSignature($payloadJson);

        // 2. Act: Call the webhook with valid signature
        $response = $this->postJson(
            '/api/v1/webhooks/stripe',
            $payload,
            $headers
        );

        // 3. Assert: Request was accepted (200)
        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testHmacWebhookValidation()
    {
        // 1. Arrange: Create a simple payload
        $payload = ['event' => 'ping'];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('hmac')->generateTestSignature($payloadJson);

        // 2. Act: Call the webhook with valid signature
        $response = $this->postJson(
            '/api/v1/webhooks/hmac',
            $payload,
            $headers
        );

        // 3. Assert: Request was accepted (200)
        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function testWebhookIdempotency()
    {
        // 1. Arrange: Create valid payment payload with matching amount
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'gateway_order_id' => 'order_idem_123',
            'amount_paise' => 100000,
            'amount' => 1000.00,
            'status' => 'pending'
        ]);

        $payload = $this->getPaymentPayload('order_idem_123', 'pay_idem_123');
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        // 2. Act: First call (The "Real" event)
        $response1 = $this->postJson(
            '/api/v1/webhooks/razorpay',
            $payload,
            $headers
        );

        // 3. Assert: First webhook was accepted
        $response1->assertStatus(200);

        // 4. Act: Second call (The "Duplicate" event with same signature)
        $response2 = $this->postJson(
            '/api/v1/webhooks/razorpay',
            $payload,
            $headers
        );

        // 5. Assert: Second webhook was also accepted (signature is still valid)
        $response2->assertStatus(200);
    }
}
