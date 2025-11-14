<?php
// V-FINAL-1730-386 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FSD-NOTIF-006: Logs all sent emails
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('template_slug');
            $table->string('to_email');
            $table->string('subject');
            $table->longText('body');
            $table->string('status')->default('queued'); // queued, sending, sent, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // FSD-NOTIF-016: Stores user opt-out preferences
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('preference_key'); // e.g., "bonus_email", "marketing_email"
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            
            $table->unique(['user_id', 'preference_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('user_notification_preferences');
    }
};