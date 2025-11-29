<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'ticket_code'  => strtoupper($this->faker->bothify('TCKT-#####')),
            'subject'      => $this->faker->sentence(6),
            'category'     => $this->faker->randomElement(['other','billing','technical','account']),
            'priority'     => $this->faker->randomElement(['low','medium','high']),
            'status'       => $this->faker->randomElement(['open','pending','resolved','closed']),
            'sla_hours'    => $this->faker->optional(0.7)->numberBetween(24,168),
            'assigned_to'  => null,
            'resolved_by'  => null,
            'resolved_at'  => null,
            'closed_at'    => null,
            'rating'       => null,
            'rating_feedback' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn() => ['status' => 'open']);
    }

    public function resolved(): static
    {
        return $this->state(fn() => [
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => User::factory(),
        ]);
    }
}
