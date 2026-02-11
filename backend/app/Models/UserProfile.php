<?php
// V-PHASE1-1730-009 (created) | V-FINAL-1730-323 (With Accessors & Casts)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

/**
 * @mixin IdeHelperUserProfile
 */
class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name', // V-FIX-PROFILE-ENHANCEMENT
        'last_name',
        'mother_name', // V-FIX-PROFILE-ENHANCEMENT
        'wife_name', // V-FIX-PROFILE-ENHANCEMENT
        'dob',
        'gender',
        'occupation', // V-FIX-PROFILE-ENHANCEMENT
        'education', // V-FIX-PROFILE-ENHANCEMENT
        'social_links', // V-FIX-PROFILE-ENHANCEMENT
        'address',
        'city',
        'state',
        'pincode',
        'avatar_url',
        'preferences'
    ];

    protected $casts = [
        'dob' => 'date', // Automatically casts to Carbon instance
        'preferences' => 'array', // Automatically casts JSON to Array
        'social_links' => 'array', // V-FIX-PROFILE-ENHANCEMENT: JSON cast
    ];

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // --- ACCESSORS ---

    /**
     * Get the full formatted address.
     */
    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $parts = [
                    $attributes['address'] ?? '',
                    $attributes['city'] ?? '',
                    $attributes['state'] ?? '',
                    $attributes['pincode'] ?? ''
                ];
                
                // Filter out empty parts and join with comma
                return implode(', ', array_filter($parts));
            }
        );
    }

    /**
     * Calculate age from DOB.
     */
    protected function age(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (empty($attributes['dob'])) {
                    return null;
                }
                return Carbon::parse($attributes['dob'])->age;
            }
        );
    }
}