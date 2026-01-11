<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * PHASE 1 STABILIZATION - Issue 1: Field Ownership Enforcement
 *
 * PURPOSE:
 * Prevents companies from writing to governance_state or platform_assertions fields.
 * Provides explicit ownership boundaries within overloaded companies table.
 *
 * USAGE:
 * Add to Company model: use EnforcesFieldOwnership;
 * Automatically validates on save/update.
 */
trait EnforcesFieldOwnership
{
    /**
     * Boot the trait
     */
    protected static function bootEnforcesFieldOwnership(): void
    {
        // Before saving, validate field ownership
        static::saving(function ($model) {
            if (!$model->validateFieldOwnership()) {
                throw new \RuntimeException(
                    'Field ownership violation: Company attempted to write to protected fields'
                );
            }
        });
    }

    /**
     * Get field ownership domain for a specific field
     *
     * @param string $field
     * @return string|null 'issuer_truth', 'governance_state', 'platform_assertions', or null
     */
    public function getFieldOwnership(string $field): ?string
    {
        $ownershipMap = $this->field_ownership_map ?? $this->getDefaultOwnershipMap();

        foreach ($ownershipMap as $domain => $fields) {
            if (in_array($field, $fields)) {
                return $domain;
            }
        }

        return null;
    }

    /**
     * Check if current user can write to a field
     *
     * @param string $field
     * @return bool
     */
    public function canWriteField(string $field): bool
    {
        $ownership = $this->getFieldOwnership($field);

        // If field not in ownership map, allow (backward compatibility)
        if ($ownership === null) {
            return true;
        }

        $user = auth()->user();

        // Platform/admin can write to all fields
        if ($user && $user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Company users can only write to issuer_truth fields
        if ($user && $user->company_id === $this->id) {
            return $ownership === 'issuer_truth';
        }

        // Default deny for governance_state and platform_assertions
        return $ownership === 'issuer_truth';
    }

    /**
     * Validate that dirty fields are writable by current user
     *
     * @return bool
     */
    protected function validateFieldOwnership(): bool
    {
        // Skip validation if no changes
        if (!$this->isDirty()) {
            return true;
        }

        // Skip validation for platform/admin users
        $user = auth()->user();
        if ($user && $user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Check each dirty field
        $dirtyFields = array_keys($this->getDirty());
        $violations = [];

        foreach ($dirtyFields as $field) {
            if (!$this->canWriteField($field)) {
                $violations[] = $field;
            }
        }

        // Log violations
        if (!empty($violations)) {
            Log::warning('Field ownership violation attempted', [
                'company_id' => $this->id,
                'user_id' => $user?->id,
                'violated_fields' => $violations,
                'field_ownership' => array_map(
                    fn($f) => $this->getFieldOwnership($f),
                    $violations
                ),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Get default ownership map (used when field_ownership_map is null)
     *
     * @return array
     */
    protected function getDefaultOwnershipMap(): array
    {
        return [
            'issuer_truth' => [
                'name', 'legal_name', 'cin', 'pan', 'registration_number',
                'registration_date', 'registered_office_address', 'corporate_office_address',
                'website', 'email', 'phone', 'industry', 'sector', 'description',
                'incorporation_country', 'business_model', 'target_market',
                'board_size', 'independent_directors', 'board_committees', 'company_secretary',
            ],
            'governance_state' => [
                'lifecycle_state', 'buying_enabled', 'is_suspended', 'suspension_reason',
                'suspended_at', 'suspended_by', 'tier_1_approved_at', 'tier_2_approved_at',
                'tier_3_approved_at', 'last_tier_progression_at', 'disclosure_stage',
                'disclosure_submitted_at', 'disclosure_approved_at', 'disclosure_approved_by',
            ],
            'platform_assertions' => [
                'platform_generated_note',
            ],
        ];
    }

    /**
     * Explicitly mark fields as governance state (admin-only)
     * Use this when updating lifecycle state
     *
     * @param array $attributes
     * @return bool
     */
    public function updateGovernanceState(array $attributes): bool
    {
        // Temporarily bypass ownership check
        static::withoutEvents(function () use ($attributes) {
            $this->fill($attributes)->save();
        });

        return true;
    }

    /**
     * Explicitly mark fields as platform assertions (platform-only)
     * Use this when updating calculated metrics
     *
     * @param array $attributes
     * @return bool
     */
    public function updatePlatformAssertions(array $attributes): bool
    {
        // Temporarily bypass ownership check
        static::withoutEvents(function () use ($attributes) {
            $this->fill($attributes)->save();
        });

        return true;
    }
}
