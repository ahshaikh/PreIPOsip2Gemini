<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('bank_ifsc');
        });
    }

    public function down(): void
    {
        Schema::table('user_kyc', function (Blueprint $table) {
            $table->dropColumn('bank_name');
        });
    }
};
