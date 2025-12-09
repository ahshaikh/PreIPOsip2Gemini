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
        // Add new fields to users table for enhanced management
        Schema::table('users', function (Blueprint $table) {
            $table->string('suspension_reason')->nullable()->after('status');
            $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
            $table->foreignId('suspended_by')->nullable()->constrained('users')->after('suspended_at');

            $table->string('block_reason')->nullable()->after('suspended_by');
            $table->timestamp('blocked_at')->nullable()->after('block_reason');
            $table->foreignId('blocked_by')->nullable()->constrained('users')->after('blocked_at');
            $table->boolean('is_blacklisted')->default(false)->after('blocked_by');

            $table->boolean('is_anonymized')->default(false)->after('is_blacklisted');
            $table->timestamp('anonymized_at')->nullable()->after('is_anonymized');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['suspended_by']);
            $table->dropForeign(['blocked_by']);
            $table->dropColumn([
                'suspension_reason',
                'suspended_at',
                'suspended_by',
                'block_reason',
                'blocked_at',
                'blocked_by',
                'is_blacklisted',
                'is_anonymized',
                'anonymized_at',
            ]);
        });
    }
};
