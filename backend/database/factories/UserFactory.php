<?php
// V-DEPLOY-1730-004 (Created) | V-FINAL-1730-602 (Relations Added)

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'mobile' => $this->faker->unique()->numerify('9#########'),
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'referral_code' => Str::upper(Str::random(10)),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'mobile_verified_at' => null,
            'status' => 'pending',
        ]);
    }

    /**
     * Configure the model factory.
     * This ensures relations are created.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            // Create the 3 critical relations for every user
            UserProfile::firstOrCreate(['user_id' => $user->id]);
            UserKyc::firstOrCreate(['user_id' => $user->id]);
            Wallet::firstOrCreate(['user_id' => $user->id], ['balance_paise' => 0, 'locked_balance_paise' => 0]);
        });
    }
}