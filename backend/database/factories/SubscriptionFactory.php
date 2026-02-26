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
        $startDate = $this->faker->dateTimeBetween('-2 years', '-1 month');

        return [
            'user_id' => User::factory(),

            // Let Laravel resolve the plan properly (supports overrides cleanly)
            'plan_id' => Plan::factory(),

            // DO NOT precompute from a temporary plan here
            // These will be derived correctly in configure()
            'amount_paise' => 0,
            'amount' => 0,

            'subscription_code' => 'SUB-' . Str::upper(Str::random(10)),
            'status' => 'active',
            'start_date' => $startDate,
            'end_date' => Carbon::instance($startDate)->addMonths(12), // safe default
            'next_payment_date' => now()->addDays(5),

            // Deterministic defaults
            'bonus_multiplier' => 1.0,
            'consecutive_payments_count' => 0,
            'pause_count' => 0,
            'is_auto_debit' => false,

            // Snapshot handled in configure()
            'bonus_contract_snapshot' => null,
        ];
    }

    /**
     * V-WAVE2-FIX: Auto-configure valid snapshot after creation
     */
    public function configure(): static
    {
        return $this->afterMaking(function ($subscription) {

            // Ensure plan relationship is loaded
            $plan = $subscription->plan;

            if ($plan) {
                $subscription->amount = $plan->monthly_amount;
                $subscription->amount_paise = (int) round($plan->monthly_amount * 100);

                if (!$subscription->end_date) {
                    $duration = $plan->duration_months ?? 12;
                    $subscription->end_date = Carbon::parse($subscription->start_date)
                        ->addMonths($duration);
                }
            }

        })->afterCreating(function ($subscription) {

            // Keep your existing snapshot logic intact
            $this->applyValidSnapshot($subscription);

        });
    }
    /**
     * V-WAVE2-FIX: Apply valid snapshot with correct hash computation
     * V-WAVE3-FIX: Read configs from Plan when available, fall back to defaults
     */
    private function applyValidSnapshot($subscription): void
    {
        $snapshotAt = now();
        $plan = $subscription->plan;

        // V-WAVE3-FIX: Read from plan configs if available, otherwise use defaults
        $progressiveConfig = $plan->getConfig('progressive_config', [
            'rate' => 0.5,
            'start_month' => 4,
            'max_percentage' => 10,
        ]);
        $milestoneConfig = $plan->getConfig('milestone_config', [
            ['month' => 12, 'amount' => 1000],
            ['month' => 24, 'amount' => 2000],
            ['month' => 36, 'amount' => 5000],
        ]);
        $consistencyConfig = $plan->getConfig('consistency_config', [
            'amount_per_payment' => 50,
        ]);
        $welcomeBonusConfig = $plan->getConfig('welcome_bonus_config', [
            'amount' => 500,
        ]);

        // Compute hash using same algorithm as SubscriptionConfigSnapshotService
        $versionHash = $this->computeCanonicalHash(
            $subscription->plan_id,
            $snapshotAt,
            $progressiveConfig,
            $milestoneConfig,
            $consistencyConfig,
            $welcomeBonusConfig,
            null, // referral_tiers
            null, // celebration_bonus_config
            null  // lucky_draw_entries
        );

        $subscription->forceFill([
            'progressive_config' => $progressiveConfig,
            'milestone_config' => $milestoneConfig,
            'consistency_config' => $consistencyConfig,
            'welcome_bonus_config' => $welcomeBonusConfig,
            'config_snapshot_at' => $snapshotAt,
            'config_snapshot_version' => $versionHash,
        ])->save();
    }

    /**
     * V-WAVE2-FIX: Compute canonical hash matching SubscriptionConfigSnapshotService exactly
     */
    private function computeCanonicalHash(
        int $planId,
        \DateTimeInterface $snapshotAt,
        ?array $progressiveConfig,
        ?array $milestoneConfig,
        ?array $consistencyConfig,
        ?array $welcomeBonusConfig,
        ?array $referralTiers,
        ?array $celebrationConfig,
        ?array $luckyDrawConfig
    ): string {
        // V-WAVE2-FIX: Normalize timestamp to second precision (MySQL timestamp doesn't store microseconds)
        $normalizedTimestamp = \Carbon\Carbon::parse($snapshotAt)->setMicroseconds(0);

        $canonicalData = [
            'celebration_bonus_config' => $this->sortRecursively($celebrationConfig),
            'config_snapshot_at' => $normalizedTimestamp->format('Y-m-d\TH:i:s.000000P'), // Match service format
            'consistency_config' => $this->sortRecursively($consistencyConfig),
            'lucky_draw_entries' => $this->sortRecursively($luckyDrawConfig),
            'milestone_config' => $this->sortRecursively($milestoneConfig),
            'plan_id' => $planId,
            'progressive_config' => $this->sortRecursively($progressiveConfig),
            'referral_tiers' => $this->sortRecursively($referralTiers),
            'welcome_bonus_config' => $this->sortRecursively($welcomeBonusConfig),
        ];

        ksort($canonicalData);
        // V-WAVE2-FIX: Use same JSON flags as SubscriptionConfigSnapshotService
        $json = json_encode($canonicalData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        return substr(hash('sha256', $json), 0, 32);
    }

    /**
     * Recursively sort array for canonical hashing
     */
    private function sortRecursively(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        // Check if sequential array
        if (array_keys($data) === range(0, count($data) - 1)) {
            return array_map(fn($item) => is_array($item) ? $this->sortRecursively($item) : $item, $data);
        }

        // Associative array - sort by key
        ksort($data);
        return array_map(fn($item) => is_array($item) ? $this->sortRecursively($item) : $item, $data);
    }

    /**
     * V-WAVE2-FIX: Snapshots are now auto-applied via configure().
     * This method is kept for backward compatibility but is now a no-op.
     */
    public function withSnapshot(): static
    {
        // Snapshot is auto-applied in configure() - this is now a no-op
        return $this;
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