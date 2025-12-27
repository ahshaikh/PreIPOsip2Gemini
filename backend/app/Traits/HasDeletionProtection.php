<?php

namespace App\Traits;

use App\Exceptions\CannotDeleteInUseException;
use Illuminate\Database\Eloquent\Model;

/**
 * Deletion Protection Trait.
 *
 * Prevents deletion of models that are currently in use by other entities.
 * Models using this trait should define $deletionProtectionRules property.
 *
 * Example:
 * protected $deletionProtectionRules = [
 *     'investments' => 'active investments',
 *     'subscriptions' => 'active subscriptions',
 * ];
 */
trait HasDeletionProtection
{
    /**
     * Boot the deletion protection trait.
     */
    public static function bootHasDeletionProtection()
    {
        static::deleting(function (Model $model) {
            $model->checkDeletionProtection();
        });
    }

    /**
     * Check if model can be deleted based on protection rules.
     *
     * @throws CannotDeleteInUseException
     */
    protected function checkDeletionProtection()
    {
        if (!property_exists($this, 'deletionProtectionRules')) {
            return;
        }

        $dependencies = [];

        foreach ($this->deletionProtectionRules as $relationship => $label) {
            // Support both string labels and callable conditions
            if (is_callable($label)) {
                $count = $label($this);
                $displayLabel = $relationship;
            } elseif (method_exists($this, $relationship)) {
                $count = $this->$relationship()->count();
                $displayLabel = is_string($label) ? $label : $relationship;
            } else {
                continue;
            }

            if ($count > 0) {
                $dependencies[$displayLabel] = $count;
            }
        }

        if (!empty($dependencies)) {
            throw new CannotDeleteInUseException(
                class_basename($this),
                $this->id,
                $dependencies
            );
        }
    }

    /**
     * Check if model is safe to delete (has no dependencies).
     */
    public function canBeDeleted(): bool
    {
        try {
            $this->checkDeletionProtection();
            return true;
        } catch (CannotDeleteInUseException $e) {
            return false;
        }
    }

    /**
     * Get dependencies that prevent deletion.
     */
    public function getDeletionDependencies(): array
    {
        if (!property_exists($this, 'deletionProtectionRules')) {
            return [];
        }

        $dependencies = [];

        foreach ($this->deletionProtectionRules as $relationship => $label) {
            if (is_callable($label)) {
                $count = $label($this);
                $displayLabel = $relationship;
            } elseif (method_exists($this, $relationship)) {
                $count = $this->$relationship()->count();
                $displayLabel = is_string($label) ? $label : $relationship;
            } else {
                continue;
            }

            if ($count > 0) {
                $dependencies[$displayLabel] = $count;
            }
        }

        return $dependencies;
    }

    /**
     * Force delete with confirmation (bypasses protection).
     * Should only be used with explicit user confirmation.
     */
    public function forceDeleteWithConfirmation(bool $confirmed = false)
    {
        if (!$confirmed) {
            throw new \Exception('Force deletion requires explicit confirmation.');
        }

        // Temporarily disable deletion protection
        $originalRules = $this->deletionProtectionRules ?? [];
        $this->deletionProtectionRules = [];

        try {
            $result = $this->delete();
            return $result;
        } finally {
            // Restore protection rules
            $this->deletionProtectionRules = $originalRules;
        }
    }
}
