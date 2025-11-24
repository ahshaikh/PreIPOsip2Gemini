<?php
// Split from: 2025_11_12_000400_create_cms_and_support_tables.php
// Table: user_legal_acceptances

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_legal_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('page_id')->constrained()->onDelete('cascade');
            $table->integer('page_version');
            $table->string('ip_address');
            $table->timestamps();
            $table->unique(['user_id', 'page_id', 'page_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_legal_acceptances');
    }
};
