<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyUpdate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'created_by',
        'title',
        'content',
        'update_type',
        'media',
        'is_featured',
        'status',
        'published_at',
    ];

    protected $casts = [
        'media' => 'array',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Update belongs to a Company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relationship: Update created by CompanyUser
     */
    public function createdBy()
    {
        return $this->belongsTo(CompanyUser::class, 'created_by');
    }

    /**
     * Scope: Published updates
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope: Featured updates
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Updates by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('update_type', $type);
    }
}
