<?php

namespace App\Traits;

/**
 * Workflow Actions Trait.
 *
 * Provides workflow suggestions and quick action CTAs for admin interfaces.
 * Enables contextual quick actions based on entity state.
 *
 * Models using this trait should implement getWorkflowActions() method.
 */
trait HasWorkflowActions
{
    /**
     * Get available workflow actions for this entity.
     *
     * Returns array of action objects with:
     * - label: Display text for button
     * - action: API endpoint or action identifier
     * - type: primary, secondary, success, warning, danger
     * - icon: Optional icon identifier
     * - condition: Boolean indicating if action is available
     *
     * @return array
     */
    abstract public function getWorkflowActions(): array;

    /**
     * Get workflow suggestions (next recommended steps).
     *
     * @return array
     */
    public function getWorkflowSuggestions(): array
    {
        return array_filter($this->getWorkflowActions(), function ($action) {
            return ($action['condition'] ?? true) && ($action['suggested'] ?? false);
        });
    }

    /**
     * Check if a specific workflow action is available.
     *
     * @param string $actionKey
     * @return bool
     */
    public function canPerformAction(string $actionKey): bool
    {
        $actions = collect($this->getWorkflowActions());
        $action = $actions->firstWhere('key', $actionKey);

        return $action && ($action['condition'] ?? true);
    }

    /**
     * Get workflow context (current state + next steps).
     *
     * @return array
     */
    public function getWorkflowContext(): array
    {
        return [
            'current_state' => $this->getCurrentState(),
            'available_actions' => array_filter($this->getWorkflowActions(), fn($a) => $a['condition'] ?? true),
            'suggested_actions' => $this->getWorkflowSuggestions(),
            'blocking_issues' => $this->getBlockingIssues(),
        ];
    }

    /**
     * Get current workflow state description.
     *
     * @return string
     */
    protected function getCurrentState(): string
    {
        return 'active';
    }

    /**
     * Get issues blocking workflow progression.
     *
     * @return array
     */
    protected function getBlockingIssues(): array
    {
        return [];
    }
}
