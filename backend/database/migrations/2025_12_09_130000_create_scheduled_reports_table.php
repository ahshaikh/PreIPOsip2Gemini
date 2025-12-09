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
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('report_type'); // revenue, bonus, investment, etc.
            $table->string('frequency'); // daily, weekly, monthly, quarterly
            $table->json('parameters')->nullable(); // filters, date ranges, etc.
            $table->json('recipients'); // email addresses
            $table->string('format')->default('pdf'); // pdf, csv, excel
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('report_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_report_id')->constrained()->onDelete('cascade');
            $table->string('status'); // pending, processing, completed, failed
            $table->text('file_path')->nullable();
            $table->integer('file_size')->nullable();
            $table->json('error_details')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_runs');
        Schema::dropIfExists('scheduled_reports');
    }
};
