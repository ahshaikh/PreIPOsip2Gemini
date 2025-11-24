<?php
// Split from: 2025_11_12_000400_create_cms_and_support_tables.php
// Table: banners (CMS Marketing)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
