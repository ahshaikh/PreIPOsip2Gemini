<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P0 FIX: Share Traceability for Company Investments
 *
 * This migration adds per-lot provenance tracking to enable forensic
 * reconstruction of share flow:
 *
 * BulkPurchase → CompanyInvestment → AdminLedgerEntry
 *
 * Without this, we cannot prove which inventory lot funded which investment.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add bulk_purchase_id to company_investments for per-lot provenance
        Schema::table('company_investments', function (Blueprint $table) {
            $table->foreignId('bulk_purchase_id')
                ->nullable()
                ->after('company_id')
                ->constrained('bulk_purchases')
                ->nullOnDelete();

            $table->foreignId('admin_ledger_entry_id')
                ->nullable()
                ->after('bulk_purchase_id')
                ->comment('Reference to AdminLedgerEntry proving cash receipt');

            $table->string('allocation_status')
                ->default('unallocated')
                ->after('status')
                ->comment('unallocated, allocated, partially_allocated');

            $table->decimal('allocated_value', 15, 2)
                ->default(0)
                ->after('amount')
                ->comment('Actual value allocated from inventory');

            $table->index(['bulk_purchase_id', 'status']);
            $table->index(['allocation_status']);
        });

        // 2. Create share_allocation_logs for immutable audit trail
        Schema::create('share_allocation_logs', function (Blueprint $table) {
            $table->id();

            // Source: Where shares came from
            $table->foreignId('bulk_purchase_id')
                ->constrained('bulk_purchases')
                ->cascadeOnDelete();

            // Destination: Where shares went
            $table->string('allocatable_type'); // CompanyInvestment, UserInvestment
            $table->unsignedBigInteger('allocatable_id');

            // Allocation details
            $table->decimal('value_allocated', 15, 2);
            $table->decimal('units_allocated', 15, 4)->nullable();

            // Balances at time of allocation (for forensic reconstruction)
            $table->decimal('inventory_before', 15, 2);
            $table->decimal('inventory_after', 15, 2);

            // Cash tracking link
            $table->foreignId('admin_ledger_entry_id')
                ->nullable()
                ->comment('Link to cash receipt entry');

            // Provenance metadata
            $table->foreignId('company_id')
                ->constrained('companies');
            $table->foreignId('user_id')
                ->constrained('users');
            $table->foreignId('allocated_by')
                ->nullable()
                ->constrained('users')
                ->comment('Admin who approved allocation, null for auto');

            // Immutability
            $table->boolean('is_immutable')->default(true);
            $table->timestamp('locked_at')->nullable();

            // Reversal tracking
            $table->boolean('is_reversed')->default(false);
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason')->nullable();
            $table->foreignId('reversal_log_id')
                ->nullable()
                ->comment('ID of compensating log entry');

            // Audit
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for reconciliation queries
            $table->index(['bulk_purchase_id', 'is_reversed']);
            $table->index(['allocatable_type', 'allocatable_id']);
            $table->index(['company_id', 'created_at']);
            $table->index(['admin_ledger_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_allocation_logs');

        Schema::table('company_investments', function (Blueprint $table) {
            $table->dropForeign(['bulk_purchase_id']);
            $table->dropColumn([
                'bulk_purchase_id',
                'admin_ledger_entry_id',
                'allocation_status',
                'allocated_value',
            ]);
        });
    }
};
