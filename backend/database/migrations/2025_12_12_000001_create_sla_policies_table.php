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
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('ticket_category')->nullable(); // null = applies to all
            $table->enum('ticket_priority', ['low', 'medium', 'high'])->nullable(); // null = applies to all

            // Response SLA (first reply time)
            $table->integer('response_time_hours')->default(24);

            // Resolution SLA (time to close ticket)
            $table->integer('resolution_time_hours')->default(72);

            // Business hours only?
            $table->boolean('business_hours_only')->default(false);

            // Working hours (for business hours calculation)
            $table->time('work_start_time')->default('09:00:00');
            $table->time('work_end_time')->default('18:00:00');

            // Working days (JSON array: [1,2,3,4,5] for Mon-Fri)
            $table->json('working_days')->default('[1,2,3,4,5]');

            // Escalation rules
            $table->boolean('auto_escalate')->default(true);
            $table->integer('escalation_threshold_percent')->default(80); // Escalate at 80% of SLA

            // Priority order (lower number = higher priority)
            $table->integer('priority_order')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['ticket_category', 'ticket_priority']);
            $table->index('is_active');
        });

        Schema::create('ticket_sla_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('sla_policy_id')->nullable()->constrained()->onDelete('set null');

            // Response SLA
            $table->timestamp('response_due_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->boolean('response_sla_breached')->default(false);
            $table->integer('response_time_minutes')->nullable();

            // Resolution SLA
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('resolution_sla_breached')->default(false);
            $table->integer('resolution_time_minutes')->nullable();

            // Escalation
            $table->boolean('escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('escalated_to_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('escalation_reason')->nullable();

            // Pauses (e.g., waiting for user response)
            $table->timestamp('paused_at')->nullable();
            $table->integer('total_paused_minutes')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('support_ticket_id');
            $table->index(['response_sla_breached', 'resolution_sla_breached'], 'idx_sla_breach');
            $table->index('escalated');
            $table->index('response_due_at');
            $table->index('resolution_due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_sla_tracking');
        Schema::dropIfExists('sla_policies');
    }
};
