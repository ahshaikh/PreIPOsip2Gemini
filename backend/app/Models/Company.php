<?php
// V-COMPANY-INTEGRATION-1210 (Product Relationship Added)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
     * V-COMPANY-INTEGRATION-1210: Company has many products
     */
    public function products()
    {
        return $this->hasMany(Product::class);
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
