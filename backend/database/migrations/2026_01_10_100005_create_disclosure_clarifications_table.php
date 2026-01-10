<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 1 - MIGRATION 5/6: Create Disclosure Clarifications Table
 *
 * PURPOSE:
 * Creates the Q&A system for back-and-forth between admins and companies
 * during disclosure review. Enables structured clarification requests with
 * field-level precision and threading support.
 *
 * KEY CONCEPTS:
 * - THREADED CONVERSATIONS: Support follow-up questions on same topic
 * - FIELD-LEVEL TARGETING: Questions can target specific JSON paths
 * - STATUS TRACKING: open → answered → accepted/disputed workflow
 * - REGULATORY AUDIT: Full history of admin-company communications
 *
 * WORKFLOW:
 * 1. Admin reviews disclosure, finds issue in "revenue_streams"
 * 2. Admin creates clarification: "Please explain 30% YoY growth claim"
 * 3. Company answers with supporting data
 * 4. Admin accepts answer → disclosure moves forward
 * 5. OR Admin disputes → company must revise disclosure
 *
 * EXAMPLE USE CASE:
 * Admin question: "Your Q3 revenue shows $5M but earlier stated $3M. Which is correct?"
 * Field path: "disclosure_data.financial_performance.quarterly_revenue.Q3_2024"
 * Company answer: "Corrected. Q3 revenue is $3M. Updated disclosure data."
 * Status: accepted → company resubmits disclosure with fix
 *
 * RELATION TO OTHER TABLES:
 * - company_disclosures: Parent disclosure being reviewed
 * - disclosure_clarifications (self): Threading support (parent_id)
 * - users: Admin asking question, CompanyUser answering
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('disclosure_clarifications', function (Blueprint $table) {
            $table->id();

            // =====================================================================
            // PARENT REFERENCES
            // =====================================================================

            $table->foreignId('company_disclosure_id')
                ->constrained('company_disclosures')
                ->cascadeOnDelete()
                ->comment('Disclosure being clarified');

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->comment('Denormalized for query performance');

            $table->foreignId('disclosure_module_id')
                ->constrained('disclosure_modules')
                ->restrictOnDelete()
                ->comment('Denormalized for query performance');

            // =====================================================================
            // THREADING SUPPORT
            // =====================================================================
            // Enables follow-up questions on same topic

            $table->foreignId('parent_id')->nullable()
                ->constrained('disclosure_clarifications')
                ->cascadeOnDelete()
                ->comment('Parent clarification for threaded conversations (NULL = root question)');

            $table->unsignedInteger('thread_depth')->default(0)
                ->comment('Nesting level: 0=root, 1=reply, 2=reply-to-reply, etc.');

            // =====================================================================
            // CLARIFICATION REQUEST (ADMIN → COMPANY)
            // =====================================================================

            $table->string('question_subject', 255)
                ->comment('Brief subject line: "Revenue Growth Clarification"');

            $table->text('question_body')
                ->comment('Detailed question from admin');

            $table->enum('question_type', [
                'missing_data',        // Required field not provided
                'inconsistency',       // Data conflicts with other disclosures
                'insufficient_detail', // Need more explanation
                'verification',        // Need supporting documents
                'compliance',          // Regulatory requirement not met
                'other'
            ])->default('other')
                ->comment('Category of clarification request');

            $table->foreignId('asked_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->comment('Admin who asked the question');

            $table->timestamp('asked_at')
                ->comment('When question was asked');

            // =====================================================================
            // FIELD-LEVEL TARGETING
            // =====================================================================
            // Precision linking to specific disclosure data fields

            $table->string('field_path', 500)->nullable()
                ->comment('JSON path to specific field: "disclosure_data.revenue_streams[0].percentage"');

            $table->json('highlighted_data')->nullable()
                ->comment('Snapshot of problematic data: {"revenue_streams":[{"name":"Subscriptions","percentage":120}]}');

            $table->json('suggested_fix')->nullable()
                ->comment('Admin suggestion for correction (optional)');

            // =====================================================================
            // ANSWER (COMPANY → ADMIN)
            // =====================================================================

            $table->text('answer_body')->nullable()
                ->comment('Company response to clarification request');

            $table->foreignId('answered_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('CompanyUser who provided the answer');

            $table->timestamp('answered_at')->nullable()
                ->comment('When company answered');

            $table->json('supporting_documents')->nullable()
                ->comment('Documents uploaded with answer: [{"file_path":"docs/revenue-proof.pdf","description":"Bank statement"}]');

            // =====================================================================
            // STATUS & RESOLUTION
            // =====================================================================

            $table->enum('status', [
                'open',           // Waiting for company answer
                'answered',       // Company answered, waiting for admin review
                'accepted',       // Admin accepted answer, issue resolved
                'disputed',       // Admin disputed answer, need revision
                'withdrawn'       // Admin withdrew question (mistake)
            ])->default('open')
                ->comment('Current status of clarification');

            $table->text('resolution_notes')->nullable()
                ->comment('Admin notes on acceptance/dispute of answer');

            $table->foreignId('resolved_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who accepted/disputed the answer');

            $table->timestamp('resolved_at')->nullable()
                ->comment('When admin resolved (accepted/disputed) the clarification');

            // =====================================================================
            // URGENCY & DEADLINES
            // =====================================================================

            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')
                ->comment('Urgency of clarification (affects SLA)');

            $table->timestamp('due_date')->nullable()
                ->comment('Deadline for company to respond (typically 7 days)');

            $table->boolean('is_blocking')->default(false)
                ->comment('Whether approval is blocked until this is resolved');

            // =====================================================================
            // INTERNAL TRACKING
            // =====================================================================

            $table->text('internal_notes')->nullable()
                ->comment('Admin-only internal notes (not visible to company)');

            $table->boolean('is_visible_to_company')->default(true)
                ->comment('Whether company can see this clarification (for internal admin discussions)');

            $table->unsignedInteger('reminder_count')->default(0)
                ->comment('How many reminder emails sent to company');

            $table->timestamp('last_reminder_at')->nullable()
                ->comment('When last reminder was sent');

            // =====================================================================
            // AUDIT TRAIL
            // =====================================================================

            $table->string('asked_by_ip', 45)->nullable()
                ->comment('IP address of admin when question created');

            $table->string('answered_by_ip', 45)->nullable()
                ->comment('IP address of company user when answered');

            // =====================================================================
            // TIMESTAMPS
            // =====================================================================

            $table->timestamps();
            $table->softDeletes();

            // =====================================================================
            // INDEXES
            // =====================================================================

            $table->index('status', 'idx_clarifications_status');
            $table->index(['company_disclosure_id', 'status'], 'idx_clarifications_disclosure_status');
            $table->index(['company_id', 'status', 'due_date'], 'idx_clarifications_company_due');
            $table->index('parent_id', 'idx_clarifications_thread');
            $table->index(['is_blocking', 'status'], 'idx_clarifications_blocking');
            $table->index('due_date', 'idx_clarifications_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disclosure_clarifications');
    }
};
