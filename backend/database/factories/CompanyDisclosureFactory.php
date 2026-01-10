<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureModule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanyDisclosure>
 */
class CompanyDisclosureFactory extends Factory
{
    protected $model = CompanyDisclosure::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'disclosure_module_id' => DisclosureModule::factory(),
            'disclosure_data' => $this->generateSampleDisclosureData(),
            'attachments' => null,
            'status' => 'draft',
            'completion_percentage' => $this->faker->numberBetween(0, 100),
            'is_locked' => false,
            'submitted_at' => null,
            'submitted_by' => null,
            'approved_at' => null,
            'approved_by' => null,
            'rejection_reason' => null,
            'rejected_at' => null,
            'rejected_by' => null,
            'version_number' => 1,
            'current_version_id' => null,
            'last_modified_at' => now(),
            'last_modified_by' => User::factory(),
            'last_modified_ip' => $this->faker->ipv4(),
            'last_modified_user_agent' => $this->faker->userAgent(),
            'internal_notes' => null,
        ];
    }

    /**
     * Generate sample disclosure data
     */
    protected function generateSampleDisclosureData(): array
    {
        return [
            'description' => $this->faker->paragraph(5),
            'items' => [
                ['name' => 'Revenue Model', 'value' => $this->faker->sentence()],
                ['name' => 'Target Market', 'value' => $this->faker->sentence()],
                ['name' => 'Key Metrics', 'value' => $this->faker->sentence()],
            ],
        ];
    }

    /**
     * Create a draft disclosure
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'completion_percentage' => $this->faker->numberBetween(10, 90),
            'is_locked' => false,
        ]);
    }

    /**
     * Create a submitted disclosure
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'completion_percentage' => 100,
            'submitted_at' => now()->subDays($this->faker->numberBetween(1, 7)),
            'submitted_by' => User::factory(),
        ]);
    }

    /**
     * Create an approved disclosure
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'completion_percentage' => 100,
            'is_locked' => true,
            'submitted_at' => now()->subDays(10),
            'submitted_by' => User::factory(),
            'approved_at' => now()->subDays(3),
            'approved_by' => User::factory(),
        ]);
    }

    /**
     * Create a rejected disclosure
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'completion_percentage' => 100,
            'submitted_at' => now()->subDays(10),
            'submitted_by' => User::factory(),
            'rejected_at' => now()->subDays(3),
            'rejected_by' => User::factory(),
            'rejection_reason' => $this->faker->sentence(10),
        ]);
    }

    /**
     * Create disclosure requiring clarification
     */
    public function needsClarification(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'clarification_required',
            'completion_percentage' => 100,
            'submitted_at' => now()->subDays(10),
            'submitted_by' => User::factory(),
        ]);
    }

    /**
     * Create disclosure under review
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_review',
            'completion_percentage' => 100,
            'submitted_at' => now()->subDays(5),
            'submitted_by' => User::factory(),
        ]);
    }

    /**
     * Create disclosure with attachments
     */
    public function withAttachments(): static
    {
        return $this->state(fn (array $attributes) => [
            'attachments' => [
                [
                    'file_path' => 'disclosures/doc1.pdf',
                    'file_name' => 'Business Plan.pdf',
                    'file_size' => 1024576,
                    'uploaded_at' => now()->subDays(2)->toIso8601String(),
                ],
                [
                    'file_path' => 'disclosures/doc2.pdf',
                    'file_name' => 'Financial Statements.pdf',
                    'file_size' => 2048576,
                    'uploaded_at' => now()->subDays(1)->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * Create locked disclosure
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_locked' => true,
        ]);
    }

    /**
     * Create incomplete disclosure
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'completion_percentage' => $this->faker->numberBetween(10, 70),
            'disclosure_data' => ['description' => $this->faker->sentence()],
        ]);
    }
}
