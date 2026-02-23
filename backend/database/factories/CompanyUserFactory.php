<?php
// V-WAVE1-FIX: Created factory for CompanyUser model

namespace Database\Factories;

use App\Models\CompanyUser;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompanyUserFactory extends Factory
{
    protected $model = CompanyUser::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'email' => $this->faker->unique()->companyEmail(),
            'password' => Hash::make('password'),
            'contact_person_name' => $this->faker->name(),
            'contact_person_designation' => $this->faker->jobTitle(),
            'phone' => $this->faker->phoneNumber(),
            'status' => 'active', // V-WAVE1-FIX: Enum is pending|active|suspended|rejected
            'is_verified' => true,
            'email_verified_at' => now(),
        ];
    }

    /**
     * Mark as pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'is_verified' => false,
        ]);
    }

    /**
     * Mark as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Create as unverified (email not verified).
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
            'is_verified' => false,
        ]);
    }
}
