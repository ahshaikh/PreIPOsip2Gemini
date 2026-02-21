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
        // Content Categories for organizing content
        Schema::create('content_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Pre-IPO Listing", "Insights"
            $table->string('slug')->unique(); // e.g., "pre-ipo-listing", "insights"
            $table->string('type'); // 'menu' or 'section'
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Sub-categories for menu items
        Schema::create('content_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('content_categories')->onDelete('cascade');
            $table->string('name'); // e.g., "Live Deals", "Market Analysis"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Content Items - flexible table for all types of content
        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcategory_id')->constrained('content_subcategories')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('thumbnail')->nullable();

            // Metadata for different content types
            $table->json('metadata')->nullable(); // Flexible field for storing custom data

            // SEO fields
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            // Publishing controls
            $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Tracking
            $table->integer('views_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);

            // Author
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('published_at');
            $table->index('is_featured');
        });

        // Deals table for Pre-IPO deals management
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('company_name');
            $table->string('company_logo')->nullable();
            $table->string('sector');

            // Deal details
            $table->enum('deal_type', ['live', 'upcoming', 'closed'])->default('upcoming');
            $table->decimal('min_investment', 15, 2)->nullable();
            $table->decimal('max_investment', 15, 2)->nullable();
            $table->decimal('valuation', 20, 2)->nullable();
            $table->string('valuation_currency', 3)->default('INR');
            $table->decimal('share_price', 10, 2)->nullable();
            $table->integer('total_shares')->nullable();
            $table->integer('available_shares')->nullable();

            // Timeline
            $table->timestamp('deal_opens_at')->nullable();
            $table->timestamp('deal_closes_at')->nullable();
            $table->integer('days_remaining')->nullable();

            // Additional info
            $table->json('highlights')->nullable(); // Array of key highlights
            $table->json('documents')->nullable(); // Array of document URLs
            $table->string('video_url')->nullable();

            // Status
            $table->enum('status', ['draft', 'active', 'paused', 'closed'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('deal_type');
            $table->index('status');
            $table->index('sector');
        });

        // Companies directory
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->string('sector');
            $table->string('founded_year')->nullable();
            $table->string('headquarters')->nullable();
            $table->string('ceo_name')->nullable();
            $table->integer('employees_count')->nullable();

            // Financial info
            $table->decimal('latest_valuation', 20, 2)->nullable();
            $table->string('funding_stage')->nullable(); // Seed, Series A, B, C, etc.
            $table->decimal('total_funding', 20, 2)->nullable();

            // Social links
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('facebook_url')->nullable();

            // Metadata
            $table->json('key_metrics')->nullable();
            $table->json('investors')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');

            // CANONICAL: disclosure_tier for company visibility tiers
            $table->enum('disclosure_tier', [
                'tier_0_pending',
                'tier_1_upcoming',
                'tier_2_live',
                'tier_3_featured'
            ])->default('tier_0_pending')->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index('sector');
            $table->index('status');
        });

        // Sectors/Industries
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->integer('companies_count')->default(0);
            $table->integer('deals_count')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Reports/Documents
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['market_analysis', 'research', 'white_paper', 'case_study', 'guide'])->default('research');
            $table->string('file_path'); // Path to PDF/document
            $table->string('cover_image')->nullable();
            $table->integer('file_size')->nullable(); // in KB
            $table->integer('pages')->nullable();

            // Access control
            $table->enum('access_level', ['public', 'registered', 'premium', 'admin'])->default('registered');
            $table->boolean('requires_subscription')->default(false);

            // Metadata
            $table->string('author')->nullable();
            $table->timestamp('published_date')->nullable();
            $table->json('tags')->nullable();
            $table->integer('downloads_count')->default(0);
            $table->decimal('rating', 3, 2)->nullable();

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('sectors');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('content_items');
        Schema::dropIfExists('content_subcategories');
        Schema::dropIfExists('content_categories');
    }
};
