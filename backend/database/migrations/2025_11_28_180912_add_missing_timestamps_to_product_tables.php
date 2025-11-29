<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $tables = [
            'products',
            'product_price_histories',
            'product_risk_disclosures',
            'product_key_metrics',
            'product_funding_rounds',
            'product_founders',
            'product_highlights',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) { // <-- prevents errors
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'created_at')) {
                        $table->timestamps();
                    }
                });
            }
        }
    }

    public function down()
    {
        $tables = [
            'products',
            'product_price_histories',
            'product_risk_disclosures',
            'product_key_metrics',
            'product_funding_rounds',
            'product_founders',
            'product_highlights',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropTimestamps();
                });
            }
        }
    }
};
