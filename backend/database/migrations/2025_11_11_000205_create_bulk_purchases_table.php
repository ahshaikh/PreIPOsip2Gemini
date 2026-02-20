<?php
// V-PHASE2-1730-029 (Canonicalized)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Core inventory table for bulk pre-IPO purchases.
     *
     * INVARIANTS (Enforced in Domain Layer):
     * - value_remaining >= 0
     * - value_remaining <= total_value_received
     * - total_value_received = face_value_purchased * (1 + extra_allocation_percentage / 100)
     *
     * Allocation Strategy:
     * FIFO allocation using:
     *   WHERE product_id = ?
     *   AND value_remaining > 0
     *   ORDER BY created_at ASC
     *   FOR UPDATE
     */
    public function up(): void
    {
        Schema::create('bulk_purchases', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('admin_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Financial fields
            $table->decimal('face_value_purchased', 14, 2);
            $table->decimal('actual_cost_paid', 14, 2);
            $table->decimal('discount_percentage', 5, 2);
            $table->decimal('extra_allocation_percentage', 5, 2);

            // Derived inventory values
            $table->decimal('total_value_received', 14, 2);
            $table->decimal('value_remaining', 14, 2);

            // Metadata
            $table->string('seller_name')->nullable();
            $table->date('purchase_date');
            $table->text('notes')->nullable();

            $table->timestamps();

            /**
             * Composite index for allocation queries.
             *
             * Optimizes:
             *   WHERE product_id = ?
             *   AND value_remaining > 0
             *   ORDER BY created_at ASC
             *
             * Leftmost prefix rule allows:
             * - product_id lookups
             * - product_id + value_remaining filtering
             * - product_id + value_remaining + created_at ordering
             */
            $table->index(['product_id', 'value_remaining', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_purchases');
    }
};
