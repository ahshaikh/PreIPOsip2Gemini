<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;

/**
 * FIX 17 (P3): State Machine Pattern
 *
 * Provides state transition management with validation and audit logging
 *
 * Usage in Model:
 * use HasStateMachine;
 *
 * Define state config:
 * protected static $stateConfig = [
 *     'field' => 'status',
 *     'states' => ['pending', 'approved', 'rejected'],
 *     'transitions' => [
 *         'approve' => ['from' => ['pending'], 'to' => 'approved'],
 *         'reject' => ['from' => ['pending'], 'to' => 'rejected'],
 *     ],
 * ];
 *
 * Use in code:
 * $model->transitionTo('approved'); // With validation
 * $model->can('approve'); // Check if transition allowed
 */
trait HasStateMachine
{
    /**
     * Attempt to transition to a new state
     *
     * @param string $newState
     * @param array $metadata Additional data to log
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function transitionTo(string $newState, array $metadata = []): bool
    {
        $config = $this->getStateConfig();
        $currentState = $this->{$config['field']};

        // Validate state exists
        if (!in_array($newState, $config['states'])) {
            throw new \InvalidArgumentException("Invalid state: {$newState}");
        }

        // Find transition
        $transition = $this->findTransition($currentState, $newState);

        if (!$transition) {
            throw new \InvalidArgumentException(
                "Invalid state transition from '{$currentState}' to '{$newState}'"
            );
        }

        // Call before hook if exists
        if (method_exists($this, $beforeHook = "before" . ucfirst($transition['name']))) {
            $this->$beforeHook();
        }

        // Perform transition
        $oldState = $currentState;
        $this->{$config['field']} = $newState;
        $this->save();

        // Log transition
        $this->logStateTransition($oldState, $newState, $transition['name'], $metadata);

        // Call after hook if exists
        if (method_exists($this, $afterHook = "after" . ucfirst($transition['name']))) {
            $this->$afterHook();
        }

        return true;
    }

    /**
     * Check if a transition is allowed
     *
     * @param string $transitionName
     * @return bool
     */
    public function can(string $transitionName): bool
    {
        $config = $this->getStateConfig();
        $currentState = $this->{$config['field']};

        if (!isset($config['transitions'][$transitionName])) {
            return false;
        }

        $transition = $config['transitions'][$transitionName];

        return in_array($currentState, $transition['from']);
    }

    /**
     * Get available transitions from current state
     *
     * @return array
     */
    public function availableTransitions(): array
    {
        $config = $this->getStateConfig();
        $currentState = $this->{$config['field']};
        $available = [];

        foreach ($config['transitions'] as $name => $transition) {
            if (in_array($currentState, $transition['from'])) {
                $available[] = [
                    'name' => $name,
                    'to' => $transition['to'],
                    'label' => $transition['label'] ?? ucfirst($name),
                ];
            }
        }

        return $available;
    }

    /**
     * Get state history
     *
     * @return \Illuminate\Support\Collection
     */
    public function stateHistory()
    {
        return AuditLog::where('metadata->model_type', get_class($this))
            ->where('metadata->model_id', $this->id)
            ->where('action', 'like', '%.state_transition')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find transition between two states
     *
     * @param string $from
     * @param string $to
     * @return array|null
     */
    protected function findTransition(string $from, string $to): ?array
    {
        $config = $this->getStateConfig();

        foreach ($config['transitions'] as $name => $transition) {
            if (in_array($from, $transition['from']) && $transition['to'] === $to) {
                return array_merge($transition, ['name' => $name]);
            }
        }

        return null;
    }

    /**
     * Log state transition to audit log
     *
     * @param string $from
     * @param string $to
     * @param string $transitionName
     * @param array $metadata
     */
    protected function logStateTransition(string $from, string $to, string $transitionName, array $metadata = []): void
    {
        try {
            AuditLog::create([
                'action' => class_basename($this) . '.state_transition',
                'actor_id' => auth()->id(),
                'actor_type' => auth()->check() ? get_class(auth()->user()) : 'System',
                'description' => $this->getTransitionDescription($from, $to, $transitionName),
                'old_values' => [$this->getStateConfig()['field'] => $from],
                'new_values' => [$this->getStateConfig()['field'] => $to],
                'metadata' => array_merge([
                    'model_type' => get_class($this),
                    'model_id' => $this->id,
                    'transition' => $transitionName,
                    'from_state' => $from,
                    'to_state' => $to,
                ], $metadata),
            ]);

            Log::info('State transition', [
                'model' => class_basename($this),
                'id' => $this->id,
                'transition' => $transitionName,
                'from' => $from,
                'to' => $to,
                'actor' => auth()->id(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log state transition', [
                'model' => get_class($this),
                'id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get human-readable transition description
     *
     * @param string $from
     * @param string $to
     * @param string $transitionName
     * @return string
     */
    protected function getTransitionDescription(string $from, string $to, string $transitionName): string
    {
        $modelName = class_basename($this);
        $identifier = method_exists($this, 'getIdentifierForAudit')
            ? $this->getIdentifierForAudit()
            : "#{$this->id}";

        return "{$modelName} {$identifier}: {$transitionName} ('{$from}' → '{$to}')";
    }

    /**
     * Get state configuration
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function getStateConfig(): array
    {
        if (!isset(static::$stateConfig)) {
            throw new \RuntimeException(
                'State configuration not defined. Please define static $stateConfig in ' . get_class($this)
            );
        }

        return static::$stateConfig;
    }

    /**
     * Scope to filter by current state
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $states
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInState($query, $states)
    {
        $config = $this->getStateConfig();
        $states = is_array($states) ? $states : [$states];

        return $query->whereIn($config['field'], $states);
    }

    /**
     * Check if model is in specific state
     *
     * @param string|array $states
     * @return bool
     */
    public function isInState($states): bool
    {
        $config = $this->getStateConfig();
        $currentState = $this->{$config['field']};
        $states = is_array($states) ? $states : [$states];

        return in_array($currentState, $states);
    }
}
