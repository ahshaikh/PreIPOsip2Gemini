<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Company Analytics Tracking
        Schema::create('company_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->date('date');
            $table->integer('profile_views')->default(0);
            $table->integer('document_downloads')->default(0);
            $table->integer('financial_report_downloads')->default(0);
            $table->integer('deal_views')->default(0);
            $table->integer('investor_interest_clicks')->default(0);
            $table->json('viewer_demographics')->nullable(); // Country, device, etc.
            $table->timestamps();

            $table->unique(['company_id', 'date']);
            $table->index('date');
        });

        // Investor Interest Tracking
        Schema::create('investor_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('investor_email')->nullable();
            $table->string('investor_name')->nullable();
            $table->string('investor_phone')->nullable();
            $table->enum('interest_level', ['low', 'medium', 'high'])->default('medium');
            $table->decimal('investment_range_min', 15, 2)->nullable();
            $table->decimal('investment_range_max', 15, 2)->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'contacted', 'qualified', 'not_interested'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        // Company Q&A
        Schema::create('company_qna', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('asked_by_name')->nullable();
            $table->string('asked_by_email')->nullable();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('company_users')->onDelete('set null');
            $table->timestamp('answered_at')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('helpful_count')->default(0);
            $table->enum('status', ['pending', 'answered', 'archived'])->default('pending');
            $table->timestamps();

            $table->index(['company_id', 'status', 'is_public']);
        });

        // Webinar/Investor Calls
        Schema::create('company_webinars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('company_users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['webinar', 'investor_call', 'ama', 'product_demo'])->default('webinar');
            $table->timestamp('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->string('meeting_link')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->integer('max_participants')->nullable();
            $table->integer('registered_count')->default(0);
            $table->json('speakers')->nullable(); // Array of speaker names/details
            $table->text('agenda')->nullable();
            $table->enum('status', ['scheduled', 'live', 'completed', 'cancelled'])->default('scheduled');
            $table->boolean('recording_available')->default(false);
            $table->string('recording_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'scheduled_at', 'status']);
        });

        // Webinar Registrations
        Schema::create('webinar_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webinar_id')->constrained('company_webinars')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('attendee_name');
            $table->string('attendee_email');
            $table->string('attendee_phone')->nullable();
            $table->text('questions')->nullable();
            $table->boolean('attended')->default(false);
            $table->timestamp('attended_at')->nullable();
            $table->enum('status', ['registered', 'confirmed', 'cancelled'])->default('registered');
            $table->timestamps();

            $table->unique(['webinar_id', 'attendee_email']);
        });

        // Onboarding Progress Tracking
        Schema::create('company_onboarding_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->json('completed_steps')->nullable(); // Array of completed step IDs
            $table->integer('current_step')->default(1);
            $table->integer('total_steps')->default(10);
            $table->integer('completion_percentage')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_onboarding_progress');
        Schema::dropIfExists('webinar_registrations');
        Schema::dropIfExists('company_webinars');
        Schema::dropIfExists('company_qna');
        Schema::dropIfExists('investor_interests');
        Schema::dropIfExists('company_analytics');
    }
};
