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
        // Check if offers table exists (migration from old system)
        if (Schema::hasTable('offers')) {
            // Rename table
            Schema::rename('offers', 'campaigns');

            // Add workflow and scheduling fields
            Schema::table('campaigns', function (Blueprint $table) {
                // Workflow fields
                $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable()->after('approved_by');

                // Active toggle (replaces status enum for activation)
                $table->boolean('is_active')->default(false)->after('approved_at');

                // Scheduling fields
                $table->timestamp('start_at')->nullable()->after('is_featured');

                // Rename expiry to end_at for consistency
                $table->renameColumn('expiry', 'end_at');

                // Add indexes for performance
                $table->index('created_by');
                $table->index('approved_by');
                $table->index('is_active');
                $table->index('start_at');
            });

            // Remove status enum - state is now derived from is_active, start_at, end_at, approved_at
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        } else {
            // Fresh installation - create campaigns table directly
            Schema::create('campaigns', function (Blueprint $table) {
                $table->id();

                // Workflow fields
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();

                // Active toggle
                $table->boolean('is_active')->default(false);

                // Campaign details
                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->text('long_description')->nullable();

                // Discount configuration
                $table->enum('discount_type', ['percentage', 'fixed_amount']);
                $table->decimal('discount_percent', 5, 2)->nullable();
                $table->decimal('discount_amount', 12, 2)->nullable();
                $table->decimal('min_investment', 12, 2)->nullable();
                $table->decimal('max_discount', 12, 2)->nullable();

                // Usage limits
                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('usage_count')->default(0);
                $table->unsignedInteger('user_usage_limit')->default(1);

                // Media
                $table->string('image_url')->nullable();
                $table->string('hero_image')->nullable();
                $table->string('video_url')->nullable();

                // JSON arrays
                $table->json('features')->nullable();
                $table->json('terms')->nullable();

                // Flags
                $table->boolean('is_featured')->default(false);

                // Scheduling fields
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();

                $table->timestamps();

                // Indexes for performance
                $table->index('created_by');
                $table->index('approved_by');
                $table->index('is_active');
                $table->index('start_at');
                $table->index('end_at');
                $table->index('code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add status column
        Schema::table('campaigns', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active')->after('code');
        });

        // Rename back to expiry
        Schema::table('campaigns', function (Blueprint $table) {
            $table->renameColumn('end_at', 'expiry');
        });

        // Drop added columns - MUST drop foreign keys before indexes
        Schema::table('campaigns', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['created_by']);
            $table->dropForeign(['approved_by']);

            // Then drop indexes
            $table->dropIndex(['created_by']);
            $table->dropIndex(['approved_by']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['start_at']);

            // Finally drop columns
            $table->dropColumn(['created_by', 'approved_by', 'approved_at', 'is_active', 'start_at']);
        });

        // Rename table back
        Schema::rename('campaigns', 'offers');
    }
};
