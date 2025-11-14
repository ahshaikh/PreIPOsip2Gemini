<?php
// V-FINAL-1730-325

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->integer('attempts')->default(0)->after('otp_code');
            $table->timestamp('last_sent_at')->nullable()->after('expires_at');
            $table->boolean('blocked')->default(false)->after('attempts');
        });
    }

    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'last_sent_at', 'blocked']);
        });
    }
};