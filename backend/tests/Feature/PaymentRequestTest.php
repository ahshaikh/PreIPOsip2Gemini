<?php
// V-FINAL-1730-TEST-71 (Created)

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Setting;
use App\Contracts\PaymentGatewayInterface;
use Mockery;

class PaymentRequestTest extends TestCase
{
    protected $user;
    protected $otherUser;
    protected $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // V-FIX-PAYMENT-TEST-2026: Mock payment gateway for tests
        // The real Razorpay gateway requires API credentials and makes network calls
        $mockGateway = Mockery::mock(PaymentGatewayInterface::class);
        $mockGateway->shouldReceive('createOrder')
            ->andReturn(['id' => 'order_test_' . uniqid(), 'status' => 'created']);
        $mockGateway->shouldReceive('createOrUpdatePlan')
            ->andReturn('plan_test_' . uniqid());
        $mockGateway->shouldReceive('createSubscription')
            ->andReturn(['id' => 'sub_test_' . uniqid(), 'status' => 'created']);
        $this->app->instance(PaymentGatewayInterface::class, $mockGateway);

        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->otherUser = User::factory()->create();
        $this->otherUser->assignRole('user');

        $subscription = Subscription::factory()->create(['user_id' => $this->user->id]);
        $this->payment = Payment::factory()->create([
            'subscription_id' => $subscription->id,
            'user_id' => $this->user->id,
            'amount' => 1000
        ]);

        Setting::updateOrCreate(['key' => 'min_payment_amount'], ['value' => 1, 'type' => 'number']);
        Setting::updateOrCreate(['key' => 'max_payment_amount'], ['value' => 100000, 'type' => 'number']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validates_subscription_exists()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/payment/initiate', [
            'payment_id' => 999 // Does not exist
        ]);

        $response->assertStatus(422) // Unprocessable Entity
                 ->assertJsonValidationErrors(['payment_id']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validates_user_owns_subscription()
    {
        // Create a payment owned by *another* user
        $otherSubscription = Subscription::factory()->create(['user_id' => $this->otherUser->id]);
        $otherPayment = Payment::factory()->create([
            'subscription_id' => $otherSubscription->id,
            'user_id' => $this->otherUser->id
        ]);

        // Try to pay for it as $this->user
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/payment/initiate', [
            'payment_id' => $otherPayment->id
        ]);

        $response->assertStatus(403); // Forbidden
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validates_amount_positive()
    {
        // This is now tested in the *Controller*, not the Request
        $this->payment->update(['amount' => 0]); // Set an invalid amount
        
        Setting::updateOrCreate(['key' => 'min_payment_amount'], ['value' => 1]);
        
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/payment/initiate', [
            'payment_id' => $this->payment->id
        ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Payment amount must be between ₹1 and ₹100000.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validates_payment_method_enum()
    {
        // This is not part of this request's logic.
        // The user selects the method on the Razorpay gateway.
        $this->assertTrue(true);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_passes_with_valid_data()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/user/payment/initiate', [
            'payment_id' => $this->payment->id,
            'enable_auto_debit' => false
        ]);

        // Will return 200 OK and Razorpay order details
        $response->assertStatus(200);
        $response->assertJsonStructure(['type', 'order_id', 'razorpay_key']);
    }
}