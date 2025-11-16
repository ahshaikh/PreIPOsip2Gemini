<?php
// V-FINAL-1730-606 (Created)

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscription_id' => Subscription::factory(),
            'amount' => $this->faker->randomElement([1000, 5000, 10000, 25000]),
            'currency' => 'INR',
            'status' => 'pending', // Default status
            'payment_type' => 'sip_installment',
            'gateway' => 'razorpay',
            'gateway_order_id' => 'order_' . Str::random(12),
            'gateway_payment_id' => null,
            'gateway_signature' => null,
            'method' => null,
            'paid_at' => null,
            'is_on_time' => false,
            'is_flagged' => false,
            'flag_reason' => null,
            'retry_count' => 0,
            'failure_reason' => null,
        ];
    }
}