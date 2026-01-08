<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create deal_approvals Table
 *
 * PROTOCOL 1 ANALYSIS:
 * - Error indicates deal_approvals table EXISTS but missing columns
 * - However, no migration creates this table in repo
 * - Table was likely created manually or in uncommitted migration
 *
 * SOLUTION:
 * - Create complete deal_approvals table with all required columns
 * - Include approved_at, approved_by for analytics queries
 * - Follow platform's approval workflow pattern (matches withdrawals, campaigns)
 *
 * PURPOSE:
 * - Track deal approval workflow from submission → review → approved/rejected
 * - Audit trail for compliance (who approved, when, why)
 * - Analytics dashboard (approvals over time, avg processing time)
 *
 * SCHEMA RATIONALE:
 * - deal_id: Links to deals table for deal details
 * - submitted_by: Company user requesting approval
 * - reviewed_by: Admin who reviewed
 * - approved_by/rejected_by: Admin who made final decision
 * - status: pending/under_review/approved/rejected
 * - notes: Reason for rejection or approval comments
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only create if table doesn't already exist
        if (!Schema::hasTable('deal_approvals')) {
            Schema::create('deal_approvals', function (Blueprint $table) {
                $table->id();

                // Deal reference
                $table->foreignId('deal_id')
                    ->constrained('deals')
                    ->onDelete('cascade')
                    ->comment('Reference to the deal being approved');

                // Workflow tracking
                $table->enum('status', ['pending', 'under_review', 'approved', 'rejected'])
                    ->default('pending')
                    ->index()
                    ->comment('Current approval status');

                // User tracking
                $table->foreignId('submitted_by')
                    ->constrained('users')
                    ->onDelete('cascade')
                    ->comment('Company user who submitted for approval');

                $table->foreignId('reviewed_by')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->comment('Admin who started reviewing');

                // PROTOCOL 1 FIX: Add review_started_at timestamp
                // EXECUTION PATH: /admin/deal-approvals/analytics queries this column
                // SQL: WHERE reviewed_by IS NOT NULL AND review_started_at BETWEEN...
                $table->timestamp('review_started_at')
                    ->nullable()
                    ->index() // Analytics queries filter by this column
                    ->comment('Timestamp when review was started by admin');

                $table->foreignId('approved_by')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->comment('Admin who approved the deal');

                $table->timestamp('approved_at')
                    ->nullable()
                    ->index() // Critical for analytics queries
                    ->comment('Timestamp when deal was approved');

                $table->foreignId('rejected_by')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->comment('Admin who rejected the deal');

                $table->timestamp('rejected_at')
                    ->nullable()
                    ->comment('Timestamp when deal was rejected');

                // Audit and notes
                $table->text('submission_notes')->nullable()->comment('Notes from submitter');
                $table->text('review_notes')->nullable()->comment('Internal admin review notes');
                $table->text('decision_notes')->nullable()->comment('Reason for approval/rejection');

                // Compliance and metadata
                $table->json('checklist')->nullable()->comment('Approval checklist items');
                $table->integer('priority')->default(3)->comment('1=High, 2=Medium, 3=Low');
                $table->timestamp('deadline')->nullable()->comment('Target review deadline');

                // Standard timestamps and soft deletes
                $table->timestamps();
                $table->softDeletes();

                // Additional indexes for common queries
                $table->index('status');
                $table->index('created_at');
                $table->index(['status', 'approved_at']); // For analytics: approved deals in date range
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_approvals');
    }
};
