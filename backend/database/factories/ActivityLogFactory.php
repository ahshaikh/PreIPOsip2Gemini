<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        $actions = [
            'login' => 'User logged in',
            'logout' => 'User logged out',
            'profile_update' => 'Updated profile information',
            'password_change' => 'Changed password',
            'kyc_submit' => 'Submitted KYC documents',
            'investment' => 'Made an investment',
            'withdrawal' => 'Requested withdrawal',
            'subscription_create' => 'Created new subscription',
            'subscription_pause' => 'Paused subscription',
        ];

        $action = $this->faker->randomElement(array_keys($actions));

        return [
            'user_id' => User::factory(),
            'action' => $action,
            'description' => $actions[$action],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'properties' => json_encode([
                'timestamp' => now()->toISOString(),
                'additional_info' => $this->faker->sentence(),
            ]),
        ];
    }
}
