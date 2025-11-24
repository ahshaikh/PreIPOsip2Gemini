<?php
// V-FINAL-1730-648 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates tables for modules that were referenced but not yet backed by DB tables.
     */
    public function up(): void
    {
        // 1. Email Templates (FSD-NOTIF-002)
        if (!Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g. "Welcome Email"
                $table->string('slug')->unique(); // e.g. "user.welcome"
                $table->string('subject');
                $table->longText('body'); // HTML content
                $table->text('variables_available')->nullable(); // JSON or CSV of available vars
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. SMS Templates (FSD-NOTIF-008)
        if (!Schema::hasTable('sms_templates')) {
            Schema::create('sms_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('body'); // Max 160 chars ideally
                $table->string('dlt_template_id')->nullable(); // Compliance
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Blog Posts (FSD-FRONT-008)
        if (!Schema::hasTable('blog_posts')) {
            Schema::create('blog_posts', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('content');
                $table->string('excerpt')->nullable();
                $table->string('featured_image')->nullable();
                $table->string('status')->default('draft'); // draft, published
                $table->foreignId('author_id')->constrained('users');
                $table->timestamp('published_at')->nullable();
                $table->json('seo_meta')->nullable();
                $table->timestamps();
            });
        }

        // 4. FAQs (FSD-FRONT-007)
        if (!Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->id();
                $table->string('question');
                $table->text('answer');
                $table->string('category')->default('general');
                $table->integer('display_order')->default(0);
                $table->boolean('is_published')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('sms_templates');
        Schema::dropIfExists('email_templates');
    }
};