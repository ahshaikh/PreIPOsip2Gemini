<?php
// V-PHASE1-1730-008 (Created) | V-FINAL-1730-321 | V-FINAL-1730-394 (Notif Prefs Added)

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
        'email_verified_at',
        'mobile_verified_at',
        'two_fa_enabled'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'two_fa_enabled' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
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

    // --- RELATIONSHIPS ---

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function kyc(): HasOne
    {
        return $this->hasOne(UserKyc::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }
    
    /**
     * NEW: User's notification preferences.
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(UserNotificationPreference::class);
    }
    
    /**
     * Helper to check a specific preference.
     */
    public function canReceiveNotification(string $key): bool
    {
        $pref = $this->notificationPreferences
                     ->where('preference_key', $key)
                     ->first();
        
        // Default to TRUE (opt-out)
        return $pref ? $pref->is_enabled : true;
    }

    // ... (all other relationships: activityLogs, otps, tickets, etc. remain here) ...
    public function activityLogs(): HasMany { return $this->hasMany(ActivityLog::class); }
    public function otps(): HasMany { return $this->hasMany(Otp::class); }
    public function tickets(): HasMany { return $this->hasMany(SupportTicket::class); }
    public function subscription(): HasOne { return $this->hasOne(Subscription::class)->latest(); }
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }
    public function investments(): HasMany { return $this->hasMany(UserInvestment::class); }
    public function bonuses(): HasMany { return $this->hasMany(BonusTransaction::class); }
    public function referrals(): HasMany { return $this->hasMany(Referral::class, 'referrer_id'); }
    public function referrer(): HasOne { return $this->hasOne(Referral::class, 'referred_id'); }
}