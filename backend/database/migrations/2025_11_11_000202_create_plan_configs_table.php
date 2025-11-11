// V-PHASE2-1730-026
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This holds the logic for the "Configurable Logic Builder".
     */
    public function up(): void
    {
        Schema::create('plan_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('config_key'); // e.g., 'progressive_rate', 'milestones', 'referral_tiers'
            $table->json('value'); // Store complex rules as JSON
            $table->timestamps();
            
            $table->unique(['plan_id', 'config_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_configs');
    }
};