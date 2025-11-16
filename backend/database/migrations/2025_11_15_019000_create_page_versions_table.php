<?php
// V-FINAL-1730-557 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * FSD-LEGAL-001: Version Control
     */
    public function up(): void
    {
        // 1. This table stores the history of changes
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

        // 2. Upgrade the main 'pages' table
        Schema::table('pages', function (Blueprint $table) {
            $table->integer('current_version')->default(1)->after('status');
            $table->boolean('require_user_acceptance')->default(false)->after('current_version');
        });
        
        // 3. This table tracks which user accepted which version
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
        Schema::dropIfExists('page_versions');
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['current_version', 'require_user_acceptance']);
        });
    }
};