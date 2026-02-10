<?php

namespace Database\Seeders;

use App\Models\DisclosureModule;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * PHASE 1 & 2 - Disclosure Module Seeder
 *
 * Seeds default SEBI-mandated disclosure modules for Pre-IPO companies.
 * These modules define the structure and validation rules for company disclosures.
 *
 * SEBI Reference: SEBI (ICDR) Regulations, 2018
 * Sections: 26(1), 32, 33 - Disclosure requirements for unlisted companies
 *
 * PHASE 2 ADDITION: Tiered Approval System
 * - Tier 1 (Visibility): Basic business information modules
 * - Tier 2 (Investable): Financial data modules (ENABLES BUYING)
 * - Tier 3 (Full Disclosure): Legal and compliance modules
 *
 * FRESHNESS CONFIGURATION (Coverage + Freshness Model):
 * - document_type: 'update_required' | 'version_controlled'
 * - expected_update_days: Cadence for update_required docs (triggers aging/stale)
 * - stability_window_days: Window for version_controlled docs
 * - max_changes_per_window: Instability threshold for version_controlled
 * - category: Pillar assignment (governance|financial|legal|operational)
 *
 * NO DEFAULTS THAT IMPLY "CURRENT" - every module must declare its freshness policy.
 */
class DisclosureModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create system admin user for created_by
        $admin = User::where('email', 'admin@preiposip.com')->first()
            ?? User::factory()->create(['email' => 'admin@preiposip.com', 'name' => 'System Admin']);

        $modules = [
            [
                'code' => 'business_model',
                'name' => 'Business Model & Operations',
                'description' => 'Comprehensive description of the company\'s business model, operations, products/services, and competitive positioning.',
                'help_text' => 'Provide detailed information about your business model, revenue streams, customer segments, key partners, and operational structure. This helps investors understand how your company creates, delivers, and captures value.',
                'is_required' => true,
                'is_active' => true,
                'display_order' => 1,
                'tier' => 1, // PHASE 2: Tier 1 (Visibility) - Basic business information
                'category' => 'operational', // Pillar: Operational (business operations)
                // FRESHNESS CONFIG: Version-controlled document (rarely changes)
                'document_type' => 'version_controlled',
                'stability_window_days' => 365, // Expected stable for 1 year
                'max_changes_per_window' => 2,  // More than 2 changes = unstable
                'expected_update_days' => null, // Not applicable for version_controlled
                'freshness_weight' => 1.00,
                'icon' => 'building',
                'color' => 'blue',
                'json_schema' => [
                    '$schema' => 'http://json-schema.org/draft-07/schema#',
                    'type' => 'object',
                    'required' => ['business_description', 'revenue_streams', 'customer_segments', 'competitive_advantages'],
                    'properties' => [
                        'business_description' => [
                            'type' => 'string',
                            'minLength' => 500,
                            'maxLength' => 5000,
                            'description' => 'Detailed description of business model and operations',
                        ],
                        'revenue_streams' => [
                            'type' => 'array',
                            'minItems' => 1,
                            'items' => [
                                'type' => 'object',
                                'required' => ['name', 'percentage', 'description'],
                                'properties' => [
                                    'name' => ['type' => 'string'],
                                    'percentage' => ['type' => 'number', 'minimum' => 0, 'maximum' => 100],
                                    'description' => ['type' => 'string', 'minLength' => 50],
                                ],
                            ],
                        ],
                        'customer_segments' => [
                            'type' => 'array',
                            'minItems' => 1,
                            'items' => ['type' => 'string'],
                        ],
                        'competitive_advantages' => [
                            'type' => 'array',
                            'minItems' => 2,
                            'items' => ['type' => 'string', 'minLength' => 50],
                        ],
                        'key_partners' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                        'market_size' => [
                            'type' => 'object',
                            'properties' => [
                                'tam' => ['type' => 'number', 'description' => 'Total Addressable Market in USD'],
                                'sam' => ['type' => 'number', 'description' => 'Serviceable Addressable Market in USD'],
                                'som' => ['type' => 'number', 'description' => 'Serviceable Obtainable Market in USD'],
                            ],
                        ],
                    ],
                ],
                'default_data' => null,
                'sebi_category' => 'Business Information',
                'regulatory_references' => [
                    ['regulation' => 'SEBI (ICDR) Regulations, 2018', 'section' => '26(1)', 'description' => 'Nature of business'],
                ],
                'requires_admin_approval' => true,
                'min_approval_reviews' => 1,
                'approval_checklist' => [
                    'Verify business description is comprehensive and clear',
                    'Check revenue stream percentages total 100%',
                    'Validate competitive advantages are substantiated',
                    'Confirm market size estimates are reasonable',
                ],
                'created_by' => $admin->id,
            ],

            [
                'code' => 'financial_performance',
                'name' => 'Financial Performance',
                'description' => 'Historical and current financial performance including revenue, profitability, cash flows, and key financial metrics.',
                'help_text' => 'Provide audited financial statements for the last 3 years. Include revenue trends, profitability metrics, cash flow statements, and key financial ratios. Be prepared to explain any significant changes.',
                'is_required' => true,
                'is_active' => true,
                'display_order' => 2,
                'tier' => 2, // PHASE 2: Tier 2 (Investable) - Financial data (ENABLES BUYING)
                'category' => 'financial', // Pillar: Financial
                // FRESHNESS CONFIG: Update-required document (quarterly updates expected)
                'document_type' => 'update_required',
                'expected_update_days' => 90, // Quarterly update cadence
                'stability_window_days' => null, // Not applicable for update_required
                'max_changes_per_window' => null,
                'freshness_weight' => 1.00,
                'icon' => 'chart-line',
                'color' => 'green',
                'json_schema' => [
                    '$schema' => 'http://json-schema.org/draft-07/schema#',
                    'type' => 'object',
                    'required' => ['fiscal_year', 'revenue', 'expenses', 'net_profit', 'cash_flow'],
                    'properties' => [
                        'fiscal_year' => ['type' => 'string', 'pattern' => '^[0-9]{4}-[0-9]{4}$'],
                        'revenue' => [
                            'type' => 'object',
                            'required' => ['total', 'breakdown'],
                            'properties' => [
                                'total' => ['type' => 'number', 'minimum' => 0],
                                'breakdown' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'quarter' => ['type' => 'string'],
                                            'amount' => ['type' => 'number'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'expenses' => [
                            'type' => 'object',
                            'required' => ['total', 'operating', 'non_operating'],
                            'properties' => [
                                'total' => ['type' => 'number'],
                                'operating' => ['type' => 'number'],
                                'non_operating' => ['type' => 'number'],
                            ],
                        ],
                        'net_profit' => ['type' => 'number'],
                        'ebitda' => ['type' => 'number'],
                        'cash_flow' => [
                            'type' => 'object',
                            'properties' => [
                                'operating' => ['type' => 'number'],
                                'investing' => ['type' => 'number'],
                                'financing' => ['type' => 'number'],
                            ],
                        ],
                        'key_metrics' => [
                            'type' => 'object',
                            'properties' => [
                                'gross_margin' => ['type' => 'number'],
                                'net_margin' => ['type' => 'number'],
                                'roe' => ['type' => 'number', 'description' => 'Return on Equity'],
                                'roa' => ['type' => 'number', 'description' => 'Return on Assets'],
                            ],
                        ],
                    ],
                ],
                'default_data' => null,
                'sebi_category' => 'Financial Data',
                'regulatory_references' => [
                    ['regulation' => 'SEBI (ICDR) Regulations, 2018', 'section' => '32', 'description' => 'Financial information'],
                ],
                'requires_admin_approval' => true,
                'min_approval_reviews' => 1,
                'approval_checklist' => [
                    'Verify financial statements are audited',
                    'Check quarter breakdown matches annual total',
                    'Validate profit/loss calculations',
                    'Confirm cash flow statement balances',
                ],
                'created_by' => $admin->id,
            ],

            [
                'code' => 'risk_factors',
                'name' => 'Risk Factors',
                'description' => 'Comprehensive disclosure of material risks that could impact the company\'s business, financial condition, or future prospects.',
                'help_text' => 'Identify and describe all material risks including business risks, financial risks, regulatory risks, market risks, and operational risks. Be honest and comprehensive - this protects both you and investors.',
                'is_required' => true,
                'is_active' => true,
                'display_order' => 3,
                'tier' => 3, // PHASE 2: Tier 3 (Full Disclosure) - Risk factors
                'category' => 'legal', // Pillar: Legal & Risk
                // FRESHNESS CONFIG: Version-controlled (changes with material events)
                'document_type' => 'version_controlled',
                'stability_window_days' => 180, // Review every 6 months
                'max_changes_per_window' => 3,  // Risk updates can be more frequent
                'expected_update_days' => null,
                'freshness_weight' => 1.00,
                'icon' => 'shield',
                'color' => 'red',
                'json_schema' => [
                    '$schema' => 'http://json-schema.org/draft-07/schema#',
                    'type' => 'object',
                    'required' => ['business_risks', 'financial_risks', 'regulatory_risks'],
                    'properties' => [
                        'business_risks' => [
                            'type' => 'array',
                            'minItems' => 3,
                            'items' => [
                                'type' => 'object',
                                'required' => ['title', 'description', 'severity', 'mitigation'],
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'description' => ['type' => 'string', 'minLength' => 100],
                                    'severity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'critical']],
                                    'mitigation' => ['type' => 'string', 'minLength' => 50],
                                    'likelihood' => ['type' => 'string', 'enum' => ['unlikely', 'possible', 'likely', 'certain']],
                                ],
                            ],
                        ],
                        'financial_risks' => [
                            'type' => 'array',
                            'minItems' => 2,
                            'items' => [
                                'type' => 'object',
                                'required' => ['title', 'description', 'severity'],
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'description' => ['type' => 'string', 'minLength' => 100],
                                    'severity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'critical']],
                                ],
                            ],
                        ],
                        'regulatory_risks' => [
                            'type' => 'array',
                            'minItems' => 1,
                            'items' => [
                                'type' => 'object',
                                'required' => ['title', 'description'],
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'description' => ['type' => 'string', 'minLength' => 100],
                                ],
                            ],
                        ],
                        'market_risks' => ['type' => 'array'],
                        'operational_risks' => ['type' => 'array'],
                    ],
                ],
                'default_data' => null,
                'sebi_category' => 'Risk Factors',
                'regulatory_references' => [
                    ['regulation' => 'SEBI (ICDR) Regulations, 2018', 'section' => '33', 'description' => 'Risk factors disclosure'],
                ],
                'requires_admin_approval' => true,
                'min_approval_reviews' => 1,
                'approval_checklist' => [
                    'Verify all material risks are disclosed',
                    'Check risk severity assessments are reasonable',
                    'Validate mitigation strategies are provided',
                    'Confirm no generic/boilerplate risk disclosures',
                ],
                'created_by' => $admin->id,
            ],

            [
                'code' => 'board_management',
                'name' => 'Board & Management',
                'description' => 'Information about board of directors, key management personnel, their backgrounds, and governance structure.',
                'help_text' => 'Provide details about your board composition, director qualifications, management team backgrounds, and corporate governance practices. Include any conflicts of interest or related party relationships.',
                'is_required' => true,
                'is_active' => true,
                'display_order' => 4,
                'tier' => 1, // PHASE 2: Tier 1 (Visibility) - Governance information
                'category' => 'governance', // Pillar: Governance
                // FRESHNESS CONFIG: Version-controlled (rarely changes)
                'document_type' => 'version_controlled',
                'stability_window_days' => 365, // Annual review expected
                'max_changes_per_window' => 2,  // Board changes should be infrequent
                'expected_update_days' => null,
                'freshness_weight' => 1.00,
                'icon' => 'users',
                'color' => 'purple',
                'json_schema' => [
                    '$schema' => 'http://json-schema.org/draft-07/schema#',
                    'type' => 'object',
                    'required' => ['board_members', 'key_management', 'governance_practices'],
                    'properties' => [
                        'board_members' => [
                            'type' => 'array',
                            'minItems' => 3,
                            'items' => [
                                'type' => 'object',
                                'required' => ['name', 'designation', 'qualification', 'experience'],
                                'properties' => [
                                    'name' => ['type' => 'string'],
                                    'designation' => ['type' => 'string', 'enum' => ['Chairperson', 'Managing Director', 'Independent Director', 'Non-Executive Director']],
                                    'qualification' => ['type' => 'string'],
                                    'experience' => ['type' => 'string', 'minLength' => 100],
                                    'other_directorships' => ['type' => 'array'],
                                ],
                            ],
                        ],
                        'key_management' => [
                            'type' => 'array',
                            'minItems' => 3,
                            'items' => [
                                'type' => 'object',
                                'required' => ['name', 'designation', 'background'],
                                'properties' => [
                                    'name' => ['type' => 'string'],
                                    'designation' => ['type' => 'string'],
                                    'background' => ['type' => 'string', 'minLength' => 100],
                                ],
                            ],
                        ],
                        'governance_practices' => [
                            'type' => 'object',
                            'properties' => [
                                'board_meetings_per_year' => ['type' => 'number', 'minimum' => 4],
                                'audit_committee_exists' => ['type' => 'boolean'],
                                'nomination_committee_exists' => ['type' => 'boolean'],
                                'remuneration_policy' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
                'default_data' => null,
                'sebi_category' => 'Corporate Governance',
                'regulatory_references' => [
                    ['regulation' => 'SEBI (ICDR) Regulations, 2018', 'section' => '26(1)(c)', 'description' => 'Management and board details'],
                ],
                'requires_admin_approval' => true,
                'min_approval_reviews' => 1,
                'approval_checklist' => [
                    'Verify board has minimum required independent directors',
                    'Check director qualifications are appropriate',
                    'Validate governance practices meet standards',
                    'Confirm no undisclosed conflicts of interest',
                ],
                'created_by' => $admin->id,
            ],

            [
                'code' => 'legal_compliance',
                'name' => 'Legal & Compliance',
                'description' => 'Legal structure, compliance status, ongoing litigation, regulatory approvals, and intellectual property.',
                'help_text' => 'Disclose all material legal matters including pending litigation, regulatory investigations, compliance violations, intellectual property ownership, and material contracts. Full transparency is required.',
                'is_required' => false,
                'is_active' => true,
                'display_order' => 5,
                'tier' => 3, // PHASE 2: Tier 3 (Full Disclosure) - Legal and compliance
                'category' => 'legal', // Pillar: Legal & Risk
                // FRESHNESS CONFIG: Version-controlled (rarely changes unless litigation)
                'document_type' => 'version_controlled',
                'stability_window_days' => 365, // Annual review expected
                'max_changes_per_window' => 2,  // Frequent legal changes = instability
                'expected_update_days' => null,
                'freshness_weight' => 1.00,
                'icon' => 'file-text',
                'color' => 'gray',
                'json_schema' => [
                    '$schema' => 'http://json-schema.org/draft-07/schema#',
                    'type' => 'object',
                    'properties' => [
                        'legal_structure' => ['type' => 'string'],
                        'pending_litigation' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'case_number' => ['type' => 'string'],
                                    'description' => ['type' => 'string'],
                                    'status' => ['type' => 'string'],
                                    'potential_liability' => ['type' => 'number'],
                                ],
                            ],
                        ],
                        'intellectual_property' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'type' => ['type' => 'string', 'enum' => ['patent', 'trademark', 'copyright', 'trade_secret']],
                                    'description' => ['type' => 'string'],
                                    'status' => ['type' => 'string'],
                                ],
                            ],
                        ],
                        'regulatory_approvals' => ['type' => 'array'],
                        'material_contracts' => ['type' => 'array'],
                    ],
                ],
                'default_data' => null,
                'sebi_category' => 'Legal Information',
                'regulatory_references' => [
                    ['regulation' => 'SEBI (ICDR) Regulations, 2018', 'section' => '26(1)(f)', 'description' => 'Legal and regulatory information'],
                ],
                'requires_admin_approval' => true,
                'min_approval_reviews' => 1,
                'approval_checklist' => [
                    'Verify all material litigation is disclosed',
                    'Check IP ownership is clear and documented',
                    'Validate regulatory compliance status',
                    'Confirm material contracts are disclosed',
                ],
                'created_by' => $admin->id,
            ],
        ];

        foreach ($modules as $moduleData) {
            DisclosureModule::updateOrCreate(
                ['code' => $moduleData['code']],
                $moduleData
            );
        }

        $this->command->info('Successfully seeded ' . count($modules) . ' disclosure modules.');
    }
}
