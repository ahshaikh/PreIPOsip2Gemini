<?php
// V-FACTORY (Created for comprehensive test coverage)

namespace Database\Factories;

use App\Models\Referral;
use App\Models\User;
use App\Models\ReferralCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralFactory extends Factory
{
    protected $model = Referral::class;

    public function definition(): array
    {
        return [
            'referrer_id' => User::factory(),
            'referred_id' => User::factory(),
            'referral_campaign_id' => null,
            'status' => 'pending',
            'bonus_amount' => $this->faker->randomFloat(2, 100, 1000),
            'bonus_credited' => false,
        ];
    }

    /**
     * Referral is successful (referred user made first payment).
     */
    public function successful(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'successful',
            'bonus_credited' => true,
            'credited_at' => now(),
        ]);
    }

    /**
     * Referral is pending (referred user registered but no payment).
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'bonus_credited' => false,
        ]);
    }

    /**
     * Referral is part of a campaign.
     */
    public function withCampaign(): static
    {
        return $this->state(fn(array $attributes) => [
            'referral_campaign_id' => ReferralCampaign::factory(),
        ]);
    }
}
