<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationFactory extends Factory
{
    public function definition(): array
    {
        $types = [
            'App\\Notifications\\InvestmentConfirmed',
            'App\\Notifications\\KYCVerified',
            'App\\Notifications\\PaymentReceived',
            'App\\Notifications\\SubscriptionCreated',
            'App\\Notifications\\WithdrawalProcessed',
        ];

        return [
            'id' => Str::uuid(),
            'type' => $this->faker->randomElement($types),
            'notifiable_type' => User::class,
            'notifiable_id' => User::factory(),
            'data' => json_encode([
                'title' => $this->faker->sentence(4),
                'message' => $this->faker->sentence(10),
                'action_url' => $this->faker->optional()->url(),
            ]),
            'read_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }
}
