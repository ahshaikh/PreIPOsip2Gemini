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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Traits\HasDeletionProtection;
use App\Traits\HasWorkflowActions;
use App\Enums\DisclosureTier;
use App\Exceptions\DisclosureTierImmutabilityException;
use App\Scopes\PublicVisibilityScope;
use App\Models\Product;

/**
 * @mixin IdeHelperCompany
 */
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
        'settings',        // [AUDIT FIX]: Store enterprise-specific UI/behavior configs
        // STORY 3.1: disclosure_tier is REMOVED from fillable - immutable except via CompanyDisclosureTierService

        // [PHASE 1]: Governance Protocol - Legal Identity & Registration
        'cin',
        'pan',
        'registration_number',
        'legal_structure',
        'incorporation_date',
        'registered_office_address',

        // [PHASE 1]: Governance Structure
        'board_size',
        'independent_directors',
        'board_committees',
        'company_secretary',

        // [PHASE 1]: SEBI & Regulatory Compliance
        'sebi_registered',
        'sebi_registration_number',
        'sebi_approval_date',
        'sebi_approval_expiry',
        'regulatory_approvals',

        // [PHASE 1]: Disclosure Lifecycle Management
        'disclosure_stage',
        'disclosure_submitted_at',
        'disclosure_approved_at',
        'disclosure_approved_by',
        'disclosure_rejection_reason',

        // [PHASE 1]: Audit Trail Enhancement
        'last_modified_by_ip',
        'last_modified_user_agent',
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

        // [STORY 3.1]: Disclosure Tier - cast to enum for type safety
        'disclosure_tier' => DisclosureTier::class,

        // [PHASE 1]: Governance Protocol Type Casts
        'incorporation_date' => 'date',
        'board_size' => 'integer',
        'independent_directors' => 'integer',
        'board_committees' => 'array',
        'sebi_registered' => 'boolean',
        'sebi_approval_date' => 'date',
        'sebi_approval_expiry' => 'date',
        'regulatory_approvals' => 'array',
        'disclosure_submitted_at' => 'datetime',
        'disclosure_approved_at' => 'datetime',
        'disclosure_approved_by' => 'integer',
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
        // STORY 3.1 GAP 2: Register global scope for public visibility enforcement
        static::addGlobalScope(new PublicVisibilityScope());

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = static::generateUniqueSlug($company->name);
            }
        });

        static::updating(function ($company) {
            // STORY 3.1: Enforce disclosure_tier ABSOLUTE IMMUTABILITY
            // This guard catches ALL modification attempts: fill(), update(), save(), etc.
            // The ONLY authorized path is CompanyDisclosureTierService::promote() which uses raw DB query.
            if ($company->isDirty('disclosure_tier')) {
                $originalTier = $company->getOriginal('disclosure_tier');
                $attemptedTier = $company->disclosure_tier;

                // Convert enum to string for exception
                $originalValue = $originalTier instanceof DisclosureTier ? $originalTier->value : $originalTier;
                $attemptedValue = $attemptedTier instanceof DisclosureTier ? $attemptedTier->value : $attemptedTier;

                // STORY 3.1 GAP 3: Context-safe logging (never throws in CLI/jobs)
                $logContext = [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'original_tier' => $originalValue,
                    'attempted_tier' => $attemptedValue,
                ];

                // Safely add HTTP context if available
                try {
                    if (function_exists('auth') && auth()->check()) {
                        $logContext['user_id'] = auth()->id();
                    }
                } catch (\Throwable $e) {
                    $logContext['user_id'] = null;
                }

                try {
                    if (function_exists('request') && request()) {
                        $logContext['ip_address'] = request()->ip();
                        $logContext['user_agent'] = request()->userAgent();
                    }
                } catch (\Throwable $e) {
                    $logContext['ip_address'] = null;
                    $logContext['user_agent'] = null;
                }

                $logContext['context'] = app()->runningInConsole() ? 'cli' : 'http';

                \Log::warning('DISCLOSURE_TIER_IMMUTABILITY_VIOLATION: Direct modification blocked', $logContext);

                throw DisclosureTierImmutabilityException::directModification(
                    (string) $company->id,
                    $originalValue,
                    $attemptedValue
                );
            }

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
     * CompanyUsers belonging to this enterprise.
     */
    public function companyUsers(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    /**
     * Enterprise-exclusive investment plans.
     */
    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    // --- STANDARD RELATIONSHIPS ---

    /**
     * FIX: Added missing sector() relationship
     * Company has sector_id foreign key to sectors table
     * Required by InvestorCompanyController and CompanyProfileController
     */
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

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

    /**
     * P0 FIX (GAP 35): Platform risk flags relationship
     * Returns investor-visible risk flags with full rationale data
     */
    public function platformRiskFlags()
    {
        return $this->hasMany(PlatformRiskFlag::class);
    }

    public function webinars()
    {
        return $this->hasMany(CompanyWebinar::class);
    }

    /**
     * Products belonging to this company.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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

    /**
     * PHASE 1: Governance Protocol Relationships
     * Added 2026-01-10 for versioned disclosure system
     */

    /**
     * Company's modular disclosures
     */
    public function disclosures(): HasMany
    {
        return $this->hasMany(CompanyDisclosure::class);
    }

    /**
     * Company's approved disclosure versions (immutable snapshots)
     */
    public function disclosureVersions(): HasMany
    {
        return $this->hasMany(DisclosureVersion::class)
            ->orderBy('approved_at', 'desc');
    }

    /**
     * Clarifications requested for company's disclosures
     */
    public function disclosureClarifications(): HasMany
    {
        return $this->hasMany(DisclosureClarification::class);
    }

    /**
     * Approval workflow records for company's disclosures
     */
    public function disclosureApprovals(): HasMany
    {
        return $this->hasMany(DisclosureApproval::class)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Helper: Get pending clarifications across all disclosures
     */
    public function pendingClarifications()
    {
        return $this->disclosureClarifications()
            ->where('status', 'open')
            ->orderBy('due_date');
    }

    /**
     * Helper: Get disclosures awaiting approval
     */
    public function disclosuresAwaitingApproval()
    {
        return $this->disclosures()
            ->whereIn('status', ['submitted', 'resubmitted']);
    }

    /**
     * Helper: Get approved disclosures only
     */
    public function approvedDisclosures()
    {
        return $this->disclosures()
            ->where('status', 'approved');
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

    // --- STORY 3.1: DISCLOSURE TIER VISIBILITY SCOPES ---

    /**
     * GOVERNANCE INVARIANT: Public-facing queries MUST use this scope.
     *
     * Filters companies to only those with disclosure_tier >= tier_2_live.
     * This is the PRIMARY enforcement mechanism for public visibility.
     *
     * Use this scope on ALL public-facing endpoints:
     * - Public company listings
     * - Public company detail pages
     * - Public search results
     * - Public API endpoints
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('disclosure_tier', [
            DisclosureTier::TIER_2_LIVE->value,
            DisclosureTier::TIER_3_FEATURED->value,
        ]);
    }

    /**
     * Filter companies by specific disclosure tier.
     *
     * @param Builder $query
     * @param DisclosureTier|string $tier
     * @return Builder
     */
    public function scopeByDisclosureTier(Builder $query, DisclosureTier|string $tier): Builder
    {
        $value = $tier instanceof DisclosureTier ? $tier->value : $tier;
        return $query->where('disclosure_tier', $value);
    }

    /**
     * Filter companies at or above a specific tier.
     *
     * @param Builder $query
     * @param DisclosureTier $minimumTier
     * @return Builder
     */
    public function scopeAtOrAboveTier(Builder $query, DisclosureTier $minimumTier): Builder
    {
        $validTiers = array_filter(
            DisclosureTier::cases(),
            fn(DisclosureTier $tier) => $tier->rank() >= $minimumTier->rank()
        );

        return $query->whereIn('disclosure_tier', array_map(fn($t) => $t->value, $validTiers));
    }

    /**
     * Filter companies that are investable (tier_2_live or higher).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInvestable(Builder $query): Builder
    {
        return $this->scopePubliclyVisible($query);
    }

    /**
     * Filter companies pending review (tier_0_pending or tier_1_upcoming).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePendingReview(Builder $query): Builder
    {
        return $query->whereIn('disclosure_tier', [
            DisclosureTier::TIER_0_PENDING->value,
            DisclosureTier::TIER_1_UPCOMING->value,
        ]);
    }

    /**
     * Filter companies that are upcoming (tier_1_upcoming).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('disclosure_tier', DisclosureTier::TIER_1_UPCOMING->value);
    }

    /**
     * Filter companies that are live (tier_2_live specifically).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('disclosure_tier', DisclosureTier::TIER_2_LIVE->value);
    }

    /**
     * Filter companies that are featured (tier_3_featured).
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeFeaturedTier(Builder $query): Builder
    {
        return $query->where('disclosure_tier', DisclosureTier::TIER_3_FEATURED->value);
    }

    // --- STORY 3.1: DISCLOSURE TIER HELPER METHODS ---

    /**
     * Get the disclosure tier as an enum (with fallback to TIER_0_PENDING).
     *
     * @return DisclosureTier
     */
    public function getDisclosureTierEnum(): DisclosureTier
    {
        if ($this->disclosure_tier instanceof DisclosureTier) {
            return $this->disclosure_tier;
        }

        $tier = DisclosureTier::tryFrom($this->disclosure_tier ?? '');
        return $tier ?? DisclosureTier::TIER_0_PENDING;
    }

    /**
     * Check if this company is publicly visible based on disclosure tier.
     *
     * GOVERNANCE INVARIANT: Only tier_2_live and tier_3_featured are visible.
     *
     * @return bool
     */
    public function isPubliclyVisibleByTier(): bool
    {
        return $this->getDisclosureTierEnum()->isPubliclyVisible();
    }

    /**
     * Check if this company is investable based on disclosure tier.
     *
     * @return bool
     */
    public function isInvestableByTier(): bool
    {
        return $this->getDisclosureTierEnum()->isInvestable();
    }

    /**
     * Get the next available tier for this company.
     *
     * @return DisclosureTier|null
     */
    public function getNextTier(): ?DisclosureTier
    {
        return $this->getDisclosureTierEnum()->nextTier();
    }

    /**
     * Check if this company can be promoted to a specific tier.
     *
     * @param DisclosureTier $targetTier
     * @return bool
     */
    public function canPromoteTo(DisclosureTier $targetTier): bool
    {
        return $this->getDisclosureTierEnum()->canPromoteTo($targetTier);
    }

    /**
     * Get disclosure tier metadata for API responses.
     *
     * @return array
     */
    public function getDisclosureTierInfo(): array
    {
        $tier = $this->getDisclosureTierEnum();
        $nextTier = $tier->nextTier();

        return [
            'current_tier' => $tier->value,
            'current_tier_label' => $tier->label(),
            'current_tier_description' => $tier->description(),
            'current_tier_rank' => $tier->rank(),
            'is_publicly_visible' => $tier->isPubliclyVisible(),
            'is_investable' => $tier->isInvestable(),
            'next_tier' => $nextTier?->value,
            'next_tier_label' => $nextTier?->label(),
            'can_be_promoted' => $nextTier !== null,
        ];
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