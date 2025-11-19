<?php
// V-FINAL-1730-653 (Restoring Missing Tables)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Activity Logs (Required for Admin Dashboard)
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('action');
                $table->text('description')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                
                // Audit fields
                $table->nullableMorphs('target'); // target_type, target_id
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                
                $table->timestamp('created_at')->nullable();
            });
        }

        // 2. Notifications (Required for User Dashboard)
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('activity_logs');
    }
};