<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 1 STABILIZATION - Issue 4: Platform Context Guard
 *
 * PURPOSE:
 * Enforces platform context authority rules.
 * Companies CANNOT write platform-managed context.
 * All platform context changes are versioned and time-aware.
 *
 * USAGE:
 * Call guardWrite() before ANY write to platform context tables.
 * Call getCurrentVersion() to get calculation logic version.
 * Call recordContextChange() after calculation.
 */
class PlatformContextGuard
{
    /**
     * Guard write operation to platform context
     *
     * @param string $contextType
     * @param string $operation 'create', 'update', 'delete'
     * @throws \RuntimeException if company tries to write
     * @return void
     */
    public function guardWrite(string $contextType, string $operation = 'update'): void
    {
        // Check authority
        $authority = DB::table('platform_context_authority')
            ->where('context_type', $contextType)
            ->first();

        if (!$authority) {
            Log::warning('Platform context write to unregistered type', [
                'context_type' => $contextType,
                'operation' => $operation,
            ]);

            throw new \RuntimeException(
                "Context type '{$contextType}' is not registered in platform authority"
            );
        }

        // Check if caller is platform
        $user = auth()->user();
        $isCompanyUser = $user && $user->company_id !== null;

        if ($isCompanyUser && !$authority->is_company_writable) {
            Log::critical('PLATFORM CONTEXT VIOLATION: Company attempted write', [
                'context_type' => $contextType,
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'operation' => $operation,
                'ip_address' => request()->ip(),
            ]);

            throw new \RuntimeException(
                "Platform context '{$contextType}' is READ-ONLY for companies. " .
                "Only platform can write this data."
            );
        }

        // Verify caller is admin or system
        if ($user && !$user->hasRole(['admin', 'super-admin', 'system'])) {
            Log::warning('Non-admin user attempted platform context write', [
                'context_type' => $contextType,
                'user_id' => $user->id,
                'roles' => $user->getRoleNames(),
            ]);

            throw new \RuntimeException(
                "Platform context write requires admin privileges"
            );
        }

        Log::info('Platform context write authorized', [
            'context_type' => $contextType,
            'operation' => $operation,
            'user_id' => $user?->id,
        ]);
    }

    /**
     * Get current version for a context type
     *
     * @param string $contextType
     * @return object|null
     */
    public function getCurrentVersion(string $contextType): ?object
    {
        return DB::table('platform_context_versions')
            ->where('context_type', $contextType)
            ->where('is_current', true)
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>', now());
            })
            ->first();
    }

    /**
     * Record platform context change
     *
     * @param string $contextType
     * @param int $entityId
     * @param array $changeData
     * @return void
     */
    public function recordContextChange(string $contextType, int $entityId, array $changeData): void
    {
        $version = $this->getCurrentVersion($contextType);

        DB::table('platform_context_change_log')->insert([
            'context_type' => $contextType,
            'entity_id' => $entityId,
            'version_code' => $version->version_code ?? 'unknown',
            'change_data' => json_encode($changeData),
            'calculated_at' => now(),
            'calculated_by' => auth()->id() ?? 0,
            'created_at' => now(),
        ]);
    }

    /**
     * Create new version of platform context calculation
     *
     * @param string $contextType
     * @param string $versionCode
     * @param string $changelog
     * @param array|null $calculationLogic
     * @return int
     */
    public function createNewVersion(
        string $contextType,
        string $versionCode,
        string $changelog,
        ?array $calculationLogic = null
    ): int {
        DB::beginTransaction();

        try {
            // Mark current version as superseded
            DB::table('platform_context_versions')
                ->where('context_type', $contextType)
                ->where('is_current', true)
                ->update([
                    'is_current' => false,
                    'effective_until' => now(),
                    'updated_at' => now(),
                ]);

            // Insert new version
            $id = DB::table('platform_context_versions')->insertGetId([
                'context_type' => $contextType,
                'version_code' => $versionCode,
                'changelog' => $changelog,
                'calculation_logic' => $calculationLogic ? json_encode($calculationLogic) : null,
                'effective_from' => now(),
                'is_current' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            Log::info('Platform context version created', [
                'context_type' => $contextType,
                'version_code' => $versionCode,
                'version_id' => $id,
            ]);

            return $id;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get calculation frequency for context type
     *
     * @param string $contextType
     * @return string|null
     */
    public function getCalculationFrequency(string $contextType): ?string
    {
        $authority = DB::table('platform_context_authority')
            ->where('context_type', $contextType)
            ->first();

        return $authority->calculation_frequency ?? null;
    }

    /**
     * Check if context should be recalculated
     *
     * @param string $contextType
     * @param \Carbon\Carbon $lastCalculated
     * @return bool
     */
    public function shouldRecalculate(string $contextType, $lastCalculated): bool
    {
        $frequency = $this->getCalculationFrequency($contextType);

        $intervals = [
            'hourly' => 3600,
            'daily' => 86400,
            'weekly' => 604800,
        ];

        $seconds = $intervals[$frequency] ?? 0;

        if ($seconds === 0) {
            return false; // on_demand or on_approval - don't auto-recalculate
        }

        return $lastCalculated->diffInSeconds(now()) >= $seconds;
    }
}
