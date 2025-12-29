<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('banners', 'start_date')) {
                $table->dateTime('start_date')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('banners', 'end_date')) {
                $table->dateTime('end_date')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('banners', 'display_order')) {
                $table->integer('display_order')->default(0)->after('end_date');
            }
            if (!Schema::hasColumn('banners', 'image_url')) {
                $table->string('image_url')->nullable()->after('link_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'display_order', 'image_url']);
        });
    }
};