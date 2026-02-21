<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

/**
 * Audit Logger Service.
 *
 * Centralized service for logging admin actions.
 */
class AuditLogger
{
    /**
     * Log an admin action.
     *
     * @param string $action Action performed (created, updated, deleted, etc)
     * @param string $module Module name (companies, products, deals, etc)
     * @param string $description Human-readable description
     * @param array $options Additional options
     * @return AuditLog
     */
    public static function log(
        string $action,
        string $module,
        string $description,
        array $options = []
    ): AuditLog {
        $user = auth()->user();
        $actorType = static::detectActorType();

        return AuditLog::create([
            // Actor
            'actor_type' => $actorType,
            'actor_id' => $user?->id,
            'actor_name' => $user?->name ?? $user?->full_name,
            'actor_email' => $user?->email,

            // Action
            'action' => $action,
            'module' => $module,
            'description' => $description,

            // Target Entity
            'target_type' => $options['target_type'] ?? null,
            'target_id' => $options['target_id'] ?? null,
            'target_name' => $options['target_name'] ?? null,

            // Changes
            'old_values' => $options['old_values'] ?? null,
            'new_values' => $options['new_values'] ?? null,
            'metadata' => $options['metadata'] ?? null,

            // Request Context
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'request_method' => Request::method(),
            'request_url' => Request::fullUrl(),
            'session_id' => session()->getId(),

            // Risk
            'risk_level' => $options['risk_level'] ?? 'low',
            'requires_review' => $options['requires_review'] ?? false,
        ]);
    }

    /**
     * Log entity creation.
     */
    public static function logCreated(string $module, $entity, string $description = null): AuditLog
    {
        return static::log(
            'created',
            $module,
            $description ?? "Created {$module} record",
            [
                'target_type' => get_class($entity),
                'target_id' => $entity->id ?? null,
                'target_name' => $entity->name ?? $entity->title ?? null,
                'new_values' => $entity->toArray(),
            ]
        );
    }

    /**
     * Log entity update.
     */
    public static function logUpdated(string $module, $entity, array $oldValues, string $description = null): AuditLog
    {
        return static::log(
            'updated',
            $module,
            $description ?? "Updated {$module} record",
            [
                'target_type' => get_class($entity),
                'target_id' => $entity->id ?? null,
                'target_name' => $entity->name ?? $entity->title ?? null,
                'old_values' => $oldValues,
                'new_values' => $entity->toArray(),
            ]
        );
    }

    /**
     * Log entity deletion.
     */
    public static function logDeleted(string $module, $entity, string $description = null): AuditLog
    {
        return static::log(
            'deleted',
            $module,
            $description ?? "Deleted {$module} record",
            [
                'target_type' => get_class($entity),
                'target_id' => $entity->id ?? null,
                'target_name' => $entity->name ?? $entity->title ?? null,
                'old_values' => $entity->toArray(),
                'risk_level' => 'high',
            ]
        );
    }

    /**
     * Log approval action.
     */
    public static function logApproved(string $module, $entity, string $description): AuditLog
    {
        return static::log(
            'approved',
            $module,
            $description,
            [
                'target_type' => get_class($entity),
                'target_id' => $entity->id ?? null,
                'target_name' => $entity->name ?? $entity->title ?? null,
                'risk_level' => 'medium',
            ]
        );
    }

    /**
     * Log rejection action.
     */
    public static function logRejected(string $module, $entity, string $reason): AuditLog
    {
        return static::log(
            'rejected',
            $module,
            $reason,
            [
                'target_type' => get_class($entity),
                'target_id' => $entity->id ?? null,
                'target_name' => $entity->name ?? $entity->title ?? null,
                'metadata' => ['reason' => $reason],
                'risk_level' => 'medium',
            ]
        );
    }

    /**
     * Log high-risk action requiring review.
     */
    public static function logHighRisk(string $action, string $module, string $description, array $options = []): AuditLog
    {
        return static::log(
            $action,
            $module,
            $description,
            array_merge($options, [
                'risk_level' => 'high',
                'requires_review' => true,
            ])
        );
    }

    /**
     * Detect actor type from current authentication.
     */
    protected static function detectActorType(): string
    {
        if (auth()->guard('company_api')->check()) {
            return 'company_user';
        } elseif (auth()->check()) {
            return 'admin';
        }

        return 'system';
    }
}
