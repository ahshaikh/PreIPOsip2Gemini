<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Traits\HasMonetaryFields;

/**
 * FIX 18: Fund Lock Model
 *
 * Tracks all fund reservations/locks on user wallets
 * Provides audit trail for financial operations
 */
class FundLock extends Model
{
    use HasFactory, HasMonetaryFields;

    protected $fillable = [
        'user_id',
        'lock_type',
        'lockable_type',
        'lockable_id',
        'amount_paise',
        'amount',
        'status',
        'locked_at',
        'released_at',
        'expires_at',
        'locked_by',
        'released_by',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'amount_paise' => 'integer',
        'amount' => 'decimal:2',
        'locked_at' => 'datetime',
        'released_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $monetaryFields = ['amount'];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('lock_type', $type);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Release this lock
     */
    public function release(?int $releasedBy = null, ?string $reason = null): bool
    {
        if ($this->status !== 'active') {
            throw new \RuntimeException("Lock is already {$this->status}");
        }

        $this->update([
            'status' => 'released',
            'released_at' => now(),
            'released_by' => $releasedBy ?? auth()->id(),
        ]);

        // Update wallet locked balance
        $this->user->wallet->decrementLockedBalance($this->amount);

        \Log::info('Fund lock released', [
            'lock_id' => $this->id,
            'user_id' => $this->user_id,
            'amount' => $this->amount,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Check if lock has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Auto-release expired locks (called by scheduled job)
     */
    public static function releaseExpiredLocks(): int
    {
        $expired = static::expired()->get();
        $count = 0;

        foreach ($expired as $lock) {
            try {
                $lock->update(['status' => 'expired']);
                $lock->user->wallet->decrementLockedBalance($lock->amount);
                $count++;
            } catch (\Exception $e) {
                \Log::error('Failed to release expired lock', [
                    'lock_id' => $lock->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
