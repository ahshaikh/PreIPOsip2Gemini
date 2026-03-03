<?php

namespace Database\Factories;

use App\Enums\DisputeStatus;
use App\Enums\DisputeType;
use App\Models\Dispute;
use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class DisputeFactory extends Factory
{
    protected $model = Dispute::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(DisputeType::cases());
        $status = $this->faker->randomElement(DisputeStatus::cases());

        return [
            'user_id' => User::factory(),
            'raised_by_user_id' => fn(array $attributes) => $attributes['user_id'],
            'company_id' => Company::factory(),
            'type' => $type->value,
            'status' => $status->value,
            'severity' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'category' => $this->faker->randomElement(Dispute::getCategories()),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'evidence' => null,
            'risk_score' => $type->riskScore(),
            'opened_at' => now(),
            'sla_deadline_at' => now()->addHours($type->slaHours()),
            'escalation_deadline_at' => now()->addHours($type->autoEscalationHours()),
            'blocks_investment' => $type->riskScore() >= 3,
        ];
    }

    /**
     * Open status dispute.
     */
    public function open(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => DisputeStatus::OPEN->value,
        ]);
    }

    /**
     * Under review status dispute.
     */
    public function underReview(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => DisputeStatus::UNDER_REVIEW->value,
            'investigation_started_at' => now(),
        ]);
    }

    /**
     * Awaiting investor response status.
     */
    public function awaitingInvestor(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => DisputeStatus::AWAITING_INVESTOR->value,
            'investigation_started_at' => now()->subDay(),
        ]);
    }

    /**
     * Escalated status dispute.
     */
    public function escalated(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => DisputeStatus::ESCALATED->value,
            'escalated_at' => now(),
            'risk_score' => 4,
        ]);
    }

    /**
     * Resolved approved status.
     */
    public function resolvedApproved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => DisputeStatus::RESOLVED_APPROVED->value,
            'resolved_at' => now(),
            'resolution' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Resolved rejected status.
     */
    public function resolvedRejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => DisputeStatus::RESOLVED_REJECTED->value,
            'resolved_at' => now(),
            'resolution' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Closed status dispute.
     */
    public function closed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => DisputeStatus::CLOSED->value,
            'closed_at' => now(),
        ]);
    }

    /**
     * Payment type dispute.
     */
    public function paymentType(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => DisputeType::B_PAYMENT->value,
            'category' => Dispute::CATEGORY_FUND_TRANSFER,
        ]);
    }

    /**
     * Allocation type dispute.
     */
    public function allocationType(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => DisputeType::C_ALLOCATION->value,
            'category' => Dispute::CATEGORY_INVESTMENT_PROCESSING,
            'risk_score' => 3,
        ]);
    }

    /**
     * Fraud type dispute.
     */
    public function fraudType(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => DisputeType::D_FRAUD->value,
            'severity' => 'critical',
            'risk_score' => 4,
            'blocks_investment' => true,
        ]);
    }

    /**
     * With assigned admin.
     */
    public function assignedTo(User $admin): static
    {
        return $this->state(fn(array $attributes) => [
            'assigned_to_admin_id' => $admin->id,
        ]);
    }
}
