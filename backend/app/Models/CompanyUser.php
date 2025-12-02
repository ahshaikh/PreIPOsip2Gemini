<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class CompanyUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'email',
        'password',
        'contact_person_name',
        'contact_person_designation',
        'phone',
        'status',
        'is_verified',
        'email_verified_at',
        'rejection_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Company User belongs to a Company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relationship: Financial Reports uploaded by this user
     */
    public function financialReports()
    {
        return $this->hasMany(CompanyFinancialReport::class, 'uploaded_by');
    }

    /**
     * Relationship: Documents uploaded by this user
     */
    public function documents()
    {
        return $this->hasMany(CompanyDocument::class, 'uploaded_by');
    }

    /**
     * Relationship: Updates created by this user
     */
    public function updates()
    {
        return $this->hasMany(CompanyUpdate::class, 'created_by');
    }

    /**
     * Check if company user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->is_verified;
    }

    /**
     * Check if company user is pending approval
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Scope: Active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_verified', true);
    }

    /**
     * Scope: Pending users
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
