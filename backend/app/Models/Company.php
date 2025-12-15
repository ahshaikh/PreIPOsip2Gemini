<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    ];

    protected $casts = [
        'latest_valuation' => 'decimal:2',
        'total_funding' => 'decimal:2',
        'key_metrics' => 'array',
        'investors' => 'array',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'profile_completed' => 'boolean',
    ];

    /**
     * Boot logic to handle automatic slug generation.
     * FIX: Module 13 - Fix Slug Collision (High)
     */
    protected static function booted()
    {
        static::creating(function ($company) {
            // Automatically generate a unique slug if not provided or empty
            if (empty($company->slug)) {
                $company->slug = static::generateUniqueSlug($company->name);
            }
        });

        static::updating(function ($company) {
            // Update slug if name changes, but keep it unique
            if ($company->isDirty('name') && !$company->isDirty('slug')) {
                $company->slug = static::generateUniqueSlug($company->name, $company->id);
            }
        });
    }

    /**
     * Generate a unique slug for the company.
     * FIX: Module 13 - Fix Slug Collision Logic
     * @param string $name
     * @param int|null $ignoreId
     * @return string
     */
    public static function generateUniqueSlug($name, $ignoreId = null)
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        // Check for existence, excluding current record if updating
        $query = static::where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        while ($query->exists()) {
            // FIX: Syntax error fixed. Expressions like ++ cannot be inside string interpolation.
            $slug = "{$original}-" . $count++;
            
            // Re-check with new slug
            $query = static::where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
        }

        return $slug;
    }

    // --- RELATIONSHIPS ---

    public function deals()
    {
        return $this->hasMany(Deal::class, 'company_name', 'name');
    }

    /**
     * Relationship: Company has many company users
     */
    public function companyUsers()
    {
        return $this->hasMany(CompanyUser::class);
    }

    /**
     * Relationship: Company has many financial reports
     */
    public function financialReports()
    {
        return $this->hasMany(CompanyFinancialReport::class);
    }

    /**
     * Relationship: Company has many documents
     */
    public function documents()
    {
        return $this->hasMany(CompanyDocument::class);
    }

    /**
     * Relationship: Company has many updates
     */
    public function updates()
    {
        return $this->hasMany(CompanyUpdate::class);
    }

    /**
     * Relationship: Company has many team members
     */
    public function teamMembers()
    {
        return $this->hasMany(CompanyTeamMember::class);
    }

    /**
     * Relationship: Company has many funding rounds
     */
    public function fundingRounds()
    {
        return $this->hasMany(CompanyFundingRound::class);
    }
    
    /**
     * Relationship: Company has many webinars
     */
    public function webinars()
    {
        return $this->hasMany(CompanyWebinar::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('status', 'active');
    }

    public function scopeBySector($query, $sector)
    {
        return $query->where('sector', $sector);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}