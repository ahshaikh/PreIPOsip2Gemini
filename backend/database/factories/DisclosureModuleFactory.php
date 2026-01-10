<?php

namespace Database\Factories;

use App\Models\DisclosureModule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DisclosureModule>
 */
class DisclosureModuleFactory extends Factory
{
    protected $model = DisclosureModule::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->slug(2),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'help_text' => $this->faker->paragraph(),
            'is_required' => $this->faker->boolean(70), // 70% required
            'is_active' => true,
            'display_order' => $this->faker->numberBetween(1, 100),
            'icon' => $this->faker->randomElement(['building', 'chart-line', 'shield', 'users', 'file-text']),
            'color' => $this->faker->randomElement(['blue', 'green', 'orange', 'red', 'purple']),
            'json_schema' => $this->generateBasicSchema(),
            'default_data' => null,
            'sebi_category' => $this->faker->randomElement(['Business Information', 'Financial Data', 'Risk Factors', 'Corporate Governance', null]),
            'regulatory_references' => $this->faker->boolean(50) ? [
                [
                    'regulation' => 'SEBI (ICDR) Regulations, 2018',
                    'section' => $this->faker->randomElement(['26(1)', '32', '33']),
                    'description' => $this->faker->sentence(),
                ]
            ] : null,
            'requires_admin_approval' => true,
            'min_approval_reviews' => 1,
            'approval_checklist' => [
                'Verify completeness of information',
                'Check for inconsistencies',
                'Validate supporting documents',
            ],
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    /**
     * Generate a basic JSON schema for testing
     */
    protected function generateBasicSchema(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'type' => 'object',
            'required' => ['description'],
            'properties' => [
                'description' => [
                    'type' => 'string',
                    'minLength' => 10,
                    'maxLength' => 5000,
                ],
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'value' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create a business model disclosure module
     */
    public function businessModel(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'business_model',
            'name' => 'Business Model & Operations',
            'is_required' => true,
            'display_order' => 1,
            'icon' => 'building',
            'color' => 'blue',
            'sebi_category' => 'Business Information',
        ]);
    }

    /**
     * Create a financial performance disclosure module
     */
    public function financials(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'financials',
            'name' => 'Financial Performance',
            'is_required' => true,
            'display_order' => 2,
            'icon' => 'chart-line',
            'color' => 'green',
            'sebi_category' => 'Financial Data',
        ]);
    }

    /**
     * Create a risk factors disclosure module
     */
    public function risks(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'risks',
            'name' => 'Risk Factors',
            'is_required' => true,
            'display_order' => 3,
            'icon' => 'shield',
            'color' => 'red',
            'sebi_category' => 'Risk Factors',
        ]);
    }

    /**
     * Create an inactive module
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an optional module
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => false,
        ]);
    }
}
