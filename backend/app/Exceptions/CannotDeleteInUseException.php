<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to delete an entity that is still in use.
 *
 * Prevents accidental data loss by blocking deletion of entities with active dependencies.
 */
class CannotDeleteInUseException extends Exception
{
    protected $entityType;
    protected $entityId;
    protected $dependencies;

    public function __construct(string $entityType, $entityId, array $dependencies = [])
    {
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->dependencies = $dependencies;

        $dependencyList = $this->formatDependencies();
        $message = "Cannot delete {$entityType} (ID: {$entityId}) because it is currently in use.{$dependencyList}";

        parent::__construct($message, 422);
    }

    /**
     * Format dependencies for error message.
     */
    protected function formatDependencies(): string
    {
        if (empty($this->dependencies)) {
            return '';
        }

        $parts = [];
        foreach ($this->dependencies as $key => $count) {
            if ($count > 0) {
                $parts[] = "{$count} {$key}";
            }
        }

        return ' Dependencies: ' . implode(', ', $parts) . '.';
    }

    /**
     * Get the entity type.
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Get the entity ID.
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Get the dependencies.
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Render the exception as JSON response.
     */
    public function render($request)
    {
        return response()->json([
            'error' => 'cannot_delete_in_use',
            'message' => $this->getMessage(),
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'dependencies' => $this->dependencies,
        ], $this->getCode());
    }
}
