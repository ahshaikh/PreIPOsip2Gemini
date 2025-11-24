<?php
// Split from: 2025_11_12_000400_create_cms_and_support_tables.php
// Table: pages (CMS)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
