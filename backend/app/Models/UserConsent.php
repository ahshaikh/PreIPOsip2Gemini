<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConsent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'consent_type',
        'consent_version',
        'consent_data',
        'ip_address',
        'user_agent',
        'granted_at',
        'revoked_at',
    ];

    protected $casts = [
        'consent_data' => 'array',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active consents
     */
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    /**
     * Scope for revoked consents
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Scope by consent type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('consent_type', $type);
    }

    /**
     * Check if consent is currently valid
     */
    public function isValid(): bool
    {
        return $this->revoked_at === null;
    }

    /**
     * Check if consent needs renewal (version mismatch)
     */
    public function needsRenewal($currentVersion): bool
    {
        return $this->consent_version !== $currentVersion;
    }
}
