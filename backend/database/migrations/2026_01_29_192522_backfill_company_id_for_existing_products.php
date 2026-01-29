<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This is a data migration to backfill the company_id on existing products.
     * It uses the oldest bulk purchase record for a product as the source of truth
     * for determining which company owned the product.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE products p
            JOIN (
                SELECT
                    bp.product_id,
                    bp.company_id
                FROM
                    bulk_purchases bp
                INNER JOIN (
                    SELECT
                        product_id,
                        MIN(id) as min_id
                    FROM
                        bulk_purchases
                    WHERE
                        company_id IS NOT NULL
                    GROUP BY
                        product_id
                ) as first_purchase
                ON bp.product_id = first_purchase.product_id AND bp.id = first_purchase.min_id
            ) as source
            ON p.id = source.product_id
            SET p.company_id = source.company_id
            WHERE p.company_id IS NULL;
        ");

        // REQUIRED FIX #2: Log any products that could not be mapped
        $unmappedProducts = DB::table('products')->whereNull('company_id')->get(['id', 'name']);

        if ($unmappedProducts->isNotEmpty()) {
            Log::warning('Backfill company_id: The following products could not be automatically mapped to a company because they have no associated bulk purchases with a company_id.', [
                'unmapped_products' => $unmappedProducts->toArray(),
                'count' => $unmappedProducts->count(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * This is a data migration, and rolling it back is not intended.
     * The `company_id` column would just become null again, which is not harmful.
     */
    public function down(): void
    {
        //
    }
};
