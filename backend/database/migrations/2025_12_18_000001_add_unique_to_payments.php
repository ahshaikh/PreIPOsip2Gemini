<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
	// [AUDIT FIX]: Enforce database-level idempotency
	Schema::table('payments', function (Blueprint $table) {
	    $table->string('gateway_payment_id')->unique()->change();
	});

    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
                'gateway_payment_id',
            );
      }
};
