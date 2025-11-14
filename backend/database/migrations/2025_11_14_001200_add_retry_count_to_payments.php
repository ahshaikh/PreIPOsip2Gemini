<?php
// V-FINAL-1730-305

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->integer('retry_count')->default(0)->after('status');
            $table->string('failure_reason')->nullable()->after('retry_count');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'failure_reason']);
        });
    }
};