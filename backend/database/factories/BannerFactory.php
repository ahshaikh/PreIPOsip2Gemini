<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['info', 'warning', 'success', 'danger']),
            'location' => $this->faker->randomElement(['header', 'homepage', 'dashboard']),
            'image_url' => $this->faker->optional()->imageUrl(1200, 400, 'business'),
            'link_url' => $this->faker->optional()->url(),
            'link_text' => $this->faker->optional()->words(2, true),
            'is_dismissible' => $this->faker->boolean(),
            'start_date' => now()->subDays($this->faker->numberBetween(1, 30)),
            'end_date' => $this->faker->optional()->dateTimeBetween('now', '+3 months'),
            'is_active' => true,
            'display_order' => $this->faker->numberBetween(0, 10),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'start_date' => now()->subDays(1),
            'end_date' => now()->addMonths(1),
        ]);
    }
}
