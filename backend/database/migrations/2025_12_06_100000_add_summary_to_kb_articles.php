<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('kb_articles', function (Blueprint $table) {
            // Check if column exists before adding to prevent errors if you ran it partially
            if (!Schema::hasColumn('kb_articles', 'summary')) {
                $table->text('summary')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('kb_articles', 'last_updated')) {
                $table->date('last_updated')->nullable()->after('summary');
            }
        });
    }

    public function down()
    {
        Schema::table('kb_articles', function (Blueprint $table) {
            $table->dropColumn(['summary', 'last_updated']);
        });
    }
};