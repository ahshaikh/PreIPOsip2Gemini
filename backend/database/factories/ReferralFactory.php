<?php

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
            'referrer_id'          => User::factory(),
            'referred_id'          => User::factory(),
            'referral_campaign_id' => null,
            'status'               => 'pending',
            'completed_at'         => null,
        ];
    }

    /**
     * Referral successful → completed_at filled
     */
    public function successful(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'       => 'successful',
            'completed_at' => now(),
        ]);
    }

    /**
     * Referral pending
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'       => 'pending',
            'completed_at' => null,
        ]);
    }

    /**
     * Referral attached to a campaign
     */
    public function withCampaign(): static
    {
        return $this->state(fn(array $attributes) => [
            'referral_campaign_id' => ReferralCampaign::factory(),
        ]);
    }
}
