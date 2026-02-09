<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DISCLOSURE DOCUMENTS - Immutable Attachments
 *
 * PURPOSE:
 * Store document attachments for disclosure events.
 * Documents are immutable and versioned by event.
 *
 * IMMUTABILITY PRINCIPLE:
 * - Documents are never overwritten
 * - Documents are never deleted (except via cascade on thread delete)
 * - Each event gets its own document snapshots
 * - Complete document history preserved
 *
 * USAGE:
 * - Company uploads documents with submission
 * - Company uploads documents with response
 * - Platform uploads documents with clarification (rare)
 *
 * RELATION TO OTHER TABLES:
 * - disclosure_events: Parent event
 * - company_disclosures: Thread (denormalized for performance)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('disclosure_documents', function (Blueprint $table) {
            $table->id();

            // =========================================================================
            // PARENT REFERENCES
            // =========================================================================
            $table->foreignId('disclosure_event_id')
                ->constrained('disclosure_events')
                ->cascadeOnDelete()
                ->comment('Event this document was attached to');

            $table->foreignId('company_disclosure_id')
                ->constrained('company_disclosures')
                ->cascadeOnDelete()
                ->comment('Disclosure thread (denormalized for queries)');

            // =========================================================================
            // FILE INFORMATION
            // =========================================================================
            $table->string('file_name', 255)
                ->comment('Original filename with extension');

            $table->string('storage_path', 500)
                ->comment('Path in storage (e.g., disclosure-documents/company-123/file.pdf)');

            $table->string('mime_type', 100)
                ->comment('File MIME type (e.g., application/pdf)');

            $table->unsignedBigInteger('file_size')
                ->comment('File size in bytes');

            $table->string('file_hash', 64)->nullable()
                ->comment('SHA256 hash for integrity verification');

            // =========================================================================
            // METADATA
            // =========================================================================
            $table->string('document_type', 100)->nullable()
                ->comment('Document classification: financial_statement, legal_document, etc');

            $table->text('description')->nullable()
                ->comment('Optional description provided by uploader');

            // =========================================================================
            // UPLOADER INFORMATION
            // =========================================================================
            $table->morphs('uploaded_by', 'idx_disclosure_documents_uploader');
            // uploaded_by_type + uploaded_by_id:
            // - CompanyUser for company uploads
            // - User (admin) for platform uploads

            $table->string('uploaded_by_name', 255)
                ->comment('Cached uploader name (denormalized)');

            // =========================================================================
            // ACCESS CONTROL
            // =========================================================================
            $table->boolean('is_public')->default(false)
                ->comment('Whether document is visible to investors (after approval)');

            $table->enum('visibility', ['company', 'platform', 'public'])->default('company')
                ->comment('Who can access this document');

            // =========================================================================
            // AUDIT TRAIL
            // =========================================================================
            $table->string('uploaded_from_ip', 45)->nullable()
                ->comment('IP address of uploader');

            $table->timestamp('created_at')
                ->comment('When document was uploaded (immutable)');

            // =========================================================================
            // INDEXES
            // =========================================================================
            $table->index(['company_disclosure_id', 'created_at'], 'idx_disclosure_docs_thread_time');
            $table->index('disclosure_event_id', 'idx_disclosure_docs_event');
            $table->index('storage_path', 'idx_disclosure_docs_storage');
            $table->index('file_hash', 'idx_disclosure_docs_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disclosure_documents');
    }
};
