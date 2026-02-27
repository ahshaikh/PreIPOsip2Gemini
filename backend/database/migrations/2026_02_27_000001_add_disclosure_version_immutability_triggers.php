<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. prevent_disclosure_version_update
        DB::unprepared("
            DROP TRIGGER IF EXISTS prevent_disclosure_version_update;
            CREATE TRIGGER prevent_disclosure_version_update
            BEFORE UPDATE ON disclosure_versions
            FOR EACH ROW
            BEGIN
                -- Allow updates ONLY to tracking/metadata fields.
                -- Core disclosure fields must remain immutable.
                IF OLD.disclosure_data != NEW.disclosure_data
                   OR OLD.version_number != NEW.version_number
                   OR OLD.version_hash != NEW.version_hash
                   OR OLD.company_disclosure_id != NEW.company_disclosure_id
                   OR OLD.company_id != NEW.company_id
                   OR OLD.disclosure_module_id != NEW.disclosure_module_id
                   OR OLD.approved_at != NEW.approved_at
                   OR OLD.approved_by != NEW.approved_by
                THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Core fields of disclosure_versions cannot be updated.';
                END IF;
            END;
        ");

        // 2. prevent_disclosure_version_delete
        DB::unprepared("
            DROP TRIGGER IF EXISTS prevent_disclosure_version_delete;
            CREATE TRIGGER prevent_disclosure_version_delete
            BEFORE DELETE ON disclosure_versions
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: disclosure_versions cannot be deleted.';
            END;
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS prevent_disclosure_version_update;");
        DB::unprepared("DROP TRIGGER IF EXISTS prevent_disclosure_version_delete;");
    }
};
