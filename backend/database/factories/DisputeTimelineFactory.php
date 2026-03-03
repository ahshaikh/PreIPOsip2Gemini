<?php

namespace Database\Factories;

use App\Models\Dispute;
use App\Models\DisputeTimeline;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DisputeTimelineFactory extends Factory
{
    protected $model = DisputeTimeline::class;

    public function definition(): array
    {
        return [
            'dispute_id' => Dispute::factory(),
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_user_id' => User::factory(),
            'actor_role' => DisputeTimeline::ROLE_INVESTOR,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'old_status' => null,
            'new_status' => null,
            'attachments' => null,
            'metadata' => null,
            'visible_to_investor' => true,
            'is_internal_note' => false,
        ];
    }

    /**
     * Status change event.
     */
    public function statusChange(string $from, string $to): static
    {
        return $this->state(fn(array $attributes) => [
            'event_type' => DisputeTimeline::EVENT_STATUS_CHANGE,
            'title' => "Status changed to {$to}",
            'old_status' => $from,
            'new_status' => $to,
        ]);
    }

    /**
     * Admin comment.
     */
    public function adminComment(User $admin = null): static
    {
        return $this->state(fn(array $attributes) => [
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_user_id' => $admin?->id ?? User::factory(),
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
        ]);
    }

    /**
     * Internal note (admin only).
     */
    public function internalNote(User $admin = null): static
    {
        return $this->state(fn(array $attributes) => [
            'event_type' => DisputeTimeline::EVENT_COMMENT,
            'actor_user_id' => $admin?->id ?? User::factory(),
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'is_internal_note' => true,
            'visible_to_investor' => false,
        ]);
    }

    /**
     * System-generated event.
     */
    public function systemGenerated(): static
    {
        return $this->state(fn(array $attributes) => [
            'actor_user_id' => null,
            'actor_role' => DisputeTimeline::ROLE_SYSTEM,
        ]);
    }

    /**
     * Evidence added event.
     */
    public function evidenceAdded(): static
    {
        return $this->state(fn(array $attributes) => [
            'event_type' => DisputeTimeline::EVENT_EVIDENCE_ADDED,
            'title' => 'Evidence added',
            'attachments' => ['file1.pdf', 'screenshot.png'],
        ]);
    }

    /**
     * Settlement event.
     */
    public function settlement(string $action, int $amountPaise = null): static
    {
        return $this->state(fn(array $attributes) => [
            'event_type' => DisputeTimeline::EVENT_SETTLEMENT,
            'title' => 'Settlement processed',
            'actor_role' => DisputeTimeline::ROLE_ADMIN,
            'metadata' => [
                'action' => $action,
                'amount_paise' => $amountPaise,
            ],
        ]);
    }

    /**
     * Escalation event.
     */
    public function escalation(): static
    {
        return $this->state(fn(array $attributes) => [
            'event_type' => DisputeTimeline::EVENT_ESCALATED,
            'title' => 'Dispute escalated',
        ]);
    }

    /**
     * Created event (dispute filing).
     */
    public function created(): static
    {
        return $this->state(fn(array $attributes) => [
            'event_type' => DisputeTimeline::EVENT_CREATED,
            'title' => 'Dispute filed',
        ]);
    }
}
