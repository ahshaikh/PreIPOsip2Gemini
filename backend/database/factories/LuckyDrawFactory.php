<?php
// V-FACTORY (Created for comprehensive test coverage)

namespace Database\Factories;

use App\Models\LuckyDraw;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LuckyDrawFactory extends Factory
{
    protected $model = LuckyDraw::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Lucky Draw',
            'draw_date' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
            'prize_structure' => [
                [
                    'tier' => 1,
                    'name' => 'First Prize',
                    'amount' => 10000,
                    'count' => 1,
                ],
                [
                    'tier' => 2,
                    'name' => 'Second Prize',
                    'amount' => 5000,
                    'count' => 2,
                ],
                [
                    'tier' => 3,
                    'name' => 'Third Prize',
                    'amount' => 1000,
                    'count' => 5,
                ],
            ],
            'status' => 'open',
            'frequency' => $this->faker->randomElement(['monthly', 'quarterly', 'custom']),
            'entry_rules' => [
                'min_investment' => 1000,
                'eligible_products' => ['all'],
            ],
            'result_visibility' => $this->faker->randomElement(['public', 'private', 'winners_only']),
            'certificate_template' => null,
            'draw_video_url' => null,
            'draw_metadata' => null,
            'created_by' => User::factory(),
            'executed_by' => null,
        ];
    }

    /**
     * Mark as completed/drawn.
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'executed_by' => User::factory(),
        ]);
    }

    /**
     * Mark as cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
