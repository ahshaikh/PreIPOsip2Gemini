<?php
// V-WAVE1-FIX: Created factory for LuckyDrawEntry model

namespace Database\Factories;

use App\Models\LuckyDrawEntry;
use App\Models\User;
use App\Models\LuckyDraw;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class LuckyDrawEntryFactory extends Factory
{
    protected $model = LuckyDrawEntry::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lucky_draw_id' => LuckyDraw::factory(),
            'payment_id' => Payment::factory(),
            'base_entries' => $this->faker->numberBetween(1, 10),
            'bonus_entries' => $this->faker->numberBetween(0, 5),
            'is_winner' => false,
            'prize_rank' => null,
            'prize_amount' => null,
        ];
    }

    /**
     * Mark as winner with prize details.
     */
    public function winner(int $rank = 1, float $amount = 10000): static
    {
        return $this->state(fn(array $attributes) => [
            'is_winner' => true,
            'prize_rank' => $rank,
            'prize_amount' => $amount,
        ]);
    }

    /**
     * Create with specific entry counts.
     */
    public function withEntries(int $base, int $bonus = 0): static
    {
        return $this->state(fn(array $attributes) => [
            'base_entries' => $base,
            'bonus_entries' => $bonus,
        ]);
    }
}
