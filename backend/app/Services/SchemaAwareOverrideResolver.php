<?php
// V-CONTRACT-HARDENING-CORRECTIVE: Schema-aware override resolver
// Replaces generic array_replace_recursive with deterministic, type-safe field resolution.
// REGULATORY GRADE: No silent fallbacks. Fails loudly on contract violations.

namespace App\Services;

use App\Models\PlanRegulatoryOverride;
use App\Exceptions\OverrideSchemaViolationException;

/**
 * SchemaAwareOverrideResolver
 *
 * Provides deterministic, schema-enforced override resolution for bonus configurations.
 * This class ensures:
 * - Override payloads contain ONLY permitted fields for their scope
 * - Type integrity is enforced (no string where float expected)
 * - Structural corruption is impossible (no array depth violations)
 * - Cross-scope mutation is blocked
 *
 * @package App\Services
 */
class SchemaAwareOverrideResolver
{
    /**
     * Schema definitions for each override scope.
     * Each field specifies: type, nullable, and whether it's overridable.
     */
    private const SCOPE_SCHEMAS = [
        PlanRegulatoryOverride::SCOPE_PROGRESSIVE => [
            'rate' => ['type' => 'float', 'nullable' => false, 'min' => 0, 'max' => 100],
            'start_month' => ['type' => 'int', 'nullable' => false, 'min' => 1, 'max' => 36],
            'max_percentage' => ['type' => 'float', 'nullable' => false, 'min' => 0, 'max' => 100],
            // 'overrides' is NOT overridable - too complex, risk of structural corruption
        ],
        PlanRegulatoryOverride::SCOPE_MILESTONE => [
            // Milestone config is an array of {month, amount} - special handling required
            '_array_of' => [
                'month' => ['type' => 'int', 'nullable' => false, 'min' => 1, 'max' => 36],
                'amount' => ['type' => 'float', 'nullable' => false, 'min' => 0],
            ],
        ],
        PlanRegulatoryOverride::SCOPE_CONSISTENCY => [
            'amount_per_payment' => ['type' => 'float', 'nullable' => false, 'min' => 0],
            // 'streaks' is NOT overridable - complex nested structure
        ],
        PlanRegulatoryOverride::SCOPE_WELCOME => [
            'amount' => ['type' => 'float', 'nullable' => false, 'min' => 0],
        ],
        PlanRegulatoryOverride::SCOPE_REFERRAL => [
            // Not directly overridable - too complex
        ],
        PlanRegulatoryOverride::SCOPE_MULTIPLIER_CAP => [
            'max_multiplier' => ['type' => 'float', 'nullable' => false, 'min' => 0.1, 'max' => 10],
        ],
        PlanRegulatoryOverride::SCOPE_GLOBAL_RATE => [
            'factor' => ['type' => 'float', 'nullable' => false, 'min' => 0, 'max' => 10],
        ],
    ];

    /**
     * Validate override payload against scope schema.
     * FAILS LOUDLY on any violation.
     *
     * V-CONTRACT-HARDENING-FINAL: Enhanced with structured exception context for audit logging.
     *
     * @param string $scope The override scope
     * @param array $payload The override payload
     * @return void
     * @throws OverrideSchemaViolationException On any schema violation
     */
    public function validatePayload(string $scope, array $payload): void
    {
        if ($scope === PlanRegulatoryOverride::SCOPE_FULL) {
            throw new OverrideSchemaViolationException(
                "SCOPE_FULL is not permitted. Full config replacement bypasses schema safety. " .
                "Use scope-specific overrides instead.",
                $scope,
                $payload
            );
        }

        if (!isset(self::SCOPE_SCHEMAS[$scope])) {
            throw new OverrideSchemaViolationException(
                "Unknown override scope: {$scope}. Cannot validate payload.",
                $scope,
                $payload
            );
        }

        $schema = self::SCOPE_SCHEMAS[$scope];

        // Handle array-of schemas (like milestone_config)
        if (isset($schema['_array_of'])) {
            $this->validateArrayOfPayload($scope, $payload, $schema['_array_of']);
            return;
        }

        // Validate each field in payload
        foreach ($payload as $field => $value) {
            if (!isset($schema[$field])) {
                throw new OverrideSchemaViolationException(
                    "Field '{$field}' is not permitted in scope '{$scope}'. " .
                    "Permitted fields: " . implode(', ', array_keys($schema)),
                    $scope,
                    $payload,
                    $field
                );
            }

            $this->validateFieldValue($scope, $field, $value, $schema[$field]);
        }
    }

    /**
     * Validate array-of structure (like milestone configs)
     */
    private function validateArrayOfPayload(string $scope, array $payload, array $itemSchema): void
    {
        if (!is_array($payload) || empty($payload)) {
            throw new OverrideSchemaViolationException(
                "Scope '{$scope}' requires a non-empty array of items.",
                $scope,
                $payload
            );
        }

        foreach ($payload as $index => $item) {
            if (!is_array($item)) {
                throw new OverrideSchemaViolationException(
                    "Item at index {$index} in scope '{$scope}' must be an associative array.",
                    $scope,
                    $payload,
                    "index_{$index}"
                );
            }

            // Validate required fields exist
            foreach ($itemSchema as $field => $rules) {
                if (!isset($item[$field])) {
                    throw new OverrideSchemaViolationException(
                        "Required field '{$field}' missing at index {$index} in scope '{$scope}'.",
                        $scope,
                        $payload,
                        "{$index}.{$field}"
                    );
                }
                $this->validateFieldValue($scope, "{$index}.{$field}", $item[$field], $rules);
            }

            // Check for unpermitted fields
            foreach ($item as $field => $value) {
                if (!isset($itemSchema[$field])) {
                    throw new OverrideSchemaViolationException(
                        "Field '{$field}' at index {$index} is not permitted in scope '{$scope}'.",
                        $scope,
                        $payload,
                        "{$index}.{$field}"
                    );
                }
            }
        }
    }

    /**
     * Validate a single field value against its schema rules.
     */
    private function validateFieldValue(string $scope, string $field, $value, array $rules): void
    {
        $expectedType = $rules['type'];

        // Type validation
        $actualType = $this->getPhpType($value);
        if (!$this->isTypeCompatible($actualType, $expectedType)) {
            throw new OverrideSchemaViolationException(
                "Field '{$field}' in scope '{$scope}' must be {$expectedType}, got {$actualType}.",
                $scope,
                null,
                $field
            );
        }

        // Cast to expected type for range validation
        $typedValue = $this->castToType($value, $expectedType);

        // Range validation
        if (isset($rules['min']) && $typedValue < $rules['min']) {
            throw new OverrideSchemaViolationException(
                "Field '{$field}' in scope '{$scope}' must be >= {$rules['min']}, got {$typedValue}.",
                $scope,
                null,
                $field
            );
        }

        if (isset($rules['max']) && $typedValue > $rules['max']) {
            throw new OverrideSchemaViolationException(
                "Field '{$field}' in scope '{$scope}' must be <= {$rules['max']}, got {$typedValue}.",
                $scope,
                null,
                $field
            );
        }
    }

    /**
     * Get PHP type string for a value
     */
    private function getPhpType($value): string
    {
        if (is_int($value)) return 'int';
        if (is_float($value)) return 'float';
        if (is_numeric($value)) return 'numeric';
        if (is_string($value)) return 'string';
        if (is_bool($value)) return 'bool';
        if (is_array($value)) return 'array';
        if (is_null($value)) return 'null';
        return 'unknown';
    }

    /**
     * Check if actual type is compatible with expected type
     */
    private function isTypeCompatible(string $actual, string $expected): bool
    {
        if ($actual === $expected) return true;

        // int is compatible with float
        if ($expected === 'float' && in_array($actual, ['int', 'float', 'numeric'])) return true;

        // numeric string is compatible with int/float
        if (in_array($expected, ['int', 'float']) && $actual === 'numeric') return true;

        return false;
    }

    /**
     * Cast value to expected type
     */
    private function castToType($value, string $type)
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'string' => (string) $value,
            default => $value,
        };
    }

    /**
     * Apply override to snapshot config using EXPLICIT field mapping.
     * NO generic merging. Each field is handled individually.
     *
     * @param array $snapshotConfig The immutable subscription snapshot
     * @param string $scope The override scope
     * @param array $overridePayload The validated override payload
     * @return array The resolved config (for calculation only - NOT persisted)
     * @throws OverrideSchemaViolationException If override cannot be applied safely
     */
    public function applyOverride(array $snapshotConfig, string $scope, array $overridePayload): array
    {
        // Re-validate payload (defense in depth)
        $this->validatePayload($scope, $overridePayload);

        $schema = self::SCOPE_SCHEMAS[$scope];

        // Handle array-of schemas (complete replacement)
        if (isset($schema['_array_of'])) {
            // For array configs like milestone, override REPLACES the entire array
            // This is intentional - partial milestone modification is ambiguous
            return $overridePayload;
        }

        // Handle global rate adjustment (special case)
        if ($scope === PlanRegulatoryOverride::SCOPE_GLOBAL_RATE) {
            return $this->applyGlobalRateOverride($snapshotConfig, $overridePayload);
        }

        // Handle multiplier cap (special case - not config modification)
        if ($scope === PlanRegulatoryOverride::SCOPE_MULTIPLIER_CAP) {
            // This doesn't modify config, it modifies the multiplier cap check
            return $snapshotConfig;
        }

        // Standard field-by-field override
        $result = $snapshotConfig;
        foreach ($overridePayload as $field => $value) {
            // Only override explicitly permitted fields
            if (isset($schema[$field])) {
                $result[$field] = $this->castToType($value, $schema[$field]['type']);
            }
        }

        return $result;
    }

    /**
     * Apply global rate adjustment factor to relevant config fields
     */
    private function applyGlobalRateOverride(array $config, array $override): array
    {
        $factor = (float) $override['factor'];

        // Apply factor to rate-based fields ONLY
        if (isset($config['rate'])) {
            $config['rate'] = $config['rate'] * $factor;
        }

        if (isset($config['amount_per_payment'])) {
            $config['amount_per_payment'] = $config['amount_per_payment'] * $factor;
        }

        if (isset($config['amount'])) {
            $config['amount'] = $config['amount'] * $factor;
        }

        return $config;
    }

    /**
     * Get the multiplier cap from override payload (if scope is MULTIPLIER_CAP)
     *
     * @param array $overridePayload
     * @return float|null The cap value, or null if not applicable
     */
    public function getMultiplierCapFromOverride(array $overridePayload): ?float
    {
        if (isset($overridePayload['max_multiplier'])) {
            return (float) $overridePayload['max_multiplier'];
        }
        return null;
    }

    /**
     * Get list of permitted fields for a scope (for API documentation/validation)
     */
    public function getPermittedFields(string $scope): array
    {
        if (!isset(self::SCOPE_SCHEMAS[$scope])) {
            return [];
        }

        $schema = self::SCOPE_SCHEMAS[$scope];

        if (isset($schema['_array_of'])) {
            return ['_array_of' => array_keys($schema['_array_of'])];
        }

        return array_keys($schema);
    }
}
