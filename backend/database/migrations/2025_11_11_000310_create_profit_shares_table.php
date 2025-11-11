// V-PHASE3-1730-071
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_shares', function (Blueprint $table) {
            $table->id();
            $table->string('period_name'); // e.g., "Q4 2025"
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('net_profit', 14, 2);
            $table->decimal('total_pool', 14, 2); // Amount to be distributed
            $table->string('status')->default('pending'); // pending, calculated, distributed
            $table->foreignId('admin_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_shares');
    }
};