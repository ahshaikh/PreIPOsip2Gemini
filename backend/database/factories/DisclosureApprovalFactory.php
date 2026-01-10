<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureApproval;
use App\Models\DisclosureModule;
use App\Models\DisclosureVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DisclosureApproval>
 */
class DisclosureApprovalFactory extends Factory
{
    protected $model = DisclosureApproval::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $requestedAt = now()->subDays($this->faker->numberBetween(1, 10));
        $slaDueDate = $requestedAt->copy()->addBusinessDays(5);

        return [
            'company_disclosure_id' => CompanyDisclosure::factory(),
            'company_id' => Company::factory(),
            'disclosure_module_id' => DisclosureModule::factory(),
            'request_type' => $this->faker->randomElement(['initial_submission', 'resubmission', 'revision', 'correction']),
            'requested_by' => User::factory(),
            'requested_at' => $requestedAt,
            'submission_notes' => $this->faker->sentence(10),
            'disclosure_version_number' => 1,
            'disclosure_version_id' => null,
            'status' => 'pending',
            'reviewed_by' => null,
            'review_started_at' => null,
            'review_completed_at' => null,
            'review_duration_minutes' => null,
            'decision_notes' => null,
            'checklist_completed' => null,
            'identified_issues' => null,
            'clarifications_requested' => 0,
            'clarifications_due_date' => null,
            'all_clarifications_answered' => false,
            'approval_conditions' => null,
            'conditional_approval_expires_at' => null,
            'is_revoked' => false,
            'revoked_by' => null,
            'revoked_at' => null,
            'revocation_reason' => null,
            'investor_notification_required' => false,
            'sla_due_date' => $slaDueDate,
            'sla_breached' => false,
            'business_days_to_review' => null,
            'sebi_compliance_status' => null,
            'approval_stage' => 1,
            'approval_chain' => null,
            'internal_notes' => null,
            'reminder_count' => 0,
            'last_reminder_at' => null,
            'requested_by_ip' => $this->faker->ipv4(),
            'reviewed_by_ip' => null,
            'requested_by_user_agent' => $this->faker->userAgent(),
        ];
    }

    /**
     * Create pending approval
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Create under review approval
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_review',
            'reviewed_by' => User::factory(),
            'review_started_at' => now()->subHours($this->faker->numberBetween(1, 24)),
        ]);
    }

    /**
     * Create approved approval
     */
    public function approved(): static
    {
        $reviewStarted = now()->subDays(5);
        $reviewCompleted = $reviewStarted->copy()->addHours($this->faker->numberBetween(1, 48));
        $businessDays = $this->calculateBusinessDays(now()->subDays(7), $reviewCompleted);

        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_by' => User::factory(),
            'review_started_at' => $reviewStarted,
            'review_completed_at' => $reviewCompleted,
            'review_duration_minutes' => $reviewStarted->diffInMinutes($reviewCompleted),
            'disclosure_version_id' => DisclosureVersion::factory(),
            'decision_notes' => 'All requirements met. Disclosure approved.',
            'checklist_completed' => [
                ['item' => 'Verify completeness', 'checked' => true, 'notes' => 'Complete'],
                ['item' => 'Check consistency', 'checked' => true, 'notes' => 'Consistent'],
                ['item' => 'Validate documents', 'checked' => true, 'notes' => 'Valid'],
            ],
            'business_days_to_review' => $businessDays,
            'sla_breached' => false,
            'sebi_compliance_status' => 'compliant',
            'reviewed_by_ip' => $this->faker->ipv4(),
        ]);
    }

    /**
     * Create rejected approval
     */
    public function rejected(): static
    {
        $reviewStarted = now()->subDays(4);
        $reviewCompleted = $reviewStarted->copy()->addHours($this->faker->numberBetween(1, 24));

        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewed_by' => User::factory(),
            'review_started_at' => $reviewStarted,
            'review_completed_at' => $reviewCompleted,
            'review_duration_minutes' => $reviewStarted->diffInMinutes($reviewCompleted),
            'decision_notes' => 'Disclosure contains insufficient information. Please revise.',
            'identified_issues' => [
                ['field' => 'revenue_streams', 'issue' => 'Missing Q4 data', 'severity' => 'high'],
                ['field' => 'risk_factors', 'issue' => 'Incomplete disclosure', 'severity' => 'medium'],
            ],
            'business_days_to_review' => $this->calculateBusinessDays(now()->subDays(6), $reviewCompleted),
            'sebi_compliance_status' => 'non_compliant',
            'reviewed_by_ip' => $this->faker->ipv4(),
        ]);
    }

    /**
     * Create approval requiring clarification
     */
    public function needsClarification(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'clarification_required',
            'reviewed_by' => User::factory(),
            'review_started_at' => now()->subDays(2),
            'clarifications_requested' => $this->faker->numberBetween(2, 5),
            'clarifications_due_date' => now()->addDays(7),
            'all_clarifications_answered' => false,
        ]);
    }

    /**
     * Create SLA breached approval
     */
    public function slaBreached(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'requested_at' => now()->subDays(10),
            'sla_due_date' => now()->subDays(3),
            'sla_breached' => true,
            'sebi_compliance_status' => 'delayed',
        ]);
    }

    /**
     * Create revoked approval
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'disclosure_version_id' => DisclosureVersion::factory(),
            'is_revoked' => true,
            'revoked_by' => User::factory(),
            'revoked_at' => now()->subDays(1),
            'revocation_reason' => 'Critical error discovered in financial data after approval.',
            'investor_notification_required' => true,
        ]);
    }

    /**
     * Create initial submission
     */
    public function initialSubmission(): static
    {
        return $this->state(fn (array $attributes) => [
            'request_type' => 'initial_submission',
            'disclosure_version_number' => 1,
        ]);
    }

    /**
     * Create resubmission
     */
    public function resubmission(): static
    {
        return $this->state(fn (array $attributes) => [
            'request_type' => 'resubmission',
            'disclosure_version_number' => $this->faker->numberBetween(2, 5),
        ]);
    }

    /**
     * Create conditional approval
     */
    public function conditional(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'disclosure_version_id' => DisclosureVersion::factory(),
            'approval_conditions' => [
                ['condition' => 'Must update quarterly', 'due' => now()->addMonths(3)->toDateString()],
                ['condition' => 'Must file SEBI update', 'due' => now()->addMonths(6)->toDateString()],
            ],
            'conditional_approval_expires_at' => now()->addMonths(6),
        ]);
    }

    /**
     * Create multi-stage approval
     */
    public function multiStage(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_stage' => 2,
            'approval_chain' => [
                [
                    'stage' => 1,
                    'role' => 'reviewer',
                    'approved_by' => $this->faker->numberBetween(1, 100),
                    'at' => now()->subDays(3)->toIso8601String(),
                ],
                [
                    'stage' => 2,
                    'role' => 'approver',
                    'approved_by' => null,
                    'at' => null,
                ],
            ],
        ]);
    }

    /**
     * Create approval with reminders
     */
    public function withReminders(int $count = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'reminder_count' => $count,
            'last_reminder_at' => now()->subDays(1),
        ]);
    }

    /**
     * Helper to calculate business days
     */
    protected function calculateBusinessDays($start, $end): int
    {
        $businessDays = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $businessDays++;
            }
            $current->addDay();
        }

        return $businessDays;
    }
}
