<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bonus_transactions', function (Blueprint $table) {
    		$table->string('description')->default('')->change();
	});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
