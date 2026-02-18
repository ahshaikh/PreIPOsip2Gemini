<?php
// V-CONTRACT-HARDENING-003: Override tracking in bonus transactions for audit
// Every bonus transaction must record whether a regulatory override was applied
// and which override was used, enabling complete audit trail.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bonus_transactions', function (Blueprint $table) {
            // Override tracking fields
            $table->boolean('override_applied')->default(false)->after('description')
                ->comment('Whether a regulatory override was applied to this calculation');
            $table->foreignId('override_id')->nullable()->after('override_applied')
                ->constrained('plan_regulatory_overrides')
                ->onDelete('restrict')
                ->comment('Reference to the regulatory override used (if any)');

            // Additional audit fields for override scenarios
            $table->json('config_used')->nullable()->after('override_id')
                ->comment('The actual config used for calculation (for audit verification)');
            $table->json('override_delta')->nullable()->after('config_used')
                ->comment('What the override changed from snapshot (for transparency)');

            // Index for querying override usage
            $table->index('override_applied', 'idx_override_applied');
            $table->index('override_id', 'idx_override_id');
        });
    }

    public function down(): void
    {
        Schema::table('bonus_transactions', function (Blueprint $table) {
	    
	    // Drop foreign key FIRST
	    $table->dropForeign(['override_id']);

            // Then drop indexes
            $table->dropIndex('idx_override_applied');
            $table->dropIndex('idx_override_id');
            
            // Then drop columns
            $table->dropColumn([
                'override_applied',
                'override_id',
                'config_used',
                'override_delta',
            ]);
        });
    }
};
