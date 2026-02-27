<?php
// V-PHASE1-1730-008 (Created) | V-FINAL-1730-321 | V-FINAL-1730-394 (Notif Prefs Added) | V-FINAL-1730-468 (2FA Added) | V-AUDIT-FIX-REFACTOR (Relationships Added) | V-DISPUTE-RISK-2026-001 (Risk Fields)

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'username',
        'email',
        'mobile',
        'password',
        'referral_code',
        'referred_by',
        'status',
        // 'kyc_status' - REMOVED: Now read from user_kyc relationship via accessor
        'email_verified_at',
        'mobile_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'suspension_reason',
        'suspended_at',
        'suspended_by',
        'block_reason',
        'blocked_at',
        'blocked_by',
        'is_blacklisted',
        'is_anonymized',
        'anonymized_at',
        // Risk profile fields (V-DISPUTE-RISK-2026-001)
        'risk_score',
        'is_blocked',
        'blocked_reason',
        'last_risk_update_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_recovery_codes' => 'array',
        // Risk profile casts (V-DISPUTE-RISK-2026-001)
        'risk_score' => 'integer',
        'is_blocked' => 'boolean',
        'last_risk_update_at' => 'datetime',
    ];

    protected $appends = [
        'role',
        'is_admin',
    ];

    /**
     * Get KYC status from canonical source (user_kyc table).
     *
     * ARCHITECTURAL FIX: Eliminates dual-state by reading from single source of truth.
     *
     * CRITICAL: This accessor is PURE - zero DB calls, zero lazy loading.
     * Callers MUST eager-load the 'kyc' relationship before accessing this property.
     * If relationship not loaded, returns 'pending' (safe default that blocks operations).
     *
     * @return string The KYC status value
     */
    public function getKycStatusAttribute(): string
    {
        // PURE accessor - explicitly check relationLoaded to prevent lazy loading
        if (!$this->relationLoaded('kyc')) {
            // Relationship not eager-loaded: return blocking default
            // This ensures payment/investment operations fail-safe
            return 'pending';
        }

        // Relationship loaded - read from it (no DB call)
        $kyc = $this->getRelation('kyc');

        if ($kyc === null) {
            // User has no KYC record → treat as pending (blocks operations)
            return 'pending';
        }

        $status = $kyc->status;

        // Handle KycStatus enum or plain string
        return is_object($status) && method_exists($status, 'value')
            ? $status->value
            : ($status ?? 'pending');
    }
    
    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->referral_code)) {
                do {
                    $code = strtoupper(Str::random(10));
                } while (User::where('referral_code', $code)->exists());
                $user->referral_code = $code;
            }
        });
    }

    // --- 2FA HELPERS ---
    
    /**
     * Get the QR Code URL for the authenticator app.
     */
    public function getTwoFactorQrCodeUrl(): string
    {
        $google2fa = app(Google2FA::class);
        $companyName = setting('site_name', 'PreIPO SIP');
        
        return $google2fa->getQRCodeUrl(
            $companyName,
            $this->email,
            decrypt($this->two_factor_secret)
        );
    }

    /**
     * Verify a 2FA code.
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        $google2fa = app(Google2FA::class);
        
        return $google2fa->verifyKey(
            decrypt($this->two_factor_secret),
            $code
        );
    }

    /**
     * Replace a used recovery code.
     */
    public function replaceRecoveryCode($code)
    {
        $codes = $this->two_factor_recovery_codes ?? [];
        
        $this->forceFill([
            'two_factor_recovery_codes' => collect($codes)->filter(fn ($c) => !hash_equals($c, $code))->all(),
        ])->save();
    }

    // --- RELATIONSHIPS ---

    public function profile(): HasOne { return $this->hasOne(UserProfile::class); }
    public function kyc(): HasOne { return $this->hasOne(UserKyc::class); }
    public function wallet(): HasOne { return $this->hasOne(Wallet::class); }
    public function activityLogs(): HasMany { return $this->hasMany(ActivityLog::class); }
    public function otps(): HasMany { return $this->hasMany(Otp::class); }
    public function tickets(): HasMany { return $this->hasMany(SupportTicket::class); }
    public function subscription(): HasOne { return $this->hasOne(Subscription::class)->latest(); }
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }
    public function investments(): HasMany { return $this->hasMany(Investment::class); }
    public function activeInvestments(): HasMany { return $this->investments()->where('status', 'active'); }

    /**
     * [P0.1 FIX]: Relationship to UserInvestment (actual share allocations from FIFO)
     * This is the real source of truth for portfolio holdings
     */
    public function userInvestments(): HasMany { return $this->hasMany(UserInvestment::class); }

    public function bonuses(): HasMany { return $this->hasMany(BonusTransaction::class); }
    public function referrals(): HasMany { return $this->hasMany(Referral::class, 'referrer_id'); }
    public function referrer(): HasOne { return $this->hasOne(Referral::class, 'referred_id'); }
    public function notificationPreferences(): HasMany { return $this->hasMany(UserNotificationPreference::class); }

    /**
     * Determine if the user can receive a particular notification.
     */
    public function canReceiveNotification(string $key): bool {
        $pref = $this->notificationPreferences->where('preference_key', $key)->first();
        return $pref ? $pref->is_enabled : true;
    }

    /**
     * Get the user's primary role name.
     * Returns the first role name, or 'user' if no roles assigned.
     */
    public function getRoleAttribute(): string
    {
        return $this->roles->first()?->name ?? 'user';
    }

    /**
     * Check if the user is an admin (has admin or super-admin role).
     *
     * FIX: Changed 'superadmin' to 'super-admin' to match actual role name in database.
     * Routes use 'role:admin|super-admin', so this accessor must match.
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->hasAnyRole(['admin', 'super-admin', 'superadmin']);
    }

    /**
     * 🔥 ADDED — Required for Lucky Draw Seeder
     * Users CAN have many payments because payments table has a user_id field.
     * Without this relationship, any call like $user->payments() will fail.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * [AUDIT FIX]: Added missing relationship for Withdrawals.
     * Required by WalletController::withdrawals()
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Campaign relationships
     */
    public function createdCampaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    public function approvedCampaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'approved_by');
    }

    public function campaignUsages(): HasMany
    {
        return $this->hasMany(CampaignUsage::class);
    }

    /**
     * Tickets assigned to this user (for support agents).
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }
}