<?php
// V-FINAL-1730-531 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-SEO-006: Track redirect hits
     */
    public function up(): void
    {
        Schema::table('redirects', function (Blueprint $table) {
            $table->unsignedBigInteger('hit_count')->default(0)->after('status_code');
        });
    }

    public function down(): void
    {
        Schema::table('redirects', function (Blueprint $table) {
            $table->dropColumn('hit_count');
        });
    }
};