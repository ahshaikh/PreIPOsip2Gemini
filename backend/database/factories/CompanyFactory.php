<?php
/**
 * STORY 3.1: Company Factory
 *
 * Factory for creating Company model instances in tests.
 */

namespace Database\Factories;

use App\Enums\DisclosureTier;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = $this->faker->company() . ' ' . $this->faker->companySuffix();
        $slug = Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1000, 9999);

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $this->faker->paragraphs(3, true),
            'logo' => null,
            'website' => $this->faker->url(),
            'sector' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Education', 'Energy', 'E-commerce', 'Fintech', 'Manufacturing']),
            'founded_year' => $this->faker->numberBetween(2000, 2024),
            'headquarters' => $this->faker->city() . ', ' . $this->faker->state(),
            'ceo_name' => $this->faker->name(),
            'latest_valuation' => $this->faker->randomFloat(2, 1000000, 100000000),
            'funding_stage' => $this->faker->randomElement(['Seed', 'Series A', 'Series B', 'Series C', 'Pre-IPO']),
            'total_funding' => $this->faker->randomFloat(2, 100000, 50000000),
            'linkedin_url' => 'https://linkedin.com/company/' . Str::slug($name),
            'twitter_url' => 'https://twitter.com/' . Str::slug($name, ''),
            'facebook_url' => null,
            'key_metrics' => [
                'revenue' => $this->faker->randomFloat(2, 100000, 10000000),
                'employees' => $this->faker->numberBetween(10, 1000),
                'growth_rate' => $this->faker->randomFloat(1, 10, 200),
            ],
            'investors' => [
                $this->faker->company(),
                $this->faker->company(),
            ],
            'is_featured' => false,
            'status' => 'active',
            'is_verified' => false,
            'profile_completed' => false,
            'profile_completion_percentage' => $this->faker->numberBetween(30, 100),
            'max_users_quota' => 10,
            'settings' => [],
            'disclosure_tier' => DisclosureTier::TIER_0_PENDING->value,
        ];
    }

    /**
     * Company is verified.
     */
    public function verified(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_verified' => true,
            'profile_completed' => true,
            'profile_completion_percentage' => 100,
        ]);
    }

    /**
     * Company is featured.
     */
    public function featured(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_featured' => true,
            'is_verified' => true,
        ]);
    }

    /**
     * Company has completed profile.
     */
    public function profileCompleted(): static
    {
        return $this->state(fn(array $attributes) => [
            'profile_completed' => true,
            'profile_completion_percentage' => 100,
        ]);
    }

    /**
     * Company is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Set disclosure tier to pending (tier_0).
     */
    public function tierPending(): static
    {
        return $this->state(fn(array $attributes) => [
            'disclosure_tier' => DisclosureTier::TIER_0_PENDING->value,
        ]);
    }

    /**
     * Set disclosure tier to upcoming (tier_1).
     */
    public function tierUpcoming(): static
    {
        return $this->state(fn(array $attributes) => [
            'disclosure_tier' => DisclosureTier::TIER_1_UPCOMING->value,
        ]);
    }

    /**
     * Set disclosure tier to live (tier_2).
     */
    public function tierLive(): static
    {
        return $this->state(fn(array $attributes) => [
            'disclosure_tier' => DisclosureTier::TIER_2_LIVE->value,
            'is_verified' => true,
            'profile_completed' => true,
        ]);
    }

    /**
     * Set disclosure tier to featured (tier_3).
     */
    public function tierFeatured(): static
    {
        return $this->state(fn(array $attributes) => [
            'disclosure_tier' => DisclosureTier::TIER_3_FEATURED->value,
            'is_verified' => true,
            'is_featured' => true,
            'profile_completed' => true,
        ]);
    }

    /**
     * Company is publicly visible (tier_2 or higher).
     */
    public function publiclyVisible(): static
    {
        return $this->tierLive();
    }
}
