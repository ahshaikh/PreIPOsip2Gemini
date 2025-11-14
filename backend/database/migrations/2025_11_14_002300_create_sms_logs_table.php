<?php
// V-FINAL-1730-390 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FSD-NOTIF-011: SMS Logs
     */
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('to_mobile');
            $table->string('template_slug')->nullable();
            $table->string('dlt_template_id')->nullable();
            $table->text('message');
            $table->string('status')->default('queued'); // queued, sending, sent, failed
            $table->text('error_message')->nullable();
            $table->string('gateway_message_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};