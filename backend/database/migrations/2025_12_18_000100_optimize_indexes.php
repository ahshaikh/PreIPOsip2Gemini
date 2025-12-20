<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OptimizeIndexes extends Migration
{
    public function up(): void
    {
        // transactions(user_id, created_at)
        if (! $this->indexExists('transactions', 'transactions_user_id_created_at_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(
                    ['user_id', 'created_at'],
                    'transactions_user_id_created_at_index'
                );
            });
        }

        // user_kyc(status)
        if (! $this->indexExists('user_kyc', 'user_kyc_status_index')) {
            Schema::table('user_kyc', function (Blueprint $table) {
                $table->index('status', 'user_kyc_status_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_id_created_at_index');
        });

        Schema::table('user_kyc', function (Blueprint $table) {
            $table->dropIndex('user_kyc_status_index');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
}
