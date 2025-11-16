<?php
// V-FINAL-1730-592 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-SUPPORT-106: Ticket Rating
     */
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable()->after('resolved_at'); // 1-5 stars
            $table->text('rating_feedback')->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['rating', 'rating_feedback']);
        });
    }
};