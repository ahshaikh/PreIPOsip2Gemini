<?php
// V-PHASE2-1730-035

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., "new-homepage"
            $table->string('name'); // e.g., "New Homepage Design"
	    $table->text('description')->nullable();      // <-- ADD THIS
            $table->boolean('is_enabled')->default(false);
            $table->integer('rollout_percentage')->default(0);
            $table->json('target_users')->nullable(); // Store array of user IDs
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};