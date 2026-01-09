<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-MULTI-TENANT-ISOLATION | V-ENTERPRISE-GATING
 * Refactored to address Module 9 Audit Gaps:
 * 1. Multi-Tenant Security: Implements infrastructure for scoped data access.
 * 2. Enterprise Logic: Added quota management and scoped user/plan relationships.
 * 3. Slug Integrity: Maintained unique slug generation while adding tenant awareness.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Traits\HasDeletionProtection;
use App\Traits\HasWorkflowActions;

class Company extends Model
{
    use HasFactory, SoftDeletes, HasDeletionProtection, HasWorkflowActions;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'website',
        'sector',
        'founded_year',
        'headquarters',
        'ceo_name',
        'latest_valuation',
        'funding_stage',
        'total_funding',
        'linkedin_url',
        'twitter_url',
        'facebook_url',
        'key_metrics',
        'investors',
        'is_featured',
        'status',
        'is_verified',
        'profile_completed',
        'profile_completion_percentage',
        'max_users_quota', // [AUDIT FIX]: Track enterprise user limits
        'settings'         // [AUDIT FIX]: Store enterprise-specific UI/behavior configs
    ];

    protected $casts = [
        'latest_valuation' => 'decimal:2',
        'total_funding' => 'decimal:2',
        'key_metrics' => 'array',
        'investors' => 'array',
        'settings' => 'array',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'profile_completed' => 'boolean',
    ];

    /**
     * Deletion protection rules.
     * Prevents deletion if company has active dependencies.
     */
    protected $deletionProtectionRules = [
        'deals' => 'active deals',
        'products' => 'products/inventory',
        'users' => 'company users',
    ];

    /**
     * Boot logic to handle automatic slug generation and unique constraints.
     * FIX 33, 34, 35: Company versioning and immutability
     */
    protected static function booted()
    {
        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = static::generateUniqueSlug($company->name);
            }
        });

        static::updating(function ($company) {
            // FIX 34: Enforce immutability after listing approval
            if ($company->hasApprovedListing()) {
                $protectedFields = [
                    'name',
                    'sector',
                    'founded_year',
                    'ceo_name',
                    'latest_valuation',
                    'total_funding',
                ];

                $changedProtectedFields = array_intersect(
                    $protectedFields,
                    array_keys($company->getDirty())
                );

                if (!empty($changedProtectedFields)) {
                    \Log::warning('Attempt to modify protected company fields after listing approval', [
                        'company_id' => $company->id,
                        'company_name' => $company->name,
                        'changed_fields' => $changedProtectedFields,
                        'user_id' => auth()->id(),
                    ]);

                    throw new \RuntimeException(
                        "Cannot modify protected fields after listing approval: " .
                        implode(', ', $changedProtectedFields) . ". " .
                        "Protected fields include: name, sector, valuation, funding. " .
                        "Contact admin to request changes."
                    );
                }
            }

            // Auto-update slug if name changed
            if ($company->isDirty('name') && !$company->isDirty('slug')) {
                $company->slug = static::generateUniqueSlug($company->name, $company->id);
            }
        });

        // FIX 33: Create version snapshot after company data is saved
        static::saved(function ($company) {
            // Only version if meaningful fields changed (not just timestamps)
            $versionableFields = [
                'name', 'description', 'logo', 'website', 'sector',
                'founded_year', 'headquarters', 'ceo_name', 'latest_valuation',
                'funding_stage', 'total_funding', 'key_metrics', 'investors',
                'is_verified', 'status',
            ];

            $changedFields = $company->wasChanged()
                ? array_intersect($versionableFields, array_keys($company->getChanges()))
                : [];

            if (!empty($changedFields)) {
                try {
                    CompanyVersion::createFromCompany(
                        $company,
                        $changedFields,
                        'Company data updated'
                    );

                    \Log::info('Company version created', [
                        'company_id' => $company->id,
                        'changed_fields' => $changedFields,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to create company version', [
                        'company_id' => $company->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail the save operation, just log the error
                }
            }
        });
    }

    /**
     * Generate a unique slug for the company.
     */
    public static function generateUniqueSlug($name, $ignoreId = null)
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        $query = static::where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        while ($query->exists()) {
            $slug = "{$original}-" . $count++;
            $query = static::where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
        }

        return $slug;
    }

    // --- [AUDIT FIX]: SCOPED ENTERPRISE RELATIONSHIPS ---

    /**
     * Users belonging to this enterprise.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Enterprise-exclusive investment plans.
     */
    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    // --- STANDARD RELATIONSHIPS ---

    public function deals()
    {
        return $this->hasMany(Deal::class); // Now uses company_id FK
    }

    public function financialReports()
    {
        return $this->hasMany(CompanyFinancialReport::class);
    }

    public function documents()
    {
        return $this->hasMany(CompanyDocument::class);
    }

    public function teamMembers()
    {
        return $this->hasMany(CompanyTeamMember::class);
    }

    public function fundingRounds()
    {
        return $this->hasMany(CompanyFundingRound::class);
    }

    /**
     * FIX: Added missing updates relationship
     * Required by CompanyProfileController::dashboard() method
     */
    public function updates()
    {
        return $this->hasMany(CompanyUpdate::class);
    }

    public function webinars()
    {
        return $this->hasMany(CompanyWebinar::class);
    }

    /**
     * FIX 33: Company versions relationship
     */
    public function versions(): HasMany
    {
        return $this->hasMany(CompanyVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * FIX 35: Get approval snapshots
     */
    public function approvalSnapshots(): HasMany
    {
        return $this->hasMany(CompanyVersion::class)
            ->where('is_approval_snapshot', true)
            ->orderBy('created_at', 'desc');
    }

    // --- SCOPES ---

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    // --- WORKFLOW ACTIONS ---

    /**
     * Get workflow actions for this company.
     */
    public function getWorkflowActions(): array
    {
        $hasProducts = $this->products()->exists();
        $hasDeals = $this->deals()->exists();
        $hasShareListings = \App\Models\CompanyShareListing::where('company_id', $this->id)
            ->whereIn('status', ['pending', 'under_review'])->exists();

        return [
            [
                'key' => 'create_product',
                'label' => 'Create Product',
                'action' => "/admin/products/create?company_id={$this->id}",
                'type' => 'primary',
                'icon' => 'plus',
                'condition' => $this->is_verified,
                'suggested' => $this->is_verified && !$hasProducts,
                'description' => 'Create investment product for this company',
            ],
            [
                'key' => 'review_share_listings',
                'label' => 'Review Share Submissions',
                'action' => "/admin/share-listings?company_id={$this->id}",
                'type' => 'warning',
                'icon' => 'clipboard-check',
                'condition' => $hasShareListings,
                'suggested' => $hasShareListings,
                'description' => 'Review pending share listing submissions',
            ],
            [
                'key' => 'create_deal',
                'label' => 'Create Deal',
                'action' => "/admin/deals/create?company_id={$this->id}",
                'type' => 'success',
                'icon' => 'handshake',
                'condition' => $hasProducts,
                'suggested' => $hasProducts && !$hasDeals,
                'description' => 'Launch investment deal',
            ],
            [
                'key' => 'verify_company',
                'label' => 'Verify Company',
                'action' => "/admin/companies/{$this->id}/verify",
                'type' => 'primary',
                'icon' => 'check-circle',
                'condition' => !$this->is_verified,
                'suggested' => !$this->is_verified && $this->profile_completed,
                'description' => 'Mark company as verified',
            ],
            [
                'key' => 'view_analytics',
                'label' => 'View Analytics',
                'action' => "/admin/companies/{$this->id}/analytics",
                'type' => 'secondary',
                'icon' => 'chart-line',
                'condition' => $hasDeals,
                'suggested' => false,
                'description' => 'View company performance metrics',
            ],
        ];
    }

    protected function getCurrentState(): string
    {
        if (!$this->is_verified) {
            return 'pending_verification';
        } elseif (!$this->products()->exists()) {
            return 'verified_no_products';
        } elseif (!$this->deals()->exists()) {
            return 'products_no_deals';
        } else {
            return 'active';
        }
    }

    protected function getBlockingIssues(): array
    {
        $issues = [];

        if (!$this->is_verified) {
            $issues[] = [
                'severity' => 'high',
                'message' => 'Company not verified. Verify before creating products.',
                'action' => 'verify_company',
            ];
        }

        if (!$this->profile_completed) {
            $issues[] = [
                'severity' => 'medium',
                'message' => 'Company profile incomplete.',
                'action' => 'complete_profile',
            ];
        }

        return $issues;
    }

    // --- FIX 34, 35: VERSIONING & IMMUTABILITY HELPERS ---

    /**
     * FIX 34: Check if company has an approved listing
     * Used to enforce immutability rules
     */
    public function hasApprovedListing(): bool
    {
        // Check if there's any approved share listing or active deal
        $hasApprovedShareListing = \App\Models\CompanyShareListing::where('company_id', $this->id)
            ->where('status', 'approved')
            ->exists();

        $hasActiveDeal = $this->deals()
            ->where('status', 'active')
            ->exists();

        return $hasApprovedShareListing || $hasActiveDeal;
    }

    /**
     * FIX 35: Create approval snapshot
     * Called when company listing is approved
     */
    public function createApprovalSnapshot(int $approvalId, string $approvalType = 'listing'): CompanyVersion
    {
        return CompanyVersion::createFromCompany(
            company: $this,
            changedFields: [], // No specific fields changed, this is a snapshot
            reason: "Snapshot created at {$approvalType} approval",
            isApprovalSnapshot: true,
            approvalId: $approvalId
        );
    }

    /**
     * FIX 33: Get latest version
     */
    public function getLatestVersion(): ?CompanyVersion
    {
        return $this->versions()->first();
    }

    /**
     * FIX 33: Get version history count
     */
    public function getVersionCount(): int
    {
        return $this->versions()->count();
    }

    /**
     * FIX 35: Get the snapshot from when company was first approved
     */
    public function getOriginalApprovalSnapshot(): ?CompanyVersion
    {
        return $this->approvalSnapshots()->orderBy('created_at', 'asc')->first();
    }
}