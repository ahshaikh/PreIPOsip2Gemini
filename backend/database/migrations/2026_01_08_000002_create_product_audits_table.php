<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Product Audit Trail (FIX 48)
     *
     * Purpose:
     * - Track all changes to products with complete audit trail
     * - Monitor critical changes (price, status, compliance fields)
     * - Enable compliance reporting and change history analysis
     * - Support rollback and change comparison
     */
    public function up(): void
    {
        Schema::create('product_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // Action tracking
            $table->enum('action', [
                'created',
                'updated',
                'activated',
                'deactivated',
                'price_updated',
                'compliance_updated',
                'deleted',
                'restored'
            ])->index();

            // Change tracking
            $table->json('changed_fields')->nullable()->comment('Array of field names that changed');
            $table->json('old_values')->nullable()->comment('Previous values of changed fields');
            $table->json('new_values')->nullable()->comment('New values of changed fields');
            $table->text('change_description')->nullable()->comment('Human-readable description of changes');

            // Critical change marker
            $table->boolean('is_critical')->default(false)->comment('Marks critical changes (price, status, SEBI approval, etc.)');
            $table->json('critical_fields')->nullable()->comment('Array of critical fields that changed');

            // User tracking
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('performed_by_type')->default('user')->comment('user, system, admin, api');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Request context
            $table->string('request_id')->nullable()->comment('UUID for request tracing');
            $table->text('request_url')->nullable();
            $table->string('request_method', 10)->nullable();

            // Compliance & metadata
            $table->text('compliance_notes')->nullable()->comment('Notes for compliance tracking');
            $table->json('metadata')->nullable()->comment('Additional contextual data');

            $table->timestamps();

            // Indexes for performance and querying
            $table->index('product_id');
            $table->index('action');
            $table->index('is_critical');
            $table->index('performed_by');
            $table->index('created_at');
            $table->index(['product_id', 'created_at']);
            $table->index(['product_id', 'action']);
            $table->index(['is_critical', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_audits');
    }
};
