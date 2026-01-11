<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 1 STABILIZATION - Issue 2: Transition Validation
 *
 * PURPOSE:
 * Validates lifecycle state transitions against data-driven rules.
 * Prevents invalid state changes without schema migration.
 *
 * INTEGRATION:
 * Call validateTransition() before any lifecycle state change.
 * Throws exception if transition is invalid.
 */
class LifecycleTransitionValidator
{
    /**
     * Validate if transition is allowed
     *
     * @param string $fromState
     * @param string $toState
     * @param string $trigger
     * @throws \RuntimeException if transition is invalid
     * @return void
     */
    public function validateTransition(string $fromState, string $toState, string $trigger): void
    {
        // Check if transition exists and is active
        $transition = DB::table('lifecycle_state_transitions')
            ->where('from_state', $fromState)
            ->where('to_state', $toState)
            ->where('is_active', true)
            ->first();

        if (!$transition) {
            Log::error('Invalid lifecycle state transition attempted', [
                'from_state' => $fromState,
                'to_state' => $toState,
                'trigger' => $trigger,
            ]);

            throw new \RuntimeException(
                "Invalid lifecycle transition: {$fromState} → {$toState}. " .
                "This transition is not defined in the system."
            );
        }

        // Verify trigger matches
        if ($transition->trigger !== $trigger) {
            Log::warning('Lifecycle transition trigger mismatch', [
                'from_state' => $fromState,
                'to_state' => $toState,
                'expected_trigger' => $transition->trigger,
                'actual_trigger' => $trigger,
            ]);
        }

        // Check if admin approval is required
        if ($transition->requires_admin_approval && !$this->hasAdminApproval()) {
            throw new \RuntimeException(
                "Transition {$fromState} → {$toState} requires admin approval"
            );
        }
    }

    /**
     * Check if current user has admin privileges
     *
     * @return bool
     */
    protected function hasAdminApproval(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole(['admin', 'super-admin']);
    }

    /**
     * Get all valid transitions from a state
     *
     * @param string $fromState
     * @return array
     */
    public function getValidTransitions(string $fromState): array
    {
        return DB::table('lifecycle_state_transitions')
            ->where('from_state', $fromState)
            ->where('is_active', true)
            ->get()
            ->map(function ($t) {
                return [
                    'to_state' => $t->to_state,
                    'trigger' => $t->trigger,
                    'requires_admin' => $t->requires_admin_approval,
                    'is_reversible' => $t->is_reversible,
                ];
            })
            ->toArray();
    }

    /**
     * Check if a state allows buying
     *
     * @param string $state
     * @return bool
     */
    public function allowsBuying(string $state): bool
    {
        $stateRecord = DB::table('lifecycle_states')
            ->where('code', $state)
            ->where('is_active', true)
            ->first();

        return $stateRecord ? (bool) $stateRecord->allows_buying : false;
    }

    /**
     * Add new lifecycle state without schema migration
     *
     * @param array $stateData
     * @return int
     */
    public function addLifecycleState(array $stateData): int
    {
        return DB::table('lifecycle_states')->insertGetId(array_merge([
            'is_active' => true,
            'allows_buying' => false,
            'display_order' => 999,
            'created_at' => now(),
            'updated_at' => now(),
        ], $stateData));
    }

    /**
     * Add new transition rule without schema migration
     *
     * @param string $fromState
     * @param string $toState
     * @param string $trigger
     * @param array $options
     * @return int
     */
    public function addTransition(
        string $fromState,
        string $toState,
        string $trigger,
        array $options = []
    ): int {
        return DB::table('lifecycle_state_transitions')->insertGetId(array_merge([
            'from_state' => $fromState,
            'to_state' => $toState,
            'trigger' => $trigger,
            'requires_admin_approval' => false,
            'is_reversible' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $options));
    }
}
