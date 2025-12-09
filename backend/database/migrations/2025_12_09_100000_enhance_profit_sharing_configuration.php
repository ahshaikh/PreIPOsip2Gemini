<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new fields to profit_shares table
        Schema::table('profit_shares', function (Blueprint $table) {
            $table->string('report_visibility')->default('private')->after('status'); // public, private, partners_only
            $table->text('report_url')->nullable()->after('report_visibility'); // URL to published report
            $table->json('calculation_metadata')->nullable()->after('report_url'); // Store formula details, eligible users count, etc.
            $table->foreignId('published_by')->nullable()->constrained('users')->after('calculation_metadata');
            $table->timestamp('published_at')->nullable()->after('published_by');
        });

        // Add settings for Profit Sharing configuration
        $settings = [
            // Global Settings
            ['key' => 'profit_share_frequency', 'value' => 'quarterly', 'type' => 'string', 'group' => 'profit_share_config'],
            ['key' => 'profit_share_auto_calculate', 'value' => 'false', 'type' => 'boolean', 'group' => 'profit_share_config'],
            ['key' => 'profit_share_auto_distribute', 'value' => 'false', 'type' => 'boolean', 'group' => 'profit_share_config'],

            // Eligibility Criteria
            ['key' => 'profit_share_min_months', 'value' => '3', 'type' => 'number', 'group' => 'profit_share_config'],
            ['key' => 'profit_share_min_investment', 'value' => '10000', 'type' => 'number', 'group' => 'profit_share_config'],
            ['key' => 'profit_share_require_active_subscription', 'value' => 'true', 'type' => 'boolean', 'group' => 'profit_share_config'],

            // Formula Configuration
            ['key' => 'profit_share_formula_type', 'value' => 'weighted_investment', 'type' => 'string', 'group' => 'profit_share_config'], // weighted_investment, equal_split, tenure_based
            ['key' => 'profit_share_tenure_weight', 'value' => '0.3', 'type' => 'number', 'group' => 'profit_share_config'], // Weight for tenure in formula (0-1)
            ['key' => 'profit_share_investment_weight', 'value' => '0.7', 'type' => 'number', 'group' => 'profit_share_config'], // Weight for investment amount (0-1)

            // Report Publishing
            ['key' => 'profit_share_default_visibility', 'value' => 'private', 'type' => 'string', 'group' => 'profit_share_config'], // public, private, partners_only
            ['key' => 'profit_share_auto_publish', 'value' => 'false', 'type' => 'boolean', 'group' => 'profit_share_config'],
            ['key' => 'profit_share_show_beneficiary_details', 'value' => 'false', 'type' => 'boolean', 'group' => 'profit_share_config'],

            // TDS and Tax Settings
            ['key' => 'profit_share_tds_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'profit_share_config'],
            ['key' => 'profit_share_tds_rate', 'value' => '0.10', 'type' => 'number', 'group' => 'profit_share_config'],
            ['key' => 'profit_share_tds_threshold', 'value' => '5000', 'type' => 'number', 'group' => 'profit_share_config'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profit_shares', function (Blueprint $table) {
            $table->dropForeign(['published_by']);
            $table->dropColumn([
                'report_visibility',
                'report_url',
                'calculation_metadata',
                'published_by',
                'published_at',
            ]);
        });

        $keys = [
            'profit_share_frequency',
            'profit_share_auto_calculate',
            'profit_share_auto_distribute',
            'profit_share_min_months',
            'profit_share_min_investment',
            'profit_share_require_active_subscription',
            'profit_share_formula_type',
            'profit_share_tenure_weight',
            'profit_share_investment_weight',
            'profit_share_default_visibility',
            'profit_share_auto_publish',
            'profit_share_show_beneficiary_details',
            'profit_share_tds_enabled',
            'profit_share_tds_rate',
            'profit_share_tds_threshold',
        ];

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
