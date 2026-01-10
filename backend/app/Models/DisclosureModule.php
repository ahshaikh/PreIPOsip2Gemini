<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PHASE 1 - MODEL 1/5: DisclosureModule
 *
 * PURPOSE:
 * Represents reusable templates for different types of company disclosures.
 * Modules define the structure, validation rules, and requirements for
 * disclosure data (business model, financials, risks, governance, etc.)
 *
 * KEY RESPONSIBILITIES:
 * - Store JSON schema validation rules
 * - Define module ordering and display configuration
 * - Map to SEBI regulatory categories
 * - Configure approval workflow requirements
 *
 * ADMIN MANAGED:
 * Only admins can create/modify modules. Companies use these as templates.
 *
 * @property int $id
 * @property string $code Unique code: business_model, financials, risks, etc.
 * @property string $name Display name
 * @property string|null $description Admin-facing description
 * @property string|null $help_text Company-facing instructions
 * @property bool $is_required Whether companies must complete this module
 * @property bool $is_active Whether this module is currently in use
 * @property int $display_order Order in disclosure flow
 * @property string|null $icon Icon identifier for frontend
 * @property string|null $color Color code for theming
 * @property array $json_schema JSON Schema v7 validation rules
 * @property array|null $default_data Template data for new disclosures
 * @property string|null $sebi_category SEBI disclosure category mapping
 * @property array|null $regulatory_references SEBI regulation references
 * @property bool $requires_admin_approval Whether changes need approval
 * @property int $min_approval_reviews Minimum admin reviews required
 * @property array|null $approval_checklist Admin verification checklist
 * @property int|null $created_by Admin who created this module
 * @property int|null $updated_by Admin who last modified
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class DisclosureModule extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'disclosure_modules';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'help_text',
        'is_required',
        'is_active',
        'display_order',
        'icon',
        'color',
        'json_schema',
        'default_data',
        'sebi_category',
        'regulatory_references',
        'requires_admin_approval',
        'min_approval_reviews',
        'approval_checklist',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'json_schema' => 'array',
        'default_data' => 'array',
        'regulatory_references' => 'array',
        'requires_admin_approval' => 'boolean',
        'min_approval_reviews' => 'integer',
        'approval_checklist' => 'array',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Company disclosures using this module template
     */
    public function companyDisclosures()
    {
        return $this->hasMany(CompanyDisclosure::class, 'disclosure_module_id');
    }

    /**
     * Admin who created this module
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Admin who last modified this module
     */
    public function modifier()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to only active modules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only required modules
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope to modules in display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    /**
     * Scope to modules by SEBI category
     */
    public function scopeBySebiCategory($query, string $category)
    {
        return $query->where('sebi_category', $category);
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Validate disclosure data against this module's JSON schema
     *
     * @param array $data Disclosure data to validate
     * @return array Returns ['valid' => bool, 'errors' => array]
     */
    public function validateDisclosureData(array $data): array
    {
        // TODO: Implement JSON Schema validation using opis/json-schema or similar
        // For now, return basic structure validation

        $errors = [];
        $schema = $this->json_schema;

        // Check required properties exist
        if (isset($schema['required']) && is_array($schema['required'])) {
            foreach ($schema['required'] as $requiredField) {
                if (!isset($data[$requiredField])) {
                    $errors[] = "Required field '{$requiredField}' is missing";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Calculate completion percentage for disclosure data
     *
     * @param array $data Disclosure data
     * @return int Percentage (0-100)
     */
    public function calculateCompletionPercentage(array $data): int
    {
        $schema = $this->json_schema;

        // Get required fields from schema
        $requiredFields = $schema['required'] ?? [];
        $totalFields = count($schema['properties'] ?? []);

        if ($totalFields === 0) {
            return 0;
        }

        // Count filled fields
        $filledFields = 0;
        foreach ($schema['properties'] ?? [] as $fieldName => $fieldSchema) {
            if (isset($data[$fieldName]) && !empty($data[$fieldName])) {
                $filledFields++;
            }
        }

        return (int) round(($filledFields / $totalFields) * 100);
    }

    /**
     * Check if this module is SEBI-mandated
     *
     * @return bool
     */
    public function isSebiMandated(): bool
    {
        return !empty($this->sebi_category);
    }

    /**
     * Get human-readable display label
     *
     * @return string
     */
    public function getDisplayLabel(): string
    {
        return $this->name . ($this->is_required ? ' *' : '');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get the icon with fallback
     */
    public function getIconAttribute($value): string
    {
        return $value ?? 'file-text';
    }

    /**
     * Get the color with fallback
     */
    public function getColorAttribute($value): string
    {
        return $value ?? 'gray';
    }
}
