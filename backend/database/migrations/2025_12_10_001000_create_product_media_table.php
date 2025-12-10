<?php
// V-PRODUCT-MEDIA-1210 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('media_type', ['image', 'video'])->default('image');
            $table->string('url'); // URL or path to media file
            $table->string('thumbnail_url')->nullable(); // Thumbnail for videos
            $table->string('title')->nullable();
            $table->text('caption')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_primary')->default(false); // Mark primary image
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
