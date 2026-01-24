<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Investment Snapshots Table
 *
 * PURPOSE:
 * Stores immutable snapshots of investment context at time of investment.
 * Used for historical record-keeping and audit trail.
 *
 * CRITICAL:
 * - Snapshots are IMMUTABLE once created
 * - Platform authority changes do NOT affect historical snapshots
 * - Used for dispute resolution and compliance
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('investment_snapshots', function (Blueprint $table) {
            $table->id();

            // Investment reference
            $table->foreignId('investment_id')
                ->constrained('investments')
                ->cascadeOnDelete()
                ->comment('Investment this snapshot belongs to');

            // Company reference
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->comment('Company at time of investment');

            // User reference
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Investor who made investment');

            // Snapshot metadata
            $table->timestamp('snapshot_at')
                ->comment('When snapshot was captured');

            $table->string('snapshot_type', 50)
                ->default('investment_creation')
                ->comment('Type of snapshot: investment_creation, material_change, platform_update');

            // Immutable snapshot data
            $table->json('company_snapshot')
                ->comment('Full company data at snapshot time');

            $table->json('platform_context_snapshot')
                ->comment('Platform context (visibility, tier, buying status) at snapshot time');

            $table->json('disclosure_snapshot')
                ->comment('Disclosure data investor saw at investment time');

            $table->json('risk_acknowledgements_snapshot')
                ->comment('Risk acknowledgements investor agreed to');

            $table->json('deal_snapshot')
                ->nullable()
                ->comment('Deal/offering data at snapshot time');

            // Investment details at snapshot
            $table->decimal('investment_amount', 15, 2)
                ->comment('Amount invested');

            $table->integer('shares_allocated')
                ->nullable()
                ->comment('Shares allocated at snapshot time');

            $table->decimal('price_per_share', 15, 2)
                ->nullable()
                ->comment('Price per share at snapshot time');

            // Audit fields
            $table->string('idempotency_key', 100)
                ->nullable()
                ->comment('Idempotency key from original investment');

            $table->string('ip_address', 45)
                ->nullable()
                ->comment('IP address of investor at investment time');

            $table->text('user_agent')
                ->nullable()
                ->comment('User agent at investment time');

            // Immutability guarantee
            $table->boolean('is_immutable')
                ->default(true)
                ->comment('Snapshot cannot be modified (always true)');

            $table->timestamps();

            // Indexes for performance
            $table->index('snapshot_at');
            $table->index(['company_id', 'snapshot_at']);
            $table->index(['user_id', 'snapshot_at']);
            $table->index(['investment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_snapshots');
    }
};
