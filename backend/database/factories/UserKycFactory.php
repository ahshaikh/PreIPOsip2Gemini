<?php

namespace Database\Factories;

use App\Models\UserKyc;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserKycFactory extends Factory
{
    protected $model = UserKyc::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'pan_number' => strtoupper($this->faker->bothify('?????####?')),
            'aadhaar_number' => $this->faker->numerify('############'),
            'demat_account' => $this->faker->numerify('IN##########'),
            'bank_account' => $this->faker->numerify('##############'),
            'bank_ifsc' => strtoupper($this->faker->bothify('????0######')),
            'status' => 'pending',
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
            'verified_at' => now(),
            'submitted_at' => now()->subDays(2),
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => now()->subDays(1),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => $this->faker->sentence(),
            'submitted_at' => now()->subDays(3),
        ]);
    }
}
