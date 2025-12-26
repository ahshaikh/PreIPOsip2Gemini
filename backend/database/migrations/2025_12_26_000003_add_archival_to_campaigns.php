<?php

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
        Schema::table('campaigns', function (Blueprint $table) {
            // Archival fields
            $table->boolean('is_archived')->default(false)->after('is_active');
            $table->foreignId('archived_by')->nullable()->after('is_archived')->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable()->after('archived_by');
            $table->text('archive_reason')->nullable()->after('archived_at');

            // Soft deletes (for complete removal, but should be rare)
            $table->softDeletes();

            // Indexes
            $table->index('is_archived');
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['is_archived']);
            $table->dropIndex(['archived_at']);
            $table->dropForeign(['archived_by']);
            $table->dropColumn(['is_archived', 'archived_by', 'archived_at', 'archive_reason']);
        });
    }
};
