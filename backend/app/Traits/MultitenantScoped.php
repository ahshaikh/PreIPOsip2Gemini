<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-TENANT-ISOLATION-GUARD | V-SCALE-SECURITY
 * * ARCHITECTURAL FIX: 
 * Moves beyond simple 'scopes' to a strict 'Context Guard'.
 * Prevents cross-tenant data leakage at the model level.
 */

namespace App\Traits;

use App\Exceptions\TenantSecurityException;
use Illuminate\Database\Eloquent\Builder;

trait MultitenantScoped
{
    protected static function bootMultitenantScoped()
    {
        $tenantId = session('active_tenant_id');

        // [SECURITY FIX]: Global Scope for Data Isolation
        static::addGlobalScope('tenant', function (Builder $builder) use ($tenantId) {
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        // [ANTI-PATTERN FIX]: Prevent saving across tenants
        static::creating(function ($model) use ($tenantId) {
            if ($tenantId && !$model->tenant_id) {
                $model->tenant_id = $tenantId;
            }
        });

        static::updating(function ($model) use ($tenantId) {
            // Check if the model being updated belongs to the active tenant
            if ($tenantId && $model->getOriginal('tenant_id') != $tenantId) {
                throw new TenantSecurityException("UNAUTHORIZED_CROSS_TENANT_ACCESS");
            }
        });
    }
}