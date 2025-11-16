<?php
// V-FINAL-1730-604 (Created) | V-FINAL-1730-605 (Carbon Fix)

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon; // <-- IMPORT CARBON

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = Plan::first() ?? Plan::factory()->create();
        
        // $this->faker returns a standard PHP DateTime object
        $startDate = $this->faker->dateTimeBetween('-2 years', '-1 month');
        $duration = $plan->duration_months;

        return [
            'user_id' => User::factory(),
            'plan_id' => $plan->id,
            'amount' => $plan->monthly_amount,
            'subscription_code' => 'SUB-' . Str::upper(Str::random(10)),
            'status' => 'active',
            'start_date' => $startDate,
            
            // --- THE FIX ---
            // Cast the DateTime object to a Carbon instance to use addMonths()
            'end_date' => (Carbon::instance($startDate))->addMonths($duration),
            // -------------

            'next_payment_date' => now()->addDays(5),
            'bonus_multiplier' => 1.0,
            'consecutive_payments_count' => $this->faker->numberBetween(0, 12),
            'pause_count' => $this->faker->numberBetween(0, 3),
            'is_auto_debit' => false,
        ];
    }
}