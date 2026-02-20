<?php
/**
 * V-AUDIT-FIX-2026: Eligibility Enforcement Configuration
 *
 * Controls whether buy eligibility is re-verified at payment commit.
 * Disable in testing to prevent cascade failures.
 */

return [
    'enforce_at_commit' => env('ENFORCE_ELIGIBILITY_AT_COMMIT', true),
];
