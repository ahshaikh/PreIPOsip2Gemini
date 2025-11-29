<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_investments', function (Blueprint $table) {
            $table->timestamp('exited_at')->nullable()->after('allocated_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_investments', function (Blueprint $table) {
            $table->dropColumn('exited_at');
        });
    }
};
