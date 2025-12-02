<?php

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
    ];

    public function deals()
    {
        return $this->hasMany(Deal::class, 'company_name', 'name');
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
}
