<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PHASE 3 - MODEL: CompanyUserRole
 * 
 * PURPOSE:
 * Role-based access control for company users.
 * 
 * ROLES:
 * - founder: Full access to all disclosures
 * - finance: Access to financial disclosures (Tier 2)
 * - legal: Access to legal/compliance disclosures
 * - viewer: Read-only access
 * 
 * USAGE:
 * Used by CompanyDisclosurePolicy to enforce permissions at API and UI level.
 *
 * @property int $id
 * @property int $user_id
 * @property int $company_id
 * @property string $role
 * @property bool $is_active
 * @property int|null $assigned_by
 * @property \Illuminate\Support\Carbon $assigned_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @mixin IdeHelperCompanyUserRole
 */
class CompanyUserRole extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'company_user_roles';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'role',
        'is_active',
        'assigned_by',
        'assigned_at',
        'revoked_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // ROLE CONSTANTS
    // =========================================================================

    public const ROLE_FOUNDER = 'founder';
    public const ROLE_FINANCE = 'finance';
    public const ROLE_LEGAL = 'legal';
    public const ROLE_VIEWER = 'viewer';

    public const ROLES = [
        self::ROLE_FOUNDER,
        self::ROLE_FINANCE,
        self::ROLE_LEGAL,
        self::ROLE_VIEWER,
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * CompanyUser who has this role
     */
    public function user()
    {
        return $this->belongsTo(CompanyUser::class, 'user_id');
    }

    /**
     * Company this role is for
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * CompanyUser who assigned this role
     */
    public function assignedBy()
    {
        return $this->belongsTo(CompanyUser::class, 'assigned_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to active roles only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('revoked_at');
    }

    /**
     * Scope to specific company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to specific role
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // =========================================================================
    // PERMISSION CHECKS
    // =========================================================================

    /**
     * Check if role can edit disclosures
     */
    public function canEdit(): bool
    {
        return in_array($this->role, [self::ROLE_FOUNDER, self::ROLE_FINANCE, self::ROLE_LEGAL]);
    }

    /**
     * Check if role can submit disclosures
     */
    public function canSubmit(): bool
    {
        return in_array($this->role, [self::ROLE_FOUNDER, self::ROLE_FINANCE, self::ROLE_LEGAL]);
    }

    /**
     * Check if role can manage company users
     */
    public function canManageUsers(): bool
    {
        return $this->role === self::ROLE_FOUNDER;
    }

    /**
     * Check if role can access specific module
     *
     * @param DisclosureModule $module
     * @return bool
     */
    public function canAccessModule(DisclosureModule $module): bool
    {
        // Founder has access to everything
        if ($this->role === self::ROLE_FOUNDER) {
            return true;
        }

        // Viewer has read-only access to everything
        if ($this->role === self::ROLE_VIEWER) {
            return true; // Read-only enforced elsewhere
        }

        // Finance role: Access to Tier 2 modules (financials)
        if ($this->role === self::ROLE_FINANCE) {
            return $module->tier === 2 || $module->sebi_category === 'Financial Information';
        }

        // Legal role: Access to compliance/legal modules
        if ($this->role === self::ROLE_LEGAL) {
            return in_array($module->sebi_category, [
                'Legal & Compliance',
                'Governance & Risk',
            ]);
        }

        return false;
    }

    /**
     * Check if role can edit specific disclosure
     *
     * @param CompanyDisclosure $disclosure
     * @return bool
     */
    public function canEditDisclosure(CompanyDisclosure $disclosure): bool
    {
        // Must have edit permission
        if (!$this->canEdit()) {
            return false;
        }

        // Must have access to module
        if (!$this->canAccessModule($disclosure->module)) {
            return false;
        }

        // Disclosure must be editable
        return in_array($disclosure->status, ['draft', 'rejected', 'clarification_required']);
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    /**
     * Revoke this role
     */
    public function revoke(int $revokedBy): void
    {
        $this->is_active = false;
        $this->revoked_at = now();
        $this->save();

        \Log::info('Company user role revoked', [
            'role_id' => $this->id,
            'user_id' => $this->user_id,
            'company_id' => $this->company_id,
            'role' => $this->role,
            'revoked_by' => $revokedBy,
        ]);
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            self::ROLE_FOUNDER => 'Founder',
            self::ROLE_FINANCE => 'Finance Team',
            self::ROLE_LEGAL => 'Legal/Compliance',
            self::ROLE_VIEWER => 'Viewer',
            default => ucfirst($this->role),
        };
    }

    /**
     * Get role permissions summary
     */
    public function getPermissionsSummary(): array
    {
        return match($this->role) {
            self::ROLE_FOUNDER => [
                'view_all' => true,
                'edit_all' => true,
                'submit_all' => true,
                'manage_users' => true,
                'report_errors' => true,
            ],
            self::ROLE_FINANCE => [
                'view_all' => true,
                'edit_financial' => true,
                'submit_financial' => true,
                'manage_users' => false,
                'report_errors' => true,
            ],
            self::ROLE_LEGAL => [
                'view_all' => true,
                'edit_legal' => true,
                'submit_legal' => true,
                'manage_users' => false,
                'report_errors' => true,
            ],
            self::ROLE_VIEWER => [
                'view_all' => true,
                'edit_all' => false,
                'submit_all' => false,
                'manage_users' => false,
                'report_errors' => false,
            ],
        };
    }
}
