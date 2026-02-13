<?php
// V-CONTRACT-HARDENING-FINAL: Single active override per plan per scope (PORTABLE)
// This migration enforces that only ONE active (non-revoked) override can exist
// per plan per scope at any time, using database-specific strategies:
// - MySQL: Generated column with unique constraint (NULL for revoked = no conflict)
// - PostgreSQL: Partial unique index with WHERE clause
// - SQLite: Partial unique index with WHERE clause
//
// This eliminates "most recent wins" ambiguity at the database level.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        match ($driver) {
            'mysql' => $this->createMySQLConstraint(),
            'pgsql' => $this->createPostgreSQLConstraint(),
            'sqlite' => $this->createSQLiteConstraint(),
            default => throw new \RuntimeException(
                "Unsupported database driver '{$driver}' for unique active override constraint. " .
                "Financial contract enforcement requires MySQL, PostgreSQL, or SQLite."
            ),
        };
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        match ($driver) {
            'mysql' => $this->dropMySQLConstraint(),
            'pgsql' => $this->dropPostgreSQLConstraint(),
            'sqlite' => $this->dropSQLiteConstraint(),
            default => null,
        };
    }

    /**
     * MySQL: Use generated column approach
     * MySQL doesn't support partial indexes, so we create a computed column
     * that is NULL when revoked (NULL values don't conflict in unique constraints)
     */
    private function createMySQLConstraint(): void
    {
        Schema::table('plan_regulatory_overrides', function (Blueprint $table) {
            $table->string('active_scope_key', 100)
                ->nullable()
                ->virtualAs("CASE WHEN revoked_at IS NULL THEN CONCAT(plan_id, ':', override_scope) ELSE NULL END")
                ->after('override_scope')
                ->comment('V-CONTRACT-HARDENING-FINAL: Generated column for unique active override enforcement');

            $table->unique('active_scope_key', 'unique_active_override_per_plan_scope');
        });
    }

    /**
     * PostgreSQL: Use native partial unique index
     * PostgreSQL supports WHERE clauses on unique indexes directly
     */
    private function createPostgreSQLConstraint(): void
    {
        DB::statement("
            CREATE UNIQUE INDEX unique_active_override_per_plan_scope
            ON plan_regulatory_overrides (plan_id, override_scope)
            WHERE revoked_at IS NULL
        ");
    }

    /**
     * SQLite: Use native partial unique index
     * SQLite also supports WHERE clauses on unique indexes
     */
    private function createSQLiteConstraint(): void
    {
        DB::statement("
            CREATE UNIQUE INDEX unique_active_override_per_plan_scope
            ON plan_regulatory_overrides (plan_id, override_scope)
            WHERE revoked_at IS NULL
        ");
    }

    private function dropMySQLConstraint(): void
    {
        Schema::table('plan_regulatory_overrides', function (Blueprint $table) {
            $table->dropUnique('unique_active_override_per_plan_scope');
            $table->dropColumn('active_scope_key');
        });
    }

    private function dropPostgreSQLConstraint(): void
    {
        DB::statement('DROP INDEX IF EXISTS unique_active_override_per_plan_scope');
    }

    private function dropSQLiteConstraint(): void
    {
        DB::statement('DROP INDEX IF EXISTS unique_active_override_per_plan_scope');
    }
};
