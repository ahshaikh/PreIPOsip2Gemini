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
        // Promotional materials table
        Schema::create('promotional_materials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category'); // banners, videos, documents, social, presentations
            $table->string('type'); // image, video, document
            $table->string('file_url');
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // in bytes
            $table->string('thumbnail_url')->nullable();
            $table->string('preview_url')->nullable();
            $table->string('dimensions')->nullable(); // e.g., "1920x1080"
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('type');
            $table->index('is_active');
        });

        // Promotional material downloads tracking
        Schema::create('promotional_material_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('promotional_material_id')->constrained()->onDelete('cascade');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('promotional_material_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotional_material_downloads');
        Schema::dropIfExists('promotional_materials');
    }
};
