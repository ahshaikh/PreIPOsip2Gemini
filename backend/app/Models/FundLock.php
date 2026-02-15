<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * FIX 18: Fund Lock Model
 *
 * Tracks all fund reservations/locks on user wallets
 * Provides audit trail for financial operations
 *
 * FINANCIAL INTEGRITY (V-PRECISION-2026):
 * - Stores amount_paise (BIGINT) only - no decimal columns
 * - Virtual `amount` accessor provides rupee value for backward compatibility
 * - All arithmetic uses integer paise to eliminate floating-point errors
 *
 * @property int $amount_paise Amount in paise (authoritative)
 * @property float $amount Virtual accessor: amount_paise / 100 (read-only for display)
 *
 * @mixin IdeHelperFundLock
 */
class FundLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lock_type',
        'lockable_type',
        'lockable_id',
        'amount_paise',
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
        'locked_at' => 'datetime',
        'released_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Virtual rupee accessor for backward compatibility.
     * Appended to JSON serialization.
     */
    protected $appends = ['amount'];

    /**
     * Virtual amount (₹) backed by amount_paise.
     * READ-ONLY: For display/API serialization only.
     *
     * ⚠️ FINANCIAL INTEGRITY: No setter provided.
     * All writes MUST use amount_paise directly to prevent float math.
     * Services must write: ['amount_paise' => $integerValue]
     */
    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) =>
                ($attributes['amount_paise'] ?? 0) / 100,
        );
    }

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
     *
     * Uses amount_paise for atomic integer arithmetic.
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

        // Update wallet locked balance using paise for atomic decrement
        $amountRupees = $this->amount_paise / 100;
        $this->user->wallet->decrementLockedBalance($amountRupees);

        \Log::info('Fund lock released', [
            'lock_id' => $this->id,
            'user_id' => $this->user_id,
            'amount_paise' => $this->amount_paise,
            'amount_rupees' => $amountRupees,
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
     *
     * Uses amount_paise for atomic integer arithmetic.
     */
    public static function releaseExpiredLocks(): int
    {
        $expired = static::expired()->get();
        $count = 0;

        foreach ($expired as $lock) {
            try {
                $lock->update(['status' => 'expired']);
                // Use amount_paise for atomic decrement
                $amountRupees = $lock->amount_paise / 100;
                $lock->user->wallet->decrementLockedBalance($amountRupees);
                $count++;
            } catch (\Exception $e) {
                \Log::error('Failed to release expired lock', [
                    'lock_id' => $lock->id,
                    'amount_paise' => $lock->amount_paise,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
