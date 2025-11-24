<?php
// V-FACTORY (Created for comprehensive test coverage)

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
            'user_id' => User::factory(),
            'subject' => $this->faker->sentence(),
            'description' => $this->faker->paragraphs(2, true),
            'category' => $this->faker->randomElement(['payment', 'kyc', 'bonus', 'withdrawal', 'technical', 'other']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => 'open',
        ];
    }

    /**
     * Ticket is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'in_progress',
            'assigned_to' => User::factory(),
        ]);
    }

    /**
     * Ticket is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Ticket is closed.
     */
    public function closed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * High priority ticket.
     */
    public function urgent(): static
    {
        return $this->state(fn(array $attributes) => [
            'priority' => 'urgent',
        ]);
    }

    /**
     * Payment related ticket.
     */
    public function paymentIssue(): static
    {
        return $this->state(fn(array $attributes) => [
            'category' => 'payment',
            'subject' => 'Payment not reflecting in account',
        ]);
    }

    /**
     * KYC related ticket.
     */
    public function kycIssue(): static
    {
        return $this->state(fn(array $attributes) => [
            'category' => 'kyc',
            'subject' => 'KYC verification pending',
        ]);
    }
}
