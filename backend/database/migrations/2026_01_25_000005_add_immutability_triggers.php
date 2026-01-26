<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P0 FIX (GAP 32 & 33): DB-Level Immutability Triggers
 *
 * PURPOSE:
 * Enforce immutability at the database level, preventing bypass via direct SQL.
 * Application-level immutability (model boot methods) can be bypassed with raw queries.
 * These triggers provide defense-in-depth.
 *
 * TABLES PROTECTED:
 * - investor_journey_transitions: Audit trail of journey state changes
 * - journey_acknowledgement_bindings: Proof of what investor acknowledged
 * - share_allocation_logs: Proof of share allocation
 * - platform_context_snapshots: Proof of platform state (when locked)
 *
 * STATE MACHINE ENFORCEMENT:
 * - company_investments: Prevent invalid status transitions
 * - investor_journeys: Prevent invalid state transitions
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // GAP 32: IMMUTABILITY TRIGGERS
        // Prevent UPDATE and DELETE on audit trail tables
        // =====================================================================

        // 1. investor_journey_transitions - IMMUTABLE
        if (Schema::hasTable('investor_journey_transitions')) {
            DB::unprepared("
                DROP TRIGGER IF EXISTS prevent_journey_transition_update;
                CREATE TRIGGER prevent_journey_transition_update
                BEFORE UPDATE ON investor_journey_transitions
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: investor_journey_transitions cannot be updated. Create a new record instead.';
                END;
            ");

            DB::unprepared("
                DROP TRIGGER IF EXISTS prevent_journey_transition_delete;
                CREATE TRIGGER prevent_journey_transition_delete
                BEFORE DELETE ON investor_journey_transitions
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: investor_journey_transitions cannot be deleted. Records are permanent for audit purposes.';
                END;
            ");
        }

        // 2. journey_acknowledgement_bindings - IMMUTABLE
        if (Schema::hasTable('journey_acknowledgement_bindings')) {
            DB::unprepared("
                DROP TRIGGER IF EXISTS prevent_ack_binding_update;
                CREATE TRIGGER prevent_ack_binding_update
                BEFORE UPDATE ON journey_acknowledgement_bindings
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: journey_acknowledgement_bindings cannot be updated. Acknowledgements are permanent records.';
                END;
            ");

            DB::unprepared("
                DROP TRIGGER IF EXISTS prevent_ack_binding_delete;
                CREATE TRIGGER prevent_ack_binding_delete
                BEFORE DELETE ON journey_acknowledgement_bindings
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: journey_acknowledgement_bindings cannot be deleted. Acknowledgements are permanent records.';
                END;
            ");
        }

        // 3. share_allocation_logs - IMMUTABLE (except reversal marking)
        if (Schema::hasTable('share_allocation_logs')) {
            DB::unprepared("
                DROP TRIGGER IF EXISTS prevent_allocation_log_update;
                CREATE TRIGGER prevent_allocation_log_update
                BEFORE UPDATE ON share_allocation_logs
                FOR EACH ROW
                BEGIN
                    -- Allow only reversal marking (is_reversed and reversal fields)
                    IF OLD.value_allocated != NEW.value_allocated
                       OR OLD.bulk_purchase_id != NEW.bulk_purchase_id
                       OR OLD.allocatable_id != NEW.allocatable_id
                       OR OLD.inventory_before != NEW.inventory_before
                       OR OLD.inventory_after != NEW.inventory_after
                    THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Core fields of share_allocation_logs cannot be updated. Only reversal marking is allowed.';
                    END IF;
                END;
            ");

            DB::unprepared("
                DROP TRIGGER IF EXISTS prevent_allocation_log_delete;
                CREATE TRIGGER prevent_allocation_log_delete
                BEFORE DELETE ON share_allocation_logs
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: share_allocation_logs cannot be deleted. Mark as reversed instead.';
                END;
            ");
        }

        // 4. platform_context_snapshots - IMMUTABLE when locked
        if (Schema::hasTable('platform_context_snapshots')) {
            DB::unprepared("
                DROP TRIGGER IF EXISTS prevent_locked_snapshot_update;
                CREATE TRIGGER prevent_locked_snapshot_update
                BEFORE UPDATE ON platform_context_snapshots
                FOR EACH ROW
                BEGIN
                    -- Allow updating is_current and valid_until (for superseding)
                    -- Block all other updates if locked
                    IF OLD.is_locked = 1 THEN
                        IF OLD.lifecycle_state != NEW.lifecycle_state
                           OR OLD.buying_enabled != NEW.buying_enabled
                           OR OLD.risk_level != NEW.risk_level
                           OR OLD.compliance_score != NEW.compliance_score
                           OR OLD.full_context_data != NEW.full_context_data
                        THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Locked platform_context_snapshots cannot have core fields updated.';
                        END IF;
                    END IF;
                END;
            ");

            DB::unprepared("
                DROP TRIGGER IF EXISTS prevent_snapshot_delete;
                CREATE TRIGGER prevent_snapshot_delete
                BEFORE DELETE ON platform_context_snapshots
                FOR EACH ROW
                BEGIN
                    IF OLD.is_locked = 1 THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Locked platform_context_snapshots cannot be deleted.';
                    END IF;
                END;
            ");
        }

        // =====================================================================
        // GAP 33: STATE MACHINE ENFORCEMENT TRIGGERS
        // Prevent invalid state transitions
        // =====================================================================

        // 5. investor_journeys - Valid state transitions only
        if (Schema::hasTable('investor_journeys')) {
            DB::unprepared("
                DROP TRIGGER IF EXISTS enforce_journey_state_machine;
                CREATE TRIGGER enforce_journey_state_machine
                BEFORE UPDATE ON investor_journeys
                FOR EACH ROW
                BEGIN
                    DECLARE valid_transition INT DEFAULT 0;

                    -- Define valid transitions
                    -- initiated -> viewing
                    -- viewing -> acknowledging
                    -- acknowledging -> reviewing
                    -- reviewing -> confirming
                    -- confirming -> processing
                    -- processing -> invested
                    -- ANY -> blocked (always allowed)
                    -- ANY -> abandoned (always allowed)

                    IF NEW.current_state = OLD.current_state THEN
                        -- No state change, allow
                        SET valid_transition = 1;
                    ELSEIF NEW.current_state IN ('blocked', 'abandoned') THEN
                        -- Emergency exits always allowed
                        SET valid_transition = 1;
                    ELSEIF OLD.current_state = 'initiated' AND NEW.current_state = 'viewing' THEN
                        SET valid_transition = 1;
                    ELSEIF OLD.current_state = 'viewing' AND NEW.current_state = 'acknowledging' THEN
                        SET valid_transition = 1;
                    ELSEIF OLD.current_state = 'acknowledging' AND NEW.current_state = 'reviewing' THEN
                        SET valid_transition = 1;
                    ELSEIF OLD.current_state = 'reviewing' AND NEW.current_state = 'confirming' THEN
                        SET valid_transition = 1;
                    ELSEIF OLD.current_state = 'confirming' AND NEW.current_state = 'processing' THEN
                        SET valid_transition = 1;
                    ELSEIF OLD.current_state = 'processing' AND NEW.current_state = 'invested' THEN
                        SET valid_transition = 1;
                    END IF;

                    -- Terminal states cannot transition
                    IF OLD.current_state IN ('invested', 'blocked', 'abandoned') AND OLD.current_state != NEW.current_state THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'STATE MACHINE VIOLATION: Cannot transition from terminal state.';
                    END IF;

                    IF valid_transition = 0 THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'STATE MACHINE VIOLATION: Invalid journey state transition. States must follow sequence.';
                    END IF;
                END;
            ");
        }

        // 6. company_investments - Valid status transitions only
        if (Schema::hasTable('company_investments')) {
            DB::unprepared("
                DROP TRIGGER IF EXISTS enforce_investment_status_machine;
                CREATE TRIGGER enforce_investment_status_machine
                BEFORE UPDATE ON company_investments
                FOR EACH ROW
                BEGIN
                    DECLARE valid_transition INT DEFAULT 0;

                    -- Define valid transitions
                    -- pending -> processing, cancelled
                    -- processing -> completed, failed
                    -- completed -> (no transitions, terminal)
                    -- failed -> pending (retry allowed)
                    -- cancelled -> (no transitions, terminal)

                    IF NEW.status = OLD.status THEN
                        SET valid_transition = 1;
                    ELSEIF OLD.status = 'pending' AND NEW.status IN ('processing', 'cancelled') THEN
                        SET valid_transition = 1;
                    ELSEIF OLD.status = 'processing' AND NEW.status IN ('completed', 'failed') THEN
                        SET valid_transition = 1;
                    ELSEIF OLD.status = 'failed' AND NEW.status = 'pending' THEN
                        SET valid_transition = 1;
                    END IF;

                    IF valid_transition = 0 THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'STATE MACHINE VIOLATION: Invalid investment status transition.';
                    END IF;
                END;
            ");
        }

        // Log trigger creation (only if audit_trails table exists)
        if (Schema::hasTable('audit_trails')) {
            DB::table('audit_trails')->insert([
                'auditable_type' => 'system',
                'auditable_id' => 0,
                'action' => 'create_immutability_triggers',
                'changes' => json_encode([
                    'triggers_created' => [
                        'prevent_journey_transition_update',
                        'prevent_journey_transition_delete',
                        'prevent_ack_binding_update',
                        'prevent_ack_binding_delete',
                        'prevent_allocation_log_update',
                        'prevent_allocation_log_delete',
                        'prevent_locked_snapshot_update',
                        'prevent_snapshot_delete',
                        'enforce_journey_state_machine',
                        'enforce_investment_status_machine',
                    ],
                ]),
                'ip_address' => 'migration',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Remove all triggers
        DB::unprepared("DROP TRIGGER IF EXISTS prevent_journey_transition_update;");
        DB::unprepared("DROP TRIGGER IF EXISTS prevent_journey_transition_delete;");
        DB::unprepared("DROP TRIGGER IF EXISTS prevent_ack_binding_update;");
        DB::unprepared("DROP TRIGGER IF EXISTS prevent_ack_binding_delete;");
        DB::unprepared("DROP TRIGGER IF EXISTS prevent_allocation_log_update;");
        DB::unprepared("DROP TRIGGER IF EXISTS prevent_allocation_log_delete;");
        DB::unprepared("DROP TRIGGER IF EXISTS prevent_locked_snapshot_update;");
        DB::unprepared("DROP TRIGGER IF EXISTS prevent_snapshot_delete;");
        DB::unprepared("DROP TRIGGER IF EXISTS enforce_journey_state_machine;");
        DB::unprepared("DROP TRIGGER IF EXISTS enforce_investment_status_machine;");
    }
};
