<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('legal_agreement_versions', function (Blueprint $table) {
            // Adding the missing column
            $table->string('content_hash', 64)->nullable()->after('legal_agreement_id');
        });
    }

    public function down()
    {
        Schema::table('legal_agreement_versions', function (Blueprint $table) {
            $table->dropColumn('content_hash');
        });
    }
};