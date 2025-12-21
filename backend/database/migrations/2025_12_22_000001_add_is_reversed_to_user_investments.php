<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_investments', function (Blueprint $table) {
            if (!Schema::hasColumn('user_investments', 'is_reversed')) {
                $table->boolean('is_reversed')->default(false)->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('user_investments', function (Blueprint $table) {
            if (Schema::hasColumn('user_investments', 'is_reversed')) {
                $table->dropColumn('is_reversed');
            }
        });
    }
};