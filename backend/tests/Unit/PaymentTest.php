<?php
// V-FINAL-1730-TEST-22

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;

class PaymentTest extends UnitTestCase
{
    protected $user;
    protected $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'next_payment_date' => now()
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_belongs_to_user()
    {
        $payment = Payment::create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'amount_paise' => 100000, // ₹1000 in paise
            'status' => 'pending'
        ]);

        $this->assertInstanceOf(User::class, $payment->user);
        $this->assertEquals($this->user->id, $payment->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_belongs_to_subscription()
    {
        $payment = Payment::create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'amount_paise' => 100000 // ₹1000 in paise
        ]);

        $this->assertInstanceOf(Subscription::class, $payment->subscription);
        $this->assertEquals($this->subscription->id, $payment->subscription->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_status_enum_validates()
    {
        // Verify we can store valid statuses following the state machine
        $payment = Payment::factory()->create(['status' => 'pending']);
        $this->assertEquals('pending', $payment->status);

        $payment->update(['status' => 'paid']);
        $this->assertEquals('paid', $payment->fresh()->status);
        
        $payment->update(['status' => 'refunded']);
        $this->assertEquals('refunded', $payment->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_method_enum_validates()
    {
        $payment = Payment::factory()->create(['method' => 'upi']);
        $this->assertEquals('upi', $payment->method);

        $payment->update(['method' => 'card']);
        $this->assertEquals('card', $payment->fresh()->method);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_tracks_razorpay_order_id()
    {
        $orderId = 'order_123456';
        $payment = Payment::factory()->create(['gateway_order_id' => $orderId]);
        
        $this->assertEquals($orderId, $payment->gateway_order_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_tracks_razorpay_payment_id()
    {
        $payId = 'pay_123456';
        $payment = Payment::factory()->create(['gateway_payment_id' => $payId]);
        
        $this->assertEquals($payId, $payment->gateway_payment_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_tracks_signature()
    {
        $sig = 'signature_hash_123';
        $payment = Payment::factory()->create(['gateway_signature' => $sig]);
        
        $this->assertEquals($sig, $payment->gateway_signature);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_marks_as_on_time_correctly()
    {
        // Case 1: On Time (Paid same day as created)
        $payment = Payment::factory()->create([
            'subscription_id' => $this->subscription->id,
            'paid_at' => now(),
            'is_on_time' => true
        ]);
        
        $this->assertTrue($payment->is_on_time);

        // Case 2: Logic Check
        // If subscription next_payment_date is TODAY
        // And we pay TODAY
        // It should be on time
        $this->assertTrue($payment->verifyOnTimeStatus());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_calculates_due_date()
    {
        // For simple payments, due date is creation date
        $now = now();
        $payment = Payment::factory()->create(['created_at' => $now]);
        
        $this->assertEquals($now->toDateTimeString(), $payment->due_date->toDateTimeString());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_scope_paid_returns_correct_records()
    {
        Payment::factory()->create(['status' => 'paid']);
        Payment::factory()->create(['status' => 'pending']);
        Payment::factory()->create(['status' => 'failed']);

        $this->assertEquals(1, Payment::paid()->count());
        $this->assertEquals('paid', Payment::paid()->first()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_scope_failed_returns_correct_records()
    {
        Payment::factory()->create(['status' => 'paid']);
        // Create as pending first, then fail, because pending -> failed is valid
        $payment = Payment::factory()->create(['status' => 'pending']);
        $payment->update(['status' => 'failed']);

        $this->assertEquals(1, Payment::failed()->count());
        $this->assertEquals('failed', Payment::failed()->first()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_scope_pending_returns_correct_records()
    {
        Payment::factory()->create(['status' => 'pending']);
        Payment::factory()->create(['status' => 'paid']);

        $this->assertEquals(1, Payment::pending()->count());
        $this->assertEquals('pending', Payment::pending()->first()->status);
    }
}
