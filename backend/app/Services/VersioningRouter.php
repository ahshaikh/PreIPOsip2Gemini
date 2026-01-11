<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 1 STABILIZATION - Issue 6: Versioning Router
 *
 * PURPOSE:
 * Prevents engineers from querying wrong versioning table.
 * Enforces versioning authority rules from config/versioning.php.
 *
 * USAGE:
 * $table = VersioningRouter::getAuthoritativeSource('investor_facing_data');
 * $model = VersioningRouter::getModel('investor_facing_data');
 * VersioningRouter::validateSource('investor_facing_data', 'disclosure_versions'); // Throws if wrong
 */
class VersioningRouter
{
    /**
     * Get authoritative version source for a data type
     *
     * @param string $dataType 'investor_facing_data', 'company_master_record', 'platform_context'
     * @return string Table name
     * @throws \InvalidArgumentException if data type not recognized
     */
    public static function getAuthoritativeSource(string $dataType): string
    {
        $authority = Config::get("versioning.authority.{$dataType}");

        if (!$authority) {
            Log::error('Unknown data type in versioning router', [
                'data_type' => $dataType,
            ]);

            throw new \InvalidArgumentException(
                "Unknown data type '{$dataType}'. Must be one of: " .
                implode(', ', array_keys(Config::get('versioning.authority')))
            );
        }

        return $authority['source'];
    }

    /**
     * Get Eloquent model for a data type
     *
     * @param string $dataType
     * @return string|null Model class name
     */
    public static function getModel(string $dataType): ?string
    {
        $source = self::getAuthoritativeSource($dataType);
        $metadata = Config::get("versioning.tables.{$source}");

        return $metadata['model'] ?? null;
    }

    /**
     * Validate that correct versioning table is being used
     *
     * @param string $dataType
     * @param string $actualTable
     * @throws \RuntimeException if wrong table used
     * @return void
     */
    public static function validateSource(string $dataType, string $actualTable): void
    {
        $correctSource = self::getAuthoritativeSource($dataType);
        $authority = Config::get("versioning.authority.{$dataType}");

        if ($actualTable !== $correctSource) {
            // Check if this is a forbidden table
            $neverUse = $authority['never_use'] ?? [];

            $isForbidden = in_array($actualTable, $neverUse);

            $logData = [
                'data_type' => $dataType,
                'correct_source' => $correctSource,
                'actual_source' => $actualTable,
                'is_forbidden' => $isForbidden,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ];

            // Log violation
            if (Config::get('versioning.enforcement.log_violations')) {
                if ($isForbidden) {
                    Log::critical('VERSIONING VIOLATION: Forbidden table used', $logData);
                } else {
                    Log::warning('Versioning table mismatch', $logData);
                }
            }

            // Track violation
            if (Config::get('versioning.enforcement.track_violations')) {
                self::trackViolation($dataType, $actualTable, $correctSource);
            }

            // Throw exception in strict mode
            if (Config::get('versioning.enforcement.strict_mode') && $isForbidden) {
                throw new \RuntimeException(
                    "VERSIONING VIOLATION: Used '{$actualTable}' for '{$dataType}' data. " .
                    "Must use '{$correctSource}'. " .
                    "This is a FORBIDDEN table for this data type."
                );
            }
        }
    }

    /**
     * Get forbidden tables for a data type
     *
     * @param string $dataType
     * @return array
     */
    public static function getForbiddenTables(string $dataType): array
    {
        $authority = Config::get("versioning.authority.{$dataType}");
        return $authority['never_use'] ?? [];
    }

    /**
     * Check if a table is forbidden for a data type
     *
     * @param string $dataType
     * @param string $tableName
     * @return bool
     */
    public static function isForbidden(string $dataType, string $tableName): bool
    {
        return in_array($tableName, self::getForbiddenTables($dataType));
    }

    /**
     * Track versioning violation for monitoring
     *
     * @param string $dataType
     * @param string $actualTable
     * @param string $correctTable
     * @return void
     */
    protected static function trackViolation(string $dataType, string $actualTable, string $correctTable): void
    {
        // Could be sent to metrics/monitoring system
        // For now, just log
        Log::channel('versioning')->warning('Versioning violation tracked', [
            'data_type' => $dataType,
            'actual_table' => $actualTable,
            'correct_table' => $correctTable,
            'timestamp' => now()->toIso8601String(),
            'user_id' => auth()->id(),
            'url' => request()->url(),
        ]);
    }

    /**
     * Get all versioning authority rules
     *
     * @return array
     */
    public static function getAllRules(): array
    {
        return Config::get('versioning.authority');
    }

    /**
     * Get common mistakes documentation
     *
     * @return array
     */
    public static function getCommonMistakes(): array
    {
        return Config::get('versioning.common_mistakes');
    }

    /**
     * Helper: Get investor-facing disclosure versions
     * (Convenience method with built-in validation)
     *
     * @param int $companyId
     * @return \Illuminate\Support\Collection
     */
    public static function getInvestorDisclosureVersions(int $companyId)
    {
        self::validateSource('investor_facing_data', 'disclosure_versions');

        return \DB::table('disclosure_versions as dv')
            ->join('company_disclosures as cd', 'dv.company_disclosure_id', '=', 'cd.id')
            ->where('cd.company_id', $companyId)
            ->where('dv.is_approved', true)
            ->orderBy('dv.approved_at', 'desc')
            ->get();
    }
}
