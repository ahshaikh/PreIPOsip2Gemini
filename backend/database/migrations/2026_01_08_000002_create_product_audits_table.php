<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Product Audit Trail (FIX 48)
     *
     * Purpose:
     * - Track all changes to products with complete audit trail
     * - Monitor critical changes (price, status, compliance fields)
     * - Enable compliance reporting and change history analysis
     * - Support rollback and change comparison
     */
    public function up(): void
    {
        Schema::create('product_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // Action tracking
            $table->enum('action', [
                'created',
                'updated',
                'activated',
                'deactivated',
                'price_updated',
                'compliance_updated',
                'deleted',
                'restored'
            ])->index();

            // Change tracking
            $table->json('changed_fields')->nullable()->comment('Array of field names that changed');
            $table->json('old_values')->nullable()->comment('Previous values of changed fields');
            $table->json('new_values')->nullable()->comment('New values of changed fields');
            $table->text('change_description')->nullable()->comment('Human-readable description of changes');

            // Critical change marker
            $table->boolean('is_critical')->default(false)->comment('Marks critical changes (price, status, SEBI approval, etc.)');
            $table->json('critical_fields')->nullable()->comment('Array of critical fields that changed');

            // User tracking
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('performed_by_type')->default('user')->comment('user, system, admin, api');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Request context
            $table->string('request_id')->nullable()->comment('UUID for request tracing');
            $table->text('request_url')->nullable();
            $table->string('request_method', 10)->nullable();

            // Compliance & metadata
            $table->text('compliance_notes')->nullable()->comment('Notes for compliance tracking');
            $table->json('metadata')->nullable()->comment('Additional contextual data');

            $table->timestamps();

        });

        // Add indexes separately to check for duplicates
        $this->addIndexIfNotExists('product_audits', 'product_id', 'product_audits_product_id_index');
        $this->addIndexIfNotExists('product_audits', 'action', 'product_audits_action_index');
        $this->addIndexIfNotExists('product_audits', 'is_critical', 'product_audits_is_critical_index');
        $this->addIndexIfNotExists('product_audits', 'performed_by', 'product_audits_performed_by_index');
        $this->addIndexIfNotExists('product_audits', 'created_at', 'product_audits_created_at_index');
        $this->addCompositeIndexIfNotExists('product_audits', ['product_id', 'created_at'], 'product_audits_product_id_created_at_index');
        $this->addCompositeIndexIfNotExists('product_audits', ['product_id', 'action'], 'product_audits_product_id_action_index');
        $this->addCompositeIndexIfNotExists('product_audits', ['is_critical', 'created_at'], 'product_audits_is_critical_created_at_index');
    }

    /**
     * Add index if it doesn't exist
     */
    private function addIndexIfNotExists(string $table, string $column, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $indexName) {
                $blueprint->index($column, $indexName);
            });
        }
    }

    /**
     * Add composite index if it doesn't exist
     */
    private function addCompositeIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                $blueprint->index($columns, $indexName);
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $query = "SELECT COUNT(*) as count FROM information_schema.statistics
                  WHERE table_schema = ? AND table_name = ? AND index_name = ?";

        $result = $connection->selectOne($query, [$databaseName, $table, $index]);

        return $result->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_audits');
    }
};
