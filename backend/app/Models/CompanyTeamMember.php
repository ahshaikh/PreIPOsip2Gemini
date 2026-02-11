<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperCompanyTeamMember
 */
class CompanyTeamMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'designation',
        'bio',
        'photo_path',
        'linkedin_url',
        'twitter_url',
        'display_order',
        'is_key_member',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_key_member' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Team member belongs to a Company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope: Key members
     */
    public function scopeKeyMembers($query)
    {
        return $query->where('is_key_member', true);
    }

    /**
     * Scope: Ordered by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }
}
