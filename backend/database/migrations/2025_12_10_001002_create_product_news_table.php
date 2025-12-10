<?php
// V-PRODUCT-NEWS-1210 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('summary')->nullable(); // Short summary for listing
            $table->longText('content'); // Full article content
            $table->string('author')->nullable();
            $table->string('source_url')->nullable(); // External source link
            $table->string('thumbnail_url')->nullable(); // Article thumbnail
            $table->date('published_date');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_news');
    }
};
