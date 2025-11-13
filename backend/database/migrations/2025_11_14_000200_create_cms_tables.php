<?php
// V-FINAL-1730-239

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Banners & Popups
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable(); // HTML or Text
            $table->string('image_url')->nullable();
            $table->string('link_url')->nullable();
            $table->string('type')->default('top_bar'); // top_bar, popup, slide
            $table->string('position')->default('top'); // top, bottom, center
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        // URL Redirects (SEO)
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_url')->unique(); // e.g., /old-page
            $table->string('to_url');   // e.g., /new-page
            $table->integer('status_code')->default(301); // 301, 302
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Add default menus if they don't exist (Safety check)
        // Note: Actual seeding logic should be in Seeder, but we ensure structure here.
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('redirects');
    }
};