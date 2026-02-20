<?php
// V-FINAL-1730-TEST-24
// V-CONTRACT-HARDENING-FINAL: Added payment amount mismatch tests

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use App\Models\Payment;
use App\Models\User;
use App\Models\Subscription;
use App\Services\RazorpayService;
use App\Services\PaymentWebhookService;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Jobs\SendPaymentFailedEmailJob;
use App\Exceptions\PaymentAmountMismatchException;
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
        $this->user->wallet()->create(['balance_paise' => 0, 'locked_balance_paise' => 0]);
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

    // =========================================================================
    // V-CONTRACT-HARDENING-FINAL: Payment Amount Mismatch Tests
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_charged_rejects_mismatched_amount()
    {
        Queue::fake();

        // Create subscription with contract amount of 1000
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 1000.00,
            'razorpay_subscription_id' => 'sub_test_mismatch_123',
            'status' => 'active',
            'next_payment_date' => now()->subDay(),
        ]);

        $initialPaymentCount = Payment::count();
        $initialNextPaymentDate = $subscription->next_payment_date->copy();

        // Webhook sends 1200 (mismatch!)
        $webhookService = app(PaymentWebhookService::class);

        $this->expectException(PaymentAmountMismatchException::class);
        $this->expectExceptionMessage('PAYMENT AMOUNT MISMATCH');

        $webhookService->handleSubscriptionCharged([
            'subscription_id' => 'sub_test_mismatch_123',
            'payment_id' => 'pay_mismatch_test_456',
            'amount' => 120000, // 1200 in paise (mismatch from 1000)
        ]);

        // These assertions run if exception is not thrown (test fails)
        $this->fail('PaymentAmountMismatchException should have been thrown');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_charged_mismatch_does_not_create_payment()
    {
        Queue::fake();

        // Create subscription with contract amount of 1000
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 1000.00,
            'razorpay_subscription_id' => 'sub_no_payment_123',
            'status' => 'active',
            'next_payment_date' => now()->subDay(),
        ]);

        $initialPaymentCount = Payment::count();

        $webhookService = app(PaymentWebhookService::class);

        try {
            $webhookService->handleSubscriptionCharged([
                'subscription_id' => 'sub_no_payment_123',
                'payment_id' => 'pay_no_create_456',
                'amount' => 120000, // 1200 in paise (mismatch!)
            ]);
        } catch (PaymentAmountMismatchException $e) {
            // Expected - now verify NO payment was created
            $this->assertEquals($initialPaymentCount, Payment::count(), 'No payment record should be created on mismatch');

            // Verify no payment with this gateway_payment_id exists
            $this->assertNull(
                Payment::where('gateway_payment_id', 'pay_no_create_456')->first(),
                'Payment record with mismatched amount should not exist'
            );
            return;
        }

        $this->fail('PaymentAmountMismatchException should have been thrown');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_charged_mismatch_does_not_mutate_subscription()
    {
        Queue::fake();

        // Create subscription with specific state
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 1000.00,
            'razorpay_subscription_id' => 'sub_no_mutate_123',
            'status' => 'active',
            'next_payment_date' => now()->addDays(5),
            'consecutive_payments_count' => 3,
        ]);

        $originalNextPaymentDate = $subscription->next_payment_date->toDateString();
        $originalConsecutiveCount = $subscription->consecutive_payments_count;
        $originalStatus = $subscription->status;

        $webhookService = app(PaymentWebhookService::class);

        try {
            $webhookService->handleSubscriptionCharged([
                'subscription_id' => 'sub_no_mutate_123',
                'payment_id' => 'pay_no_mutate_456',
                'amount' => 150000, // 1500 in paise (mismatch!)
            ]);
        } catch (PaymentAmountMismatchException $e) {
            // Refresh subscription from DB
            $subscription->refresh();

            // Verify NO subscription mutations occurred
            $this->assertEquals(
                $originalNextPaymentDate,
                $subscription->next_payment_date->toDateString(),
                'next_payment_date should not change on amount mismatch'
            );
            $this->assertEquals(
                $originalConsecutiveCount,
                $subscription->consecutive_payments_count,
                'consecutive_payments_count should not change on amount mismatch'
            );
            $this->assertEquals(
                $originalStatus,
                $subscription->status,
                'status should not change on amount mismatch'
            );
            return;
        }

        $this->fail('PaymentAmountMismatchException should have been thrown');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_charged_mismatch_logs_critical()
    {
        Queue::fake();

        // Spy on the financial_contract log channel
        Log::shouldReceive('channel')
            ->with('financial_contract')
            ->andReturnSelf();

        Log::shouldReceive('critical')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'PAYMENT AMOUNT MISMATCH')
                    && isset($context['contract_amount'])
                    && isset($context['webhook_amount'])
                    && isset($context['alert_level'])
                    && $context['alert_level'] === 'CRITICAL';
            });

        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 1000.00,
            'razorpay_subscription_id' => 'sub_log_test_123',
            'status' => 'active',
        ]);

        $webhookService = app(PaymentWebhookService::class);

        try {
            $webhookService->handleSubscriptionCharged([
                'subscription_id' => 'sub_log_test_123',
                'payment_id' => 'pay_log_test_456',
                'amount' => 120000, // 1200 in paise (mismatch!)
            ]);
        } catch (PaymentAmountMismatchException $e) {
            // Expected - test passes if CRITICAL log was called (verified by mock)
            return;
        }

        $this->fail('PaymentAmountMismatchException should have been thrown');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_charged_accepts_matching_amount()
    {
        Queue::fake();

        // Create subscription with contract amount of 1000
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 1000.00,
            'razorpay_subscription_id' => 'sub_match_123',
            'status' => 'active',
            'next_payment_date' => now(),
        ]);

        $initialPaymentCount = Payment::count();

        $webhookService = app(PaymentWebhookService::class);

        // Webhook sends exact matching amount
        $webhookService->handleSubscriptionCharged([
            'subscription_id' => 'sub_match_123',
            'payment_id' => 'pay_match_456',
            'amount' => 100000, // 1000 in paise (MATCHES!)
        ]);

        // Payment SHOULD be created
        $this->assertEquals($initialPaymentCount + 1, Payment::count(), 'Payment should be created for matching amount');

        // Verify payment exists with correct amount
        $payment = Payment::where('gateway_payment_id', 'pay_match_456')->first();
        $this->assertNotNull($payment, 'Payment record should exist');
        $this->assertEquals(1000.00, (float) $payment->amount, 'Payment amount should match contract');

        // Job should be dispatched
        Queue::assertPushed(ProcessSuccessfulPaymentJob::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_subscription_charged_accepts_amount_with_decimal_precision()
    {
        Queue::fake();

        // Test with decimal amounts that need precision handling
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 999.99,
            'razorpay_subscription_id' => 'sub_decimal_123',
            'status' => 'active',
            'next_payment_date' => now(),
        ]);

        $webhookService = app(PaymentWebhookService::class);

        // Webhook sends 99999 paise = 999.99 rupees
        $webhookService->handleSubscriptionCharged([
            'subscription_id' => 'sub_decimal_123',
            'payment_id' => 'pay_decimal_456',
            'amount' => 99999, // 999.99 in paise
        ]);

        $payment = Payment::where('gateway_payment_id', 'pay_decimal_456')->first();
        $this->assertNotNull($payment, 'Payment should be created for matching decimal amount');
        $this->assertEquals(999.99, (float) $payment->amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_amount_mismatch_exception_contains_correct_data()
    {
        $exception = new PaymentAmountMismatchException(
            subscriptionId: 123,
            expectedAmount: 1000.00,
            webhookAmount: 1200.00,
            razorpaySubscriptionId: 'sub_test',
            razorpayPaymentId: 'pay_test'
        );

        $this->assertEquals(123, $exception->getSubscriptionId());
        $this->assertEquals(1000.00, $exception->getExpectedAmount());
        $this->assertEquals(1200.00, $exception->getWebhookAmount());
        $this->assertEquals('sub_test', $exception->getRazorpaySubscriptionId());
        $this->assertEquals('pay_test', $exception->getRazorpayPaymentId());
        $this->assertEquals(200.00, $exception->getAmountDifference());

        // Verify reportContext() for logging
        $context = $exception->reportContext();
        $this->assertEquals('PaymentAmountMismatchException', $context['exception_type']);
        $this->assertEquals('CRITICAL', $context['alert_level']);
        $this->assertEquals(1000.00, $context['expected_amount']);
        $this->assertEquals(1200.00, $context['webhook_amount']);
    }
}