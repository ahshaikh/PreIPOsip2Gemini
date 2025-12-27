<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Add company_id FK
        Schema::table('deals', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('product_id')
                  ->constrained('companies')->onDelete('restrict');
        });

        // Migrate existing data: match company_name to companies.name
        DB::statement("
            UPDATE deals
            SET company_id = (
                SELECT id FROM companies
                WHERE LOWER(companies.name) = LOWER(deals.company_name)
                LIMIT 1
            )
            WHERE company_name IS NOT NULL
        ");

        // Deals without matching company → keep company_name for manual review
        $orphaned = DB::table('deals')->whereNull('company_id')->count();
        if ($orphaned > 0) {
            echo "\nWARNING: {$orphaned} deals could not be matched to companies. Manual review required.\n";
        }

        // Make company_id required, drop old fields
        Schema::table('deals', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->dropColumn(['company_name', 'company_logo']);
        });
    }

    public function down(): void
    {
        // Restore company_name from company relationship
        Schema::table('deals', function (Blueprint $table) {
            $table->string('company_name')->nullable();
            $table->string('company_logo')->nullable();
        });

        DB::statement("
            UPDATE deals
            INNER JOIN companies ON deals.company_id = companies.id
            SET deals.company_name = companies.name,
                deals.company_logo = companies.logo
        ");

        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
