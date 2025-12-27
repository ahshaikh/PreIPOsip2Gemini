<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit Logging System.
     *
     * Tracks all admin actions for compliance, security, and debugging.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Actor Information
            $table->string('actor_type')->comment('admin, company_user, system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Action Information
            $table->string('action')->index()->comment('created, updated, deleted, approved, rejected, etc');
            $table->string('module')->index()->comment('companies, products, deals, users, etc');
            $table->string('description');

            // Target Entity
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_name')->nullable();

            // Change Details
            $table->json('old_values')->nullable()->comment('State before change');
            $table->json('new_values')->nullable()->comment('State after change');
            $table->json('metadata')->nullable()->comment('Additional context');

            // Context
            $table->string('request_method', 10)->nullable();
            $table->string('request_url')->nullable();
            $table->string('session_id')->nullable();

            // Risk & Compliance
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->boolean('requires_review')->default(false);

            $table->timestamp('created_at')->index();

            // Indexes for common queries
            $table->index(['actor_type', 'actor_id']);
            $table->index(['target_type', 'target_id']);
            $table->index(['module', 'action']);
            $table->index(['risk_level', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
