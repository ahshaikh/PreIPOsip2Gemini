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
        // Admin Dashboard Widgets
        Schema::create('admin_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('widget_type'); // revenue_chart, user_stats, kyc_pending, etc.
            $table->integer('position')->default(0);
            $table->integer('width')->default(6); // Grid width (1-12)
            $table->integer('height')->default(4); // Grid height
            $table->json('config')->nullable(); // Widget-specific configuration
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['admin_id', 'widget_type']);
        });

        // Admin Preferences
        Schema::create('admin_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('key'); // dark_mode, sidebar_collapsed, etc.
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['admin_id', 'key']);
        });

        // Error Logs
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level')->default('error'); // error, warning, critical
            $table->string('message');
            $table->text('exception')->nullable();
            $table->text('stack_trace')->nullable();
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->string('url')->nullable();
            $table->string('method')->nullable(); // GET, POST, etc.
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable(); // Additional context data
            $table->boolean('is_resolved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['level', 'created_at']);
            $table->index('is_resolved');
        });

        // Scheduled Tasks
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('command'); // Artisan command or job class
            $table->string('expression'); // Cron expression
            $table->text('description')->nullable();
            $table->json('parameters')->nullable(); // Command parameters
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status')->nullable(); // success, failed
            $table->text('last_run_output')->nullable();
            $table->integer('last_run_duration')->nullable(); // in seconds
            $table->timestamp('next_run_at')->nullable();
            $table->integer('run_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
        });

        // Audit Logs (Admin Actions)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('action'); // create, update, delete, approve, reject, etc.
            $table->string('module'); // users, payments, kyc, settings, etc.
            $table->string('target_type')->nullable(); // Model class
            $table->unsignedBigInteger('target_id')->nullable(); // Model ID
            $table->json('old_values')->nullable(); // Before state
            $table->json('new_values')->nullable(); // After state
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'created_at']);
            $table->index(['module', 'action']);
            $table->index(['target_type', 'target_id']);
        });

        // Bulk Import Jobs
        Schema::create('bulk_import_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // users, investments, payments, etc.
            $table->string('filename');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->json('errors')->nullable(); // Array of error messages
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        // Data Export Jobs
        Schema::create('data_export_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // users, payments, investments, transactions, etc.
            $table->string('format'); // csv, xlsx, pdf, json
            $table->json('filters')->nullable(); // Applied filters
            $table->json('columns')->nullable(); // Selected columns
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('file_path')->nullable();
            $table->integer('file_size')->nullable(); // in bytes
            $table->integer('record_count')->default(0);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Auto-delete after expiry
            $table->timestamps();

            $table->index(['created_by', 'status']);
            $table->index('expires_at');
        });

        // Performance Metrics
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type'); // response_time, memory_usage, db_query_time, etc.
            $table->string('endpoint')->nullable(); // API endpoint or route
            $table->float('value');
            $table->string('unit')->default('ms'); // ms, mb, seconds, etc.
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['metric_type', 'recorded_at']);
            $table->index('endpoint');
        });

        // System Health Checks
        Schema::create('system_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('check_name'); // database, redis, queue, storage, mail, etc.
            $table->string('status'); // healthy, warning, critical, down
            $table->text('message')->nullable();
            $table->json('details')->nullable(); // Check-specific data
            $table->integer('response_time')->nullable(); // in milliseconds
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['check_name', 'checked_at']);
            $table->index('status');
        });

        // API Test Cases
        Schema::create('api_test_cases', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('method'); // GET, POST, PUT, DELETE
            $table->string('endpoint');
            $table->json('headers')->nullable();
            $table->json('body')->nullable();
            $table->json('expected_response')->nullable();
            $table->integer('expected_status_code')->default(200);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // API Test Results
        Schema::create('api_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_case_id')->constrained('api_test_cases')->onDelete('cascade');
            $table->string('status'); // passed, failed
            $table->integer('response_time')->nullable(); // in milliseconds
            $table->integer('status_code')->nullable();
            $table->json('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['test_case_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_test_results');
        Schema::dropIfExists('api_test_cases');
        Schema::dropIfExists('system_health_checks');
        Schema::dropIfExists('performance_metrics');
        Schema::dropIfExists('data_export_jobs');
        Schema::dropIfExists('bulk_import_jobs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('scheduled_tasks');
        Schema::dropIfExists('error_logs');
        Schema::dropIfExists('admin_preferences');
        Schema::dropIfExists('admin_dashboard_widgets');
    }
};
