<?php
// V-AUDIT-FIX-REPORTS | [AUDIT FIX] User Reports Module - High Priority #1
// Migration to create generated_reports table for storing user-generated financial reports

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();

            // User relationship
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Report metadata
            $table->string('report_type', 50); // investment, payment, bonus, referral, tax, statement
            $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
            $table->string('date_range', 100); // e.g., "Jan 2025 - Dec 2025"

            // File information
            $table->string('file_path')->nullable(); // Storage path to generated file
            $table->unsignedBigInteger('file_size')->nullable(); // File size in bytes

            // Status tracking
            $table->enum('status', ['pending', 'ready', 'failed'])->default('pending');
            $table->text('error_message')->nullable(); // If status = failed

            // Timestamps
            $table->timestamps();
            $table->softDeletes(); // Allow soft deletes for audit trail

            // Indexes for performance
            $table->index('user_id');
            $table->index('status');
            $table->index(['user_id', 'report_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};
