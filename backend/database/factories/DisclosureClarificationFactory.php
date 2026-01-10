<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureClarification;
use App\Models\DisclosureModule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DisclosureClarification>
 */
class DisclosureClarificationFactory extends Factory
{
    protected $model = DisclosureClarification::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'company_disclosure_id' => CompanyDisclosure::factory(),
            'company_id' => Company::factory(),
            'disclosure_module_id' => DisclosureModule::factory(),
            'parent_id' => null,
            'thread_depth' => 0,
            'question_subject' => $this->faker->sentence(5),
            'question_body' => $this->faker->paragraph(3),
            'question_type' => $this->faker->randomElement(['missing_data', 'inconsistency', 'insufficient_detail', 'verification', 'compliance', 'other']),
            'asked_by' => User::factory(),
            'asked_at' => now()->subDays($this->faker->numberBetween(1, 7)),
            'field_path' => $this->faker->randomElement([
                'disclosure_data.revenue_streams[0].percentage',
                'disclosure_data.business_description',
                'disclosure_data.customer_segments',
                null
            ]),
            'highlighted_data' => null,
            'suggested_fix' => null,
            'answer_body' => null,
            'answered_by' => null,
            'answered_at' => null,
            'supporting_documents' => null,
            'status' => 'open',
            'resolution_notes' => null,
            'resolved_by' => null,
            'resolved_at' => null,
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'due_date' => now()->addDays($this->faker->numberBetween(3, 14)),
            'is_blocking' => $this->faker->boolean(30), // 30% blocking
            'internal_notes' => null,
            'is_visible_to_company' => true,
            'reminder_count' => 0,
            'last_reminder_at' => null,
            'asked_by_ip' => $this->faker->ipv4(),
            'answered_by_ip' => null,
        ];
    }

    /**
     * Create an open clarification
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'answer_body' => null,
            'answered_by' => null,
            'answered_at' => null,
        ]);
    }

    /**
     * Create an answered clarification
     */
    public function answered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'answered',
            'answer_body' => $this->faker->paragraph(3),
            'answered_by' => User::factory(),
            'answered_at' => now()->subDays($this->faker->numberBetween(1, 3)),
            'answered_by_ip' => $this->faker->ipv4(),
        ]);
    }

    /**
     * Create an accepted clarification
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'answer_body' => $this->faker->paragraph(3),
            'answered_by' => User::factory(),
            'answered_at' => now()->subDays(5),
            'answered_by_ip' => $this->faker->ipv4(),
            'resolved_by' => User::factory(),
            'resolved_at' => now()->subDays(1),
            'resolution_notes' => 'Answer is satisfactory. Issue resolved.',
        ]);
    }

    /**
     * Create a disputed clarification
     */
    public function disputed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disputed',
            'answer_body' => $this->faker->paragraph(3),
            'answered_by' => User::factory(),
            'answered_at' => now()->subDays(5),
            'answered_by_ip' => $this->faker->ipv4(),
            'resolved_by' => User::factory(),
            'resolved_at' => now()->subDays(1),
            'resolution_notes' => 'Answer is insufficient. Please revise disclosure data.',
        ]);
    }

    /**
     * Create a withdrawn clarification
     */
    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'withdrawn',
            'resolved_by' => User::factory(),
            'resolved_at' => now()->subDays(1),
            'resolution_notes' => 'Question withdrawn - asked by mistake.',
        ]);
    }

    /**
     * Create a blocking clarification
     */
    public function blocking(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocking' => true,
            'priority' => 'critical',
        ]);
    }

    /**
     * Create an overdue clarification
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'due_date' => now()->subDays($this->faker->numberBetween(1, 7)),
        ]);
    }

    /**
     * Create a high priority clarification
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
            'due_date' => now()->addDays(3),
        ]);
    }

    /**
     * Create a critical priority clarification
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'critical',
            'is_blocking' => true,
            'due_date' => now()->addDays(2),
        ]);
    }

    /**
     * Create clarification with field path targeting
     */
    public function withFieldPath(string $path = 'disclosure_data.revenue_streams[0]'): static
    {
        return $this->state(fn (array $attributes) => [
            'field_path' => $path,
            'highlighted_data' => [
                'revenue_streams' => [
                    ['name' => 'Subscriptions', 'percentage' => 120], // Invalid: >100%
                ],
            ],
            'suggested_fix' => [
                'revenue_streams' => [
                    ['name' => 'Subscriptions', 'percentage' => 70],
                ],
            ],
        ]);
    }

    /**
     * Create clarification with supporting documents
     */
    public function withDocuments(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'answered',
            'answer_body' => $this->faker->paragraph(3),
            'answered_by' => User::factory(),
            'answered_at' => now()->subDays(2),
            'supporting_documents' => [
                [
                    'file_path' => 'clarifications/doc1.pdf',
                    'file_name' => 'Bank Statement.pdf',
                    'description' => 'Proof of revenue claim',
                    'uploaded_at' => now()->subDays(2)->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * Create threaded reply
     */
    public function reply(DisclosureClarification $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent ? $parent->id : DisclosureClarification::factory(),
            'thread_depth' => $parent ? $parent->thread_depth + 1 : 1,
            'company_disclosure_id' => $parent ? $parent->company_disclosure_id : $attributes['company_disclosure_id'],
            'company_id' => $parent ? $parent->company_id : $attributes['company_id'],
            'disclosure_module_id' => $parent ? $parent->disclosure_module_id : $attributes['disclosure_module_id'],
        ]);
    }

    /**
     * Create internal note (not visible to company)
     */
    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible_to_company' => false,
            'internal_notes' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Create clarification with reminders sent
     */
    public function withReminders(int $count = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'reminder_count' => $count,
            'last_reminder_at' => now()->subDays(1),
        ]);
    }
}
