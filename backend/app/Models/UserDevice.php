<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperUserDevice
 */
class UserDevice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'device_token',
        'device_type',
        'device_name',
        'device_model',
        'os_version',
        'app_version',
        'provider',
        'platform',
        'browser',
        'metadata',
        'is_active',
        'last_active_at',
        'registered_at',
        'token_refreshed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
        'registered_at' => 'datetime',
        'token_refreshed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeByDeviceType($query, $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Helper Methods
     */
    public function markAsActive()
    {
        $this->update([
            'is_active' => true,
            'last_active_at' => now(),
        ]);
    }

    public function markAsInactive()
    {
        $this->update(['is_active' => false]);
    }

    public function refreshToken($newToken)
    {
        $this->update([
            'device_token' => $newToken,
            'token_refreshed_at' => now(),
        ]);
    }
}
