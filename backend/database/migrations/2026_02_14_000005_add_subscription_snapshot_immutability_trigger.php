<?php
// V-CONTRACT-HARDENING-FINAL: DB-level snapshot immutability enforcement
// This trigger prevents ANY modification of snapshot fields after config_snapshot_at is set.
// Applies to: raw SQL, mass updates, Eloquent bypasses, direct DB access.
// IMMUTABILITY IS IMPOSSIBLE TO BYPASS.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Immutable snapshot fields that cannot be modified after snapshot creation.
     */
    private const IMMUTABLE_FIELDS = [
        'progressive_config',
        'milestone_config',
        'consistency_config',
        'welcome_bonus_config',
        'referral_tiers',
        'celebration_bonus_config',
        'lucky_draw_entries',
        'config_snapshot_version',
        'config_snapshot_at',
    ];

    public function up(): void
    {
        $driver = DB::getDriverName();

        match ($driver) {
            'mysql' => $this->createMySQLTrigger(),
            'pgsql' => $this->createPostgreSQLTrigger(),
            'sqlite' => $this->createSQLiteTrigger(),
            default => throw new \RuntimeException(
                "Unsupported database driver '{$driver}' for immutability trigger. " .
                "Financial contract enforcement requires MySQL, PostgreSQL, or SQLite."
            ),
        };
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        match ($driver) {
            'mysql' => DB::unprepared('DROP TRIGGER IF EXISTS enforce_subscription_snapshot_immutability'),
            'pgsql' => DB::unprepared('DROP TRIGGER IF EXISTS enforce_subscription_snapshot_immutability ON subscriptions; DROP FUNCTION IF EXISTS check_subscription_snapshot_immutability()'),
            'sqlite' => DB::unprepared('DROP TRIGGER IF EXISTS enforce_subscription_snapshot_immutability'),
            default => null,
        };
    }

    /**
     * MySQL trigger implementation
     */
    private function createMySQLTrigger(): void
    {
        $fieldChecks = $this->generateMySQLFieldChecks();

        DB::unprepared("
            DROP TRIGGER IF EXISTS enforce_subscription_snapshot_immutability;
        ");

        DB::unprepared("
            CREATE TRIGGER enforce_subscription_snapshot_immutability
            BEFORE UPDATE ON subscriptions
            FOR EACH ROW
            BEGIN
                -- Only enforce if snapshot was previously set (not initial creation)
                IF OLD.config_snapshot_at IS NOT NULL THEN
                    {$fieldChecks}
                END IF;
            END
        ");
    }

    /**
     * Generate MySQL field check conditions
     */
    private function generateMySQLFieldChecks(): string
    {
        $checks = [];

        foreach (self::IMMUTABLE_FIELDS as $field) {
            // Use JSON comparison for JSON fields, direct comparison for others
            if (in_array($field, ['config_snapshot_at', 'config_snapshot_version'])) {
                $checks[] = "
                    IF NOT (OLD.{$field} <=> NEW.{$field}) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Cannot modify {$field} after snapshot creation. Subscription snapshots are immutable contracts.';
                    END IF;
                ";
            } else {
                // JSON fields - compare as text to handle NULL safely
                $checks[] = "
                    IF NOT (COALESCE(CAST(OLD.{$field} AS CHAR), '') <=> COALESCE(CAST(NEW.{$field} AS CHAR), '')) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Cannot modify {$field} after snapshot creation. Subscription snapshots are immutable contracts.';
                    END IF;
                ";
            }
        }

        return implode("\n", $checks);
    }

    /**
     * PostgreSQL trigger implementation
     */
    private function createPostgreSQLTrigger(): void
    {
        $fieldChecks = $this->generatePostgreSQLFieldChecks();

        DB::unprepared("
            CREATE OR REPLACE FUNCTION check_subscription_snapshot_immutability()
            RETURNS TRIGGER AS \$\$
            BEGIN
                -- Only enforce if snapshot was previously set
                IF OLD.config_snapshot_at IS NOT NULL THEN
                    {$fieldChecks}
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        DB::unprepared("
            DROP TRIGGER IF EXISTS enforce_subscription_snapshot_immutability ON subscriptions;
            CREATE TRIGGER enforce_subscription_snapshot_immutability
            BEFORE UPDATE ON subscriptions
            FOR EACH ROW
            EXECUTE FUNCTION check_subscription_snapshot_immutability();
        ");
    }

    /**
     * Generate PostgreSQL field check conditions
     */
    private function generatePostgreSQLFieldChecks(): string
    {
        $checks = [];

        foreach (self::IMMUTABLE_FIELDS as $field) {
            $checks[] = "
                IF OLD.{$field} IS DISTINCT FROM NEW.{$field} THEN
                    RAISE EXCEPTION 'IMMUTABILITY VIOLATION: Cannot modify {$field} after snapshot creation. Subscription snapshots are immutable contracts.';
                END IF;
            ";
        }

        return implode("\n", $checks);
    }

    /**
     * SQLite trigger implementation (for testing)
     */
    private function createSQLiteTrigger(): void
    {
        // SQLite requires separate triggers and uses RAISE for errors
        // We'll check each field and abort if any immutable field is modified

        $conditions = [];
        foreach (self::IMMUTABLE_FIELDS as $field) {
            $conditions[] = "(OLD.{$field} IS NOT NEW.{$field} OR (OLD.{$field} IS NULL) != (NEW.{$field} IS NULL))";
        }
        $conditionString = implode(' OR ', $conditions);

        DB::unprepared("DROP TRIGGER IF EXISTS enforce_subscription_snapshot_immutability");

        DB::unprepared("
            CREATE TRIGGER enforce_subscription_snapshot_immutability
            BEFORE UPDATE ON subscriptions
            FOR EACH ROW
            WHEN OLD.config_snapshot_at IS NOT NULL AND ({$conditionString})
            BEGIN
                SELECT RAISE(ABORT, 'IMMUTABILITY VIOLATION: Cannot modify snapshot fields after creation. Subscription snapshots are immutable contracts.');
            END
        ");
    }
};
