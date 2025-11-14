<?php
// V-FINAL-1730-375 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->foreignId('referral_campaign_id')->nullable()->constrained('referral_campaigns')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropForeign(['referral_campaign_id']);
            $table->dropColumn('referral_campaign_id');
        });
    }
};