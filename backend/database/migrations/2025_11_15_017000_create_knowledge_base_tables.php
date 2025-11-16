<?php
// V-FINAL-1730-549 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-SUPPORT-010: Knowledge Base Categories & Articles
     */
    public function up(): void
    {
        // 1. Categories (FSD-SUPPORT-010)
        Schema::create('kb_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            
            // For nesting (e.g., "Getting Started" > "KYC")
            $table->foreignId('parent_id')->nullable()->constrained('kb_categories')->onDelete('set null');
            
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Articles (FSD-SUPPORT-011)
        Schema::create('kb_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kb_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users')->onDelete('restrict');
            
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content'); // Rich text/markdown
            
            $table->string('status')->default('draft'); // draft, published
            $table->integer('views')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_articles');
        Schema::dropIfExists('kb_categories');
    }
};