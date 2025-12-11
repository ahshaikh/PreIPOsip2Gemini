<?php
// V-COMPANY-PRODUCT-INTEGRATION-1210 (Created)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add company_id to products table to link products with companies
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->after('company_id')->constrained('company_users')->onDelete('set null');
            $table->enum('approval_status', ['draft', 'pending_approval', 'approved', 'rejected'])->default('draft')->after('status');
            $table->text('rejection_reason')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('rejection_reason');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->onDelete('set null'); // Admin who approved
        });

        // Add product_id to deals if not exists (already exists based on Deal model)
        if (!Schema::hasColumn('deals', 'company_id')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('product_id')->constrained('companies')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'company_id',
                'created_by',
                'approval_status',
                'rejection_reason',
                'approved_at',
                'approved_by'
            ]);
        });

        if (Schema::hasColumn('deals', 'company_id')) {
            Schema::table('deals', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};
