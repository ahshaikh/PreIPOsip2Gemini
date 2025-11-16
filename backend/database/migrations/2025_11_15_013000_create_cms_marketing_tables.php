<?php
// V-FINAL-1730-512 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-FRONT-007 (Banners) & FSD-SEO-004 (Redirects)
     */
    public function up(): void
    {
        // 1. Banners & Popups
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Internal name
            $table->text('content')->nullable(); // HTML or Text
            $table->string('link_url')->nullable();
            $table->string('type')->default('top_bar'); // top_bar, popup
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        // 2. URL Redirects (SEO)
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_url')->unique(); // e.g., /old-page
            $table->string('to_url');   // e.g., /new-page
            $table->integer('status_code')->default(301); // 301, 302
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('redirects');
    }
};