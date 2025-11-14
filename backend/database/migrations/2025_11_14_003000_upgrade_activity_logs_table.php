<?php
// V-FINAL-1730-404 (Created) | V-FINAL-1730-406 (FIXED: Idempotent)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            
            // --- FIX: Check if columns exist before adding ---

            if (!Schema::hasColumn('activity_logs', 'target_type')) {
                $table->string('target_type')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('activity_logs', 'target_id')) {
                $table->unsignedBigInteger('target_id')->nullable()->after('target_type');
            }
            
            if (!Schema::hasColumn('activity_logs', 'user_agent')) {
                $table->string('user_agent')->nullable()->after('ip_address');
            }
            
            if (!Schema::hasColumn('activity_logs', 'old_values')) {
                $table->json('old_values')->nullable()->after('user_agent');
            }
            
            if (!Schema::hasColumn('activity_logs', 'new_values')) {
                $table->json('new_values')->nullable()->after('old_values');
            }

            // Add index (it's safe to add if it exists, but good practice)
            $indexExists = collect(DB::select("SHOW INDEX FROM activity_logs"))
                             ->pluck('Key_name')
                             ->contains('activity_logs_target_type_target_id_index');

            if (!$indexExists && Schema::hasColumn('activity_logs', 'target_type') && Schema::hasColumn('activity_logs', 'target_id')) {
                $table->index(['target_type', 'target_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Drop only if they exist
            if (Schema::hasColumn('activity_logs', 'target_type')) {
                $table->dropColumn('target_type');
            }
            if (Schema::hasColumn('activity_logs', 'target_id')) {
                $table->dropColumn('target_id');
            }
            if (Schema::hasColumn('activity_logs', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
            if (Schema::hasColumn('activity_logs', 'old_values')) {
                $table->dropColumn('old_values');
            }
            if (Schema::hasColumn('activity_logs', 'new_values')) {
                $table->dropColumn('new_values');
            }
        });
    }
};