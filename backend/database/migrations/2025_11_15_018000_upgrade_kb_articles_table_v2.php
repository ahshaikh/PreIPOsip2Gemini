<?php
// V-FINAL-1730-553 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-SUPPORT-011: Advanced Article Fields
     */
    public function up(): void
    {
        // 1. Add missing columns to articles table
        Schema::table('kb_articles', function (Blueprint $table) {
            $table->string('featured_image')->nullable()->after('content');
            $table->json('seo_meta')->nullable()->after('status');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->integer('helpful_yes')->default(0);
            $table->integer('helpful_no')->default(0);
        });

        // 2. Tags
        Schema::create('kb_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
        });

        // 3. Pivot table: article_tag
        Schema::create('kb_article_tag', function (Blueprint $table) {
            $table->primary(['kb_article_id', 'kb_tag_id']);
            $table->foreignId('kb_article_id')->constrained('kb_articles')->onDelete('cascade');
            $table->foreignId('kb_tag_id')->constrained('kb_tags')->onDelete('cascade');
        });
        
        // 4. Pivot table: related_articles
        Schema::create('kb_related_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('kb_articles')->onDelete('cascade');
            $table->foreignId('related_article_id')->constrained('kb_articles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_related_articles');
        Schema::dropIfExists('kb_article_tag');
        Schema::dropIfExists('kb_tags');
        Schema::table('kb_articles', function (Blueprint $table) {
            $table->dropColumn(['featured_image', 'seo_meta', 'published_at', 'helpful_yes', 'helpful_no']);
        });
    }
};