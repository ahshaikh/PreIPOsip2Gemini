<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DisclosureModuleCategorySeeder extends Seeder
{
    /**
     * Categorize disclosure modules based on their code/name
     */
    public function run(): void
    {
        // Define category mappings based on module codes and names
        $categoryMappings = [
            'governance' => [
                'board_structure',
                'board_composition',
                'governance',
                'policies',
                'directors',
                'management_team',
                'organizational_structure',
                'corporate_governance',
            ],
            'financial' => [
                'financials',
                'financial_statements',
                'funding',
                'revenue',
                'projections',
                'valuation',
                'capital_structure',
                'use_of_funds',
                'financial_performance',
            ],
            'legal' => [
                'legal',
                'risk',
                'compliance',
                'regulatory',
                'litigation',
                'material_contracts',
                'intellectual_property',
                'risk_factors',
            ],
            'operational' => [
                'business_model',
                'operations',
                'operational',
                'products',
                'services',
                'market',
                'customers',
                'technology',
                'infrastructure',
            ],
        ];

        // Get all modules
        $modules = DB::table('disclosure_modules')->get();

        foreach ($modules as $module) {
            $category = $this->determineCategory($module->code, $module->name, $categoryMappings);

            DB::table('disclosure_modules')
                ->where('id', $module->id)
                ->update(['category' => $category]);

            $this->command->info("Set category '{$category}' for module: {$module->name} ({$module->code})");
        }
    }

    /**
     * Determine category based on code and name
     */
    private function determineCategory(string $code, string $name, array $mappings): string
    {
        $searchText = strtolower($code . ' ' . $name);

        foreach ($mappings as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($searchText, $keyword)) {
                    return $category;
                }
            }
        }

        // Default to operational
        return 'operational';
    }
}
