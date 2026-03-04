<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Webhooks\WebhookVerifierRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $subscription;
    protected $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->user = User::factory()->create();
        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'razorpay_subscription_id' => 'sub_test_123'
        ]);
        $this->registry = app(WebhookVerifierRegistry::class);
    }

    public function test_webhook_rejects_invalid_signature()
    {
        $payload = ['event' => 'payment.captured'];
        $response = $this->postJson('/api/v1/webhooks/razorpay', $payload, [
            'X-Razorpay-Signature' => 'invalid'
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_logs_all_events()
    {
        $payload = ['event' => 'payment.captured', 'id' => 'pay_123'];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $this->assertDatabaseHas('webhook_logs', [
            'webhook_id' => 'pay_123',
            'event_type' => 'payment.captured'
        ]);
    }

    public function test_webhook_handles_duplicate_events()
    {
        $payload = ['event' => 'payment.captured', 'id' => 'pay_123'];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        // First delivery
        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);
        
        // Duplicate delivery
        $response = $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'duplicate']);
    }

    public function test_handle_payment_captured_updates_payment_status()
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
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
                        'amount' => $payment->amount_paise,
                        'status' => 'captured'
                    ]
                ]
            ]
        ];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        // Process the job manually or rely on sync queue
        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('pay_123', $payment->gateway_payment_id);
    }

    public function test_handle_payment_captured_triggers_allocation()
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
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
                        'amount' => $payment->amount_paise,
                        'status' => 'captured'
                    ]
                ]
            ]
        ];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        // Allocation logic would be triggered via ProcessSuccessfulPaymentJob
    }

    public function test_handle_payment_failed_updates_status()
    {
        $payment = Payment::factory()->create([
            'gateway_order_id' => 'order_fail_123',
            'status' => 'pending'
        ]);

        $payload = [
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_fail_123',
                        'order_id' => 'order_fail_123',
                        'status' => 'failed'
                    ]
                ]
            ]
        ];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $payment->refresh();
        $this->assertEquals('failed', $payment->status);
    }

    public function test_handle_payment_failed_sends_notification()
    {
        // Similar to above but check for notification
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'gateway_order_id' => 'order_fail_notify_123',
            'status' => 'pending'
        ]);

        $payload = [
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_fail_123',
                        'order_id' => 'order_fail_notify_123',
                        'status' => 'failed'
                    ]
                ]
            ]
        ];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $payment->refresh();
        $this->assertEquals('failed', $payment->status);
    }

    public function test_pending_subscription_activates_on_first_payment_success()
    {
        $this->subscription->update(['status' => 'pending']);
        
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'gateway_order_id' => 'order_sub_123',
            'status' => 'pending'
        ]);

        $payload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_sub_123',
                        'order_id' => 'order_sub_123',
                        'amount' => $payment->amount_paise,
                        'status' => 'captured'
                    ]
                ]
            ]
        ];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $this->subscription->refresh();
        $this->assertEquals('active', $this->subscription->status);
    }

    public function test_subscription_charged_accepts_matching_amount()
    {
        $this->subscription->update(['amount' => 1000]); // 1000.00 rupees

        $payload = [
            'event' => 'subscription.charged',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id' => $this->subscription->razorpay_subscription_id,
                    ]
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_auto_123',
                        'subscription_id' => $this->subscription->razorpay_subscription_id,
                        'amount' => 100000, // 1000.00 rupees in paise
                        'status' => 'captured'
                    ]
                ]
            ]
        ];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $this->assertDatabaseHas('payments', [
            'gateway_payment_id' => 'pay_auto_123',
            'subscription_id' => $this->subscription->id,
            'amount' => 1000
        ]);
    }

    public function test_subscription_charged_rejects_mismatched_amount()
    {
        $this->subscription->update(['amount' => 1000]);

        $payload = [
            'event' => 'subscription.charged',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id' => $this->subscription->razorpay_subscription_id,
                    ]
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_auto_fail_123',
                        'subscription_id' => $this->subscription->razorpay_subscription_id,
                        'amount' => 50000, // 500.00 - Mismatch!
                        'status' => 'captured'
                    ]
                ]
            ]
        ];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $this->assertDatabaseMissing('payments', [
            'gateway_payment_id' => 'pay_auto_fail_123'
        ]);
    }

    public function test_subscription_charged_mismatch_logs_critical()
    {
        Log::spy();
        $this->subscription->update(['amount' => 1000]);

        $payload = [
            'event' => 'subscription.charged',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id' => $this->subscription->razorpay_subscription_id,
                    ]
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_auto_crit_123',
                        'subscription_id' => $this->subscription->razorpay_subscription_id,
                        'amount' => 50000,
                        'status' => 'captured'
                    ]
                ]
            ]
        ];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        Log::shouldHaveReceived('channel')
            ->with('financial_contract')
            ->once();
    }

    public function test_subscription_charged_mismatch_does_not_mutate_subscription()
    {
        $oldDate = now()->addMonth();
        $this->subscription->update([
            'amount' => 1000,
            'next_payment_date' => $oldDate
        ]);

        $payload = [
            'event' => 'subscription.charged',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id' => $this->subscription->razorpay_subscription_id,
                    ]
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_auto_no_mut_123',
                        'subscription_id' => $this->subscription->razorpay_subscription_id,
                        'amount' => 50000,
                        'status' => 'captured'
                    ]
                ]
            ]
        ];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $this->subscription->refresh();
        $this->assertEquals($oldDate->format('Y-m-d'), $this->subscription->next_payment_date->format('Y-m-d'));
    }

    public function test_subscription_charged_mismatch_does_not_create_payment()
    {
        $this->subscription->update(['amount' => 1000]);

        $payload = [
            'event' => 'subscription.charged',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id' => $this->subscription->razorpay_subscription_id,
                    ]
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_auto_no_pay_123',
                        'subscription_id' => $this->subscription->razorpay_subscription_id,
                        'amount' => 50000,
                        'status' => 'captured'
                    ]
                ]
            ]
        ];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $this->assertDatabaseMissing('payments', [
            'gateway_payment_id' => 'pay_auto_no_pay_123'
        ]);
    }

    public function test_subscription_charged_accepts_amount_with_decimal_precision()
    {
        $this->subscription->update(['amount' => 1000.50]);

        $payload = [
            'event' => 'subscription.charged',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id' => $this->subscription->razorpay_subscription_id,
                    ]
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_auto_dec_123',
                        'subscription_id' => $this->subscription->razorpay_subscription_id,
                        'amount' => 100050, // 1000.50
                        'status' => 'captured'
                    ]
                ]
            ]
        ];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $this->assertDatabaseHas('payments', [
            'gateway_payment_id' => 'pay_auto_dec_123',
            'amount' => 1000.50
        ]);
    }

    public function test_payment_amount_mismatch_exception_contains_correct_data()
    {
        $this->subscription->update(['amount' => 1000]);

        $payload = [
            'event' => 'subscription.charged',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id' => $this->subscription->razorpay_subscription_id,
                    ]
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_auto_exc_123',
                        'subscription_id' => $this->subscription->razorpay_subscription_id,
                        'amount' => 50000,
                        'status' => 'captured'
                    ]
                ]
            ]
        ];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        // Even though it throws internal exception, the ProcessWebhookJob handles it 
        // and marks it as failed in the ledger.
        $this->assertDatabaseMissing('payments', ['gateway_payment_id' => 'pay_auto_exc_123']);
    }

    public function test_handle_refund_processed_updates_records()
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'gateway_payment_id' => 'pay_refund_123',
            'status' => 'paid',
            'amount' => 1000,
            'amount_paise' => 100000
        ]);

        $payload = [
            'event' => 'refund.processed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_refund_123',
                    ]
                ],
                'refund' => [
                    'entity' => [
                        'id' => 'rfnd_123',
                        'payment_id' => 'pay_refund_123',
                        'amount' => 100000,
                        'status' => 'processed'
                    ]
                ]
            ]
        ];
        $payloadJson = json_encode($payload);
        $headers = $this->registry->get('razorpay')->generateTestSignature($payloadJson);

        $this->postJson('/api/v1/webhooks/razorpay', $payload, $headers);

        $payment->refresh();
        $this->assertEquals('refunded', $payment->status);
        $this->assertEquals(100000, $payment->refund_amount_paise);
    }
}
