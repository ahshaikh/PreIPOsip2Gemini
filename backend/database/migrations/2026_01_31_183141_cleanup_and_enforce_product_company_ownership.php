<?php

     use Illuminate\Database\Migrations\Migration;
     use Illuminate\Database\Schema\Blueprint;
     use Illuminate\Support\Facades\Schema;
     use Illuminate\Support\Facades\DB;
     use Illuminate\Support\Facades\Log;
    
     /**
     * FINAL PHASE 1 AUDIT FIX: Clean up orphan products and enforce ownership.
     *
     * This migration ensures the product ownership invariant is enforced at the DB level.
     *
     * BEHAVIOR:
     * 1. Finds all products where company_id is NULL.
     * 2. Logs the IDs of these orphan products for audit purposes.
     * 3. DELETES the orphan products, as they are invalid data.
     * 4. Modifies the products.company_id column to be NOT NULL.
     * 5. Re-applies the foreign key constraint.
     */
    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            // STEP 1: Find and log orphan products for review
            // We use DB::table here to avoid model observers or boot methods during migration
            $orphanProductIds = DB::table('products')->whereNull('company_id')->pluck('id');
   
            if ($orphanProductIds->isNotEmpty()) {
                Log::warning('DB MIGRATION: Orphan products found and will be deleted before enforcing ownership.', [
                    'count' => $orphanProductIds->count(),
                    'product_ids' => $orphanProductIds->toArray(),
                ]);
   
                // STEP 2: Delete the orphan products
                DB::table('products')->whereNull('company_id')->delete();
            }
   
            // STEP 3: Now, safely enforce the NOT NULL constraint
            Schema::table('products', function (Blueprint $table) {
                // Drop foreign key to allow column modification
                // Note: Check driver for SQLite compatibility if needed, but assuming MySQL/PostgreSQL
                try {
                     $table->dropForeign(['company_id']);
                } catch (\Exception $e) {
                    Log::info("Could not drop foreign key 'products_company_id_foreign'. It may not exist, which is acceptable. Error: " . $e->getMessage());
                }
            });
   
            // Use a raw statement to modify the column to NOT NULL
            DB::statement('ALTER TABLE products MODIFY company_id BIGINT UNSIGNED NOT NULL');
   
            Schema::table('products', function (Blueprint $table) {
                // Re-add the foreign key constraint
                $table->foreign('company_id')
                      ->references('id')
                      ->on('companies')
                      ->onDelete('cascade');
            });
        }
   
        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            // This down migration is intentionally simple. Reverting to a nullable state
            // is a significant step backward and should be a conscious decision.
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });
   
            DB::statement('ALTER TABLE products MODIFY company_id BIGINT UNSIGNED NULL');
   
            Schema::table('products', function (Blueprint $table) {
                $table->foreign('company_id')
                      ->references('id')
                      ->on('companies')
                      ->onDelete('cascade');
            });
        }
    };