<?php
// V-WAVE1-FIX: Created factory for CompanyShareListing model

namespace Database\Factories;

use App\Models\CompanyShareListing;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyShareListingFactory extends Factory
{
    protected $model = CompanyShareListing::class;

    public function definition(): array
    {
        $totalShares = $this->faker->numberBetween(1000, 100000);
        $faceValue = $this->faker->randomFloat(2, 10, 1000);
        $askingPrice = $faceValue * $this->faker->randomFloat(2, 0.8, 1.2);

        return [
            'company_id' => Company::factory(),
            'submitted_by' => CompanyUser::factory(), // V-WAVE1-FIX: FK references company_users not users
            'listing_title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'total_shares_offered' => $totalShares,
            'face_value_per_share' => $faceValue,
            'asking_price_per_share' => $askingPrice,
            'total_value' => $totalShares * $askingPrice,
            'minimum_purchase_value' => $this->faker->randomFloat(2, 1000, 10000),
            'current_company_valuation' => $this->faker->randomFloat(2, 1000000, 100000000),
            'valuation_currency' => 'INR',
            'percentage_of_company' => $this->faker->randomFloat(2, 0.1, 10),
            'terms_and_conditions' => $this->faker->paragraphs(2, true),
            'offer_valid_until' => $this->faker->dateTimeBetween('+1 month', '+6 months'),
            'lock_in_period' => $this->faker->numberBetween(0, 24),
            'status' => 'pending',
        ];
    }

    /**
     * Mark as approved.
     */
    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'approved',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Mark as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'rejected',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }
}
