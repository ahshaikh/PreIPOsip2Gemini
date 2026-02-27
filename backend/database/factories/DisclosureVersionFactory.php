<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureModule;
use App\Models\DisclosureVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DisclosureVersion>
 */
class DisclosureVersionFactory extends Factory
{
    protected $model = DisclosureVersion::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $disclosureData = $this->generateVersionData();

        return [
            'company_disclosure_id' => CompanyDisclosure::factory(),
            'company_id' => Company::factory(),
            'disclosure_module_id' => DisclosureModule::factory(),
            'version_number' => 1,
            'version_hash' => hash('sha256', json_encode($disclosureData)),
            'disclosure_data' => $disclosureData,
            'attachments' => null,
            'changes_summary' => null,
            'change_reason' => null,
            'is_locked' => true,
            'locked_at' => now(),
            'approved_at' => now(),
            'approved_by' => User::factory(),
            'approval_notes' => $this->faker->sentence(),
            'was_investor_visible' => false,
            'first_investor_view_at' => null,
            'investor_view_count' => 0,
            'linked_transactions' => null,
            'sebi_filing_reference' => null,
            'sebi_filed_at' => null,
            'certification' => null,
            'created_by_ip' => $this->faker->ipv4(),
            'created_by_user_agent' => $this->faker->userAgent(),
            'created_by_id' => User::factory(),
            'created_by_type' => 'App\\Models\\User',
        ];
    }

    /**
     * Generate version data
     */
    protected function generateVersionData(): array
    {
        return [
            'description' => $this->faker->paragraph(5),
            'items' => [
                ['name' => 'Revenue Model', 'value' => $this->faker->sentence()],
                ['name' => 'Market Size', 'value' => $this->faker->sentence()],
            ],
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Create version 2 or higher
     */
    public function versioned(int $versionNumber = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'version_number' => $versionNumber,
            'changes_summary' => [
                'description' => 'Modified',
                'items' => 'Modified',
            ],
            'change_reason' => $this->faker->sentence(10),
        ]);
    }

    /**
     * Create investor-visible version
     */
    public function investorVisible(): static
    {
        return $this->state(fn (array $attributes) => [
            'was_investor_visible' => true,
            'first_investor_view_at' => now()->subDays($this->faker->numberBetween(1, 30)),
            'investor_view_count' => $this->faker->numberBetween(1, 100),
        ]);
    }

    /**
     * Create version with linked transactions
     */
    public function withTransactions(): static
    {
        return $this->state(fn (array $attributes) => [
            'was_investor_visible' => true,
            'first_investor_view_at' => now()->subDays(20),
            'investor_view_count' => 50,
            'linked_transactions' => [
                [
                    'transaction_id' => $this->faker->numberBetween(1000, 9999),
                    'linked_at' => now()->subDays(15)->toIso8601String(),
                    'amount' => $this->faker->randomFloat(2, 10000, 100000),
                ],
                [
                    'transaction_id' => $this->faker->numberBetween(1000, 9999),
                    'linked_at' => now()->subDays(10)->toIso8601String(),
                    'amount' => $this->faker->randomFloat(2, 10000, 100000),
                ],
            ],
        ]);
    }

    /**
     * Create SEBI-filed version
     */
    public function sebiFiled(): static
    {
        return $this->state(fn (array $attributes) => [
            'sebi_filing_reference' => 'SEBI/ICDR/2024/' . $this->faker->numberBetween(1000, 9999),
            'sebi_filed_at' => now()->subDays($this->faker->numberBetween(1, 90)),
        ]);
    }

    /**
     * Create certified version
     */
    public function certified(): static
    {
        return $this->state(fn (array $attributes) => [
            'certification' => [
                'signed_by' => 'CEO',
                'signer_name' => $this->faker->name(),
                'signature_hash' => hash('sha256', $this->faker->uuid()),
                'timestamp' => now()->toIso8601String(),
                'certificate_url' => 'certificates/cert_' . $this->faker->uuid() . '.pdf',
            ],
        ]);
    }

    /**
     * Create version with attachments
     */
    public function withAttachments(): static
    {
        return $this->state(fn (array $attributes) => [
            'attachments' => [
                [
                    'file_path' => 'versions/doc1.pdf',
                    'file_name' => 'Supporting Document.pdf',
                    'file_size' => 1024576,
                    'uploaded_at' => now()->subDays(2)->toIso8601String(),
                ],
            ],
        ]);
    }
}
