<?php
// V-FINAL-1730-TEST-24

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use App\Models\Payment;
use App\Models\User;
use App\Models\Subscription;
use App\Services\RazorpayService;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Jobs\SendPaymentFailedEmailJob;
use Mockery;

class PaymentWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $subscription;
    protected $razorpayMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->user = User::factory()->create();
        $this->user->wallet()->create(['balance' => 0]);
        $this->subscription = Subscription::factory()->create(['user_id' => $this->user->id]);

        // Mock RazorpayService
        $this->razorpayMock = Mockery::mock(RazorpayService::class);
        $this->app->instance(RazorpayService::class, $this->razorpayMock);
    }

    private function mockSignatureValidation($isValid = true)
    {
        $this->razorpayMock->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn($isValid);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_handle_payment_captured_updates_payment_status()
    {
        $this->mockSignatureValidation(true);
        Queue::fake();

        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'gateway_order_id' => 'order_123',
            'status' => 'pending'
        ]);

        $payload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_123',
                        'order_id' => 'order_123',
                        'amount' => 100000
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/webhooks/razorpay', $payload, ['X-Razorpay-Signature' => 'valid']);

        $response->assertStatus(200);
        $this->assertEquals('paid', $payment->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_handle_payment_captured_triggers_allocation()
    {
        $this->mockSignatureValidation(true);
        Queue::fake();

        Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'gateway_order_id' => 'order_123',
            'status' => 'pending'
        ]);

        $payload = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_123', 'order_id' => 'order_123']]]
        ];

        $this->postJson('/api/v1/webhooks/razorpay', $payload, ['X-Razorpay-Signature' => 'valid']);

        Queue::assertPushed(ProcessSuccessfulPaymentJob::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_handle_payment_failed_updates_status()
    {
        $this->mockSignatureValidation(true);
        Queue::fake();

        $payment = Payment::factory()->create([
            'gateway_order_id' => 'order_fail',
            'status' => 'pending'
        ]);

        $payload = [
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'order_id' => 'order_fail',
                        'error_description' => 'Bank declined'
                    ]
                ]
            ]
        ];

        $this->postJson('/api/v1/webhooks/razorpay', $payload, ['X-Razorpay-Signature' => 'valid']);

        $this->assertEquals('failed', $payment->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_handle_payment_failed_sends_notification()
    {
        $this->mockSignatureValidation(true);
        Queue::fake();

        Payment::factory()->create(['gateway_order_id' => 'order_fail']);

        $payload = [
            'event' => 'payment.failed',
            'payload' => ['payment' => ['entity' => ['order_id' => 'order_fail']]]
        ];

        $this->postJson('/api/v1/webhooks/razorpay', $payload, ['X-Razorpay-Signature' => 'valid']);

        Queue::assertPushed(SendPaymentFailedEmailJob::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_handle_refund_processed_updates_records()
    {
        $this->mockSignatureValidation(true);

        $payment = Payment::factory()->create([
            'gateway_payment_id' => 'pay_refund',
            'status' => 'paid',
            'user_id' => $this->user->id
        ]);

        $payload = [
            'event' => 'refund.processed',
            'payload' => [
                'refund' => [
                    'entity' => [
                        'id' => 'rfnd_123',
                        'payment_id' => 'pay_refund'
                    ]
                ]
            ]
        ];

        $this->postJson('/api/v1/webhooks/razorpay', $payload, ['X-Razorpay-Signature' => 'valid']);

        $this->assertEquals('refunded', $payment->fresh()->status);
        $this->assertDatabaseHas('transactions', [
            'type' => 'refund',
            'reference_id' => $payment->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_webhook_rejects_invalid_signature()
    {
        $this->razorpayMock->shouldReceive('verifyWebhookSignature')
            ->once()
            ->andReturn(false);

        $response = $this->postJson('/api/v1/webhooks/razorpay', [], ['X-Razorpay-Signature' => 'fake']);

        $response->assertStatus(400)
                 ->assertJson(['error' => 'Invalid Signature']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_webhook_handles_duplicate_events()
    {
        $this->razorpayMock->shouldReceive('verifyWebhookSignature')->twice()->andReturn(true);
        Queue::fake();

        // 1. First Call
        $payment = Payment::factory()->create(['gateway_order_id' => 'order_dup', 'status' => 'pending']);
        
        $payload = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_dup', 'order_id' => 'order_dup']]]
        ];

        $this->postJson('/api/v1/webhooks/razorpay', $payload, ['X-Razorpay-Signature' => 'valid']);
        
        // 2. Second Call (Duplicate)
        $this->postJson('/api/v1/webhooks/razorpay', $payload, ['X-Razorpay-Signature' => 'valid']);

        // Job should only be pushed ONCE
        Queue::assertPushed(ProcessSuccessfulPaymentJob::class, 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_webhook_logs_all_events()
    {
        Log::shouldReceive('info')->atLeast()->once();
        // We must also mock the critical check in controller
        Log::shouldReceive('critical')->never();
        
        $this->mockSignatureValidation(true);

        $this->postJson('/api/v1/webhooks/razorpay', ['event' => 'ping'], ['X-Razorpay-Signature' => 'valid']);
    }
}