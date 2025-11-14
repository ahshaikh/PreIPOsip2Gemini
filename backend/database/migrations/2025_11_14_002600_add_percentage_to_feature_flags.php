<?php
// V-FINAL-1730-402 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_flags', function (Blueprint $table) {
            // e.g., 10 = 10% of users. null = 100% on/off
            $table->unsignedSmallInteger('percentage')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('feature_flags', function (Blueprint $table) {
            $table->dropColumn('percentage');
        });
    }
};