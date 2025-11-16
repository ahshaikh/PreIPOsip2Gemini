<?php
// V-DEPLOY-1730-018 (Created) |  V-REMEDIATE-1730-145 | V-FINAL-1730-618 (Consolidated)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Support System
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ticket_code')->unique();
            $table->string('subject');
            $table->string('category');
            $table->string('priority')->default('medium');
            $table->string('status')->default('open');
            $table->integer('sla_hours')->default(24);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('rating_feedback')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Author
            $table->boolean('is_admin_reply')->default(false);
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->timestamps();
        });

        Schema::create('canned_responses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // CMS Pages
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->json('seo_meta')->nullable();
            $table->string('status')->default('draft');
            $table->integer('current_version')->default(1);
            $table->boolean('require_user_acceptance')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('page_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->onDelete('cascade');
            $table->foreignId('author_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('version')->default(1);
            $table->string('title');
            $table->longText('content');
            $table->string('change_summary')->nullable();
            $table->timestamps();
        });

        Schema::create('user_legal_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('page_id')->constrained()->onDelete('cascade');
            $table->integer('page_version');
            $table->string('ip_address');
            $table->timestamps();
            $table->unique(['user_id', 'page_id', 'page_version']);
        });

        // CMS Marketing
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('variant_of')->nullable();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('link_url')->nullable();
            $table->string('type')->default('top_bar');
            $table->string('trigger_type')->default('load');
            $table->integer('trigger_value')->default(0);
            $table->string('frequency')->default('always');
            $table->json('targeting_rules')->nullable();
            $table->json('style_config')->nullable();
            $table->integer('display_weight')->default(1);
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_url')->unique();
            $table->string('to_url');
            $table->integer('status_code')->default(301);
            $table->unsignedBigInteger('hit_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // CMS Knowledge Base
        Schema::create('kb_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('kb_categories')->onDelete('set null');
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kb_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kb_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users')->onDelete('restrict');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->string('featured_image')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->json('seo_meta')->nullable();
            $table->integer('views')->default(0);
            $table->integer('helpful_yes')->default(0);
            $table->integer('helpful_no')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_articles');
        Schema::dropIfExists('kb_categories');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('user_legal_acceptances');
        Schema::dropIfExists('page_versions');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('canned_responses');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
    }
};