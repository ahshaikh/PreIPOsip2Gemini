<?php
// V-PRODUCT-ALLOCATION-1210 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Allocation Configuration
            $table->enum('allocation_method', ['auto', 'manual', 'hybrid'])->default('auto')->after('min_investment');
            $table->json('allocation_rules')->nullable()->after('allocation_method'); // Stores priority, limits, formulas
            $table->decimal('max_allocation_per_user', 15, 2)->nullable()->after('allocation_rules');
            $table->decimal('total_units_available', 15, 2)->nullable()->after('max_allocation_per_user');
            $table->decimal('units_allocated', 15, 2)->default(0)->after('total_units_available');
            $table->boolean('enable_waitlist')->default(false)->after('units_allocated');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'allocation_method',
                'allocation_rules',
                'max_allocation_per_user',
                'total_units_available',
                'units_allocated',
                'enable_waitlist'
            ]);
        });
    }
};
