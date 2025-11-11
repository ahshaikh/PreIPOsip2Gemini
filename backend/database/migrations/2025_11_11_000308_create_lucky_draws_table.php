<?php
// V-PHASE3-1730-069

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lucky_draws', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('draw_date');
            $table->json('prize_structure'); // e.g., [{"rank": 1, "count": 1, "amount": 50000}, ...]
            $table->string('status')->default('open'); // open, drawn, completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lucky_draws');
    }
};