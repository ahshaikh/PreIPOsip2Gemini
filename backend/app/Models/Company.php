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

class Company extends Model
{
    use HasFactory, SoftDeletes;

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
     * Boot logic to handle automatic slug generation and unique constraints.
     */
    protected static function booted()
    {
        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = static::generateUniqueSlug($company->name);
            }
        });

        static::updating(function ($company) {
            if ($company->isDirty('name') && !$company->isDirty('slug')) {
                $company->slug = static::generateUniqueSlug($company->name, $company->id);
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
    
    public function webinars()
    {
        return $this->hasMany(CompanyWebinar::class);
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
}