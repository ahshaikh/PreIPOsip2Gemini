<?php
// V-FINAL-1730-261

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->date('pause_start_date')->nullable()->after('status');
            $table->date('pause_end_date')->nullable()->after('pause_start_date');
            $table->timestamp('cancelled_at')->nullable()->after('pause_end_date');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['pause_start_date', 'pause_end_date', 'cancelled_at', 'cancellation_reason']);
        });
    }
};