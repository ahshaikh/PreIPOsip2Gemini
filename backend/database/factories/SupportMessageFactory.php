<?php

namespace Database\Factories;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupportMessageFactory extends Factory
{
    protected $model = SupportMessage::class;

    public function definition(): array
    {
        return [
            'support_ticket_id' => SupportTicket::factory(),
            'user_id' => User::factory(),
            'message' => $this->faker->paragraphs(2, true),
            'is_internal' => $this->faker->boolean(10),
            'attachments' => $this->faker->optional()->passthrough(json_encode([
                ['filename' => 'screenshot.png', 'path' => 'attachments/screenshot.png'],
            ])),
        ];
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_internal' => true,
        ]);
    }
}
