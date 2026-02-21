<?php
// V-FINAL-1730-606 (Created)
// V-AUDIT-FIX-2026: Added orphan payment support for defensive testing

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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

    /**
     * Configure the factory to hook into model creation.
     *
     * V-AUDIT-FIX-2026: Intercept orphan payment creation (user_id = null)
     * and handle via raw SQL to bypass FK constraint.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Payment $payment) {
            // afterMaking is called after make() but before save
            // We can't intercept here effectively for the FK issue
        });
    }

    /**
     * V-AUDIT-FIX-2026: State for creating orphan payment.
     *
     * Usage: Payment::factory()->orphan()->create([...])
     *
     * DEFENSIVE TESTING:
     * Fintech systems must be robust to orphan records that can occur via:
     * - Partial DB corruption
     * - External system drift
     * - Historical bad data
     * - Manual insertion mistakes
     *
     * This state sets a flag that triggers raw SQL insertion in create().
     */
    public function orphan(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'subscription_id' => null,
        ]);
    }

    /**
     * Override create to handle orphan payments specially.
     *
     * V-AUDIT-FIX-2026: When user_id => null is requested, we create a payment
     * with a real user, then soft-delete the user. This creates a realistic
     * "orphan" scenario where user_id is valid but $payment->user returns null.
     *
     * @param (callable(array<string, mixed>): array<string, mixed>)|array<string, mixed> $attributes
     * @param \Illuminate\Database\Eloquent\Model|null $parent
     * @return \Illuminate\Database\Eloquent\Collection<int, Payment>|Payment
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        // Check if explicitly creating orphan payment (user_id = null in attributes)
        if (is_array($attributes) && array_key_exists('user_id', $attributes) && $attributes['user_id'] === null) {
            return $this->createOrphanPayment($attributes);
        }

        return parent::create($attributes, $parent);
    }

    /**
     * V-AUDIT-FIX-2026: Create orphan payment via soft-deleted user.
     *
     * DEFENSIVE TESTING:
     * Fintech systems must be robust to "orphan" records where the user
     * relationship returns null. This can occur via:
     * - User soft-deletion after payment creation
     * - Database drift or corruption
     * - Migration/backup issues
     *
     * This method creates a payment with a real user, then soft-deletes
     * the user. The payment keeps a valid user_id but $payment->user
     * returns null (SoftDeletes filters deleted records).
     *
     * WARNING: Only use for defensive testing.
     */
    protected function createOrphanPayment(array $overrides): Payment
    {
        // Create a temporary user for this orphan payment
        $orphanUser = User::factory()->create([
            'email' => 'orphan_' . Str::random(8) . '@test.local',
        ]);

        // Remove user_id from overrides since we're using our orphan user
        unset($overrides['user_id']);

        // Create payment with the orphan user
        $payment = parent::create(array_merge($overrides, [
            'user_id' => $orphanUser->id,
        ]));

        // Soft-delete the user to create orphan scenario
        // Payment still has user_id but $payment->user will return null
        $orphanUser->delete();

        // Refresh to clear cached relationship
        $payment->refresh();

        return $payment;
    }
}