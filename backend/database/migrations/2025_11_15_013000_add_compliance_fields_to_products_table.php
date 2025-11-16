<?php
// V-FINAL-1730-512 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-PROD-012: Compliance Information
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sebi_approval_number')->nullable()->after('status');
            $table->date('sebi_approval_date')->nullable()->after('sebi_approval_number');
            $table->text('compliance_notes')->nullable()->after('description');
            $table->text('regulatory_warnings')->nullable()->after('compliance_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'sebi_approval_number', 
                'sebi_approval_date', 
                'compliance_notes', 
                'regulatory_warnings'
            ]);
        });
    }
};