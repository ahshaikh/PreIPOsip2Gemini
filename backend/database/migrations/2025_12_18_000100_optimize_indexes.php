<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OptimizeIndexes extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // [AUDIT FIX]: Speeds up wallet history lookups
            $table->index(['user_id', 'created_at']); 
        });

        Schema::table('users', function (Blueprint $table) {
            // [AUDIT FIX]: Speeds up admin searches for KYC status
            $table->index('kyc_status');
        });
    }
}