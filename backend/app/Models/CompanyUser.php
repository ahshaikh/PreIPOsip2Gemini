<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Traits\LogsStateChanges;

/**
 * FIX 19: Company User with Email Verification
 *
 * Implements MustVerifyEmail to require email verification before approval
 * Uses LogsStateChanges for audit trail
 */
class CompanyUser extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, LogsStateChanges, HasRoles;

    /**
     * The guard name for this model.
     *
     * @var string
     */
    public string $guard_name = 'company_api';

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
     * FIX 19: State fields to track for audit logging
     */
    protected static $stateFields = ['status', 'is_verified'];

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
     * FIX 19: Check if email has been verified
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
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
     * FIX 19: Approve company user (with email verification check)
     *
     * @throws \RuntimeException if email not verified
     */
    public function approve(): void
    {
        if (!$this->hasVerifiedEmail()) {
            throw new \RuntimeException(
                'Cannot approve company user: Email not verified. User must verify their email first.'
            );
        }

        if ($this->status === 'active' && $this->is_verified) {
            throw new \RuntimeException('Company user is already approved');
        }

        $this->update([
            'status' => 'active',
            'is_verified' => true,
            'rejection_reason' => null,
        ]);

        \Log::info('Company user approved', [
            'company_user_id' => $this->id,
            'email' => $this->email,
            'company_id' => $this->company_id,
        ]);
    }

    /**
     * FIX 19: Reject company user
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'is_verified' => false,
            'rejection_reason' => $reason,
        ]);

        \Log::info('Company user rejected', [
            'company_user_id' => $this->id,
            'email' => $this->email,
            'reason' => $reason,
        ]);
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
