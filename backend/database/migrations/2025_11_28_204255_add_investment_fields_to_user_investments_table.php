<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_investments', function (Blueprint $table) {
            if (!Schema::hasColumn('user_investments', 'shares')) {
                $table->integer('shares')->nullable();
            }

            if (!Schema::hasColumn('user_investments', 'price_per_share')) {
                $table->decimal('price_per_share', 10, 2)->nullable();
            }

            if (!Schema::hasColumn('user_investments', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->nullable();
            }

            if (!Schema::hasColumn('user_investments', 'source')) {
                $table->string('source')->default('sip');
            }

            if (!Schema::hasColumn('user_investments', 'status')) {
                $table->string('status')->default('active');
            }

            if (!Schema::hasColumn('user_investments', 'allocated_at')) {
                $table->timestamp('allocated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_investments', function (Blueprint $table) {
            $table->dropColumn([
                'shares',
                'price_per_share',
                'total_amount',
                'source',
                'status',
                'allocated_at',
            ]);
        });
    }
};
