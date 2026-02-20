<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

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

        $startDate = $this->faker->dateTimeBetween('-2 years', '-1 month');
        $duration  = $plan->duration_months;

        return [
            'user_id' => User::factory(),
            'plan_id' => $plan->id,
            'amount' => $plan->monthly_amount,
            'subscription_code' => 'SUB-' . Str::upper(Str::random(10)),
            'status' => 'active',
            'start_date' => $startDate,
            'end_date' => Carbon::instance($startDate)->addMonths($duration),
            'next_payment_date' => now()->addDays(5),

            // Deterministic default values
            'bonus_multiplier' => 1.0,
            'consecutive_payments_count' => 0,
            'pause_count' => 0,
            'is_auto_debit' => false,

            // Ensure snapshot field exists
            'bonus_contract_snapshot' => null,
        ];
    }

    /**
     * Automatically attach a valid bonus contract snapshot
     */
    public function withSnapshot(): static
    {
        return $this->afterCreating(function ($subscription) {

            $plan = $subscription->plan;

            $subscription->bonus_contract_snapshot = [
                'plan_id'                 => $plan->id,
                'plan_name'               => $plan->name,
                'monthly_amount_paise'    => $plan->monthly_amount,
                'duration_months'         => $plan->duration_months,

                // Progressive
                'progressive_rate'        => $plan->progressive_bonus_percentage ?? 0,

                // Milestones
                'milestone_12'            => $plan->milestone_12_bonus ?? 0,
                'milestone_24'            => $plan->milestone_24_bonus ?? 0,
                'milestone_36'            => $plan->milestone_36_bonus ?? 0,

                // Consistency
                'consistency_bonus'       => $plan->consistency_bonus ?? 0,

                // Profit share
                'profit_share_percentage' => $plan->profit_share_percentage ?? 0,

                // Timestamp freeze
                'snapshot_created_at'     => now()->toDateTimeString(),
            ];

            $subscription->save();
        });
    }

    /**
     * Explicitly remove snapshot (for negative tests only)
     */
    public function withoutSnapshot(): static
    {
        return $this->state([
            'bonus_contract_snapshot' => null,
        ]);
    }

    /**
     * Helper: simulate subscription that has reached specific month
     */
    public function atMonth(int $month): static
    {
        return $this->afterCreating(function ($subscription) use ($month) {

            $subscription->consecutive_payments_count = $month;
            $subscription->save();
        });
    }
}