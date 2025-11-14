<?php
// V-FINAL-1730-381 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            // Admin who is responsible for this ticket
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null')->after('user_id');
            // SLA defined in hours
            $table->integer('sla_hours')->default(24)->after('priority');
            // Final closed state
            $table->timestamp('closed_at')->nullable()->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn(['assigned_to', 'sla_hours', 'closed_at']);
        });
    }
};