<?php
// V-PHASE3-1730-073 (Created) | V-FINAL-1730-333 (Rich Domain Model)
// V-CONTRACT-HARDENING-CORRECTIVE: Enforced snapshot immutability

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Exceptions\SnapshotImmutabilityViolationException;
use Carbon\Carbon;

/**
 * @mixin IdeHelperSubscription
 */
class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * V-CONTRACT-HARDENING-CORRECTIVE: Immutable snapshot fields.
     * These fields CANNOT be modified after config_snapshot_at is set.
     */
    private const IMMUTABLE_SNAPSHOT_FIELDS = [
        'progressive_config',
        'milestone_config',
        'consistency_config',
        'welcome_bonus_config',
        'referral_tiers',
        'celebration_bonus_config',
        'lucky_draw_entries',
        'config_snapshot_at',
        'config_snapshot_version',
    ];

    protected $fillable = [
        'user_id',
        'plan_id',
        'subscription_code',
        'status',
        'amount_paise', // V-MONETARY-REFACTOR-2026: Authoritative integer paise
        'amount', // Legacy compatibility
        'start_date',
        'end_date',
        'next_payment_date',
        'bonus_multiplier',
        'consecutive_payments_count',
        'pause_count',
        'pause_start_date',
        'pause_end_date',
        'cancelled_at',
        'cancellation_reason',
        'is_auto_debit',
        'razorpay_subscription_id',
        // V-CONTRACT-HARDENING: Immutable bonus config snapshots
        // These are fillable ONLY during initial creation
        'progressive_config',
        'milestone_config',
        'consistency_config',
        'welcome_bonus_config',
        'referral_tiers',
        'celebration_bonus_config',
        'lucky_draw_entries',
        'config_snapshot_at',
        'config_snapshot_version',
        'bonus_contract_snapshot', // V-WAVE1-FIX: Legacy field for backward compatibility
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_payment_date' => 'date',
        'pause_start_date' => 'date',
        'pause_end_date' => 'date',
        'cancelled_at' => 'datetime',
        'is_auto_debit' => 'boolean',
        'amount_paise' => 'integer', // V-MONETARY-REFACTOR-2026
        'bonus_multiplier' => 'decimal:2',
        // V-CONTRACT-HARDENING: Immutable bonus config snapshots (JSON)
        'progressive_config' => 'json',
        'milestone_config' => 'json',
        'consistency_config' => 'json',
        'welcome_bonus_config' => 'json',
        'referral_tiers' => 'json',
        'celebration_bonus_config' => 'json',
        'lucky_draw_entries' => 'json',
        'config_snapshot_at' => 'datetime',
        'bonus_contract_snapshot' => 'json', // V-WAVE1-FIX: Legacy field for backward compatibility
    ];

    /**
     * V-CONTRACT-HARDENING-CORRECTIVE: Boot method to enforce immutability
     */
    protected static function booted()
    {
        // Enforce snapshot immutability on update
        static::updating(function (Subscription $subscription) {
            $subscription->enforceSnapshotImmutability();
        });
    }

    /**
     * V-CONTRACT-HARDENING-CORRECTIVE: Enforce that snapshot fields cannot be modified
     * after the snapshot has been created.
     *
     * @throws SnapshotImmutabilityViolationException If immutable fields are being modified
     */
    private function enforceSnapshotImmutability(): void
    {
        // Get the original value before any changes
        $originalSnapshotAt = $this->getOriginal('config_snapshot_at');

        // If snapshot was never set, allow changes (initial snapshot creation)
        if ($originalSnapshotAt === null) {
            return;
        }

        // Check which immutable fields are being modified
        $violatedFields = [];
        foreach (self::IMMUTABLE_SNAPSHOT_FIELDS as $field) {
            if ($this->isDirty($field)) {
                $violatedFields[] = $field;
            }
        }

        // If any immutable fields are being modified, throw exception
        if (!empty($violatedFields)) {
            throw new SnapshotImmutabilityViolationException(
                $this->id ?? 0,
                $violatedFields
            );
        }
    }

    /**
     * V-CONTRACT-HARDENING-CORRECTIVE: Check if snapshot has been created
     */
    public function hasSnapshot(): bool
    {
        return $this->config_snapshot_at !== null;
    }

    /**
     * V-CONTRACT-HARDENING-CORRECTIVE: Check if snapshot is valid (has all required fields)
     */
    public function hasValidSnapshot(): bool
    {
        if (!$this->hasSnapshot()) {
            return false;
        }

        // Must have version hash
        if (empty($this->config_snapshot_version)) {
            return false;
        }

        // Must have at least progressive_config (the minimum required config)
        if ($this->progressive_config === null) {
            return false;
        }

        return true;
    }

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(BonusTransaction::class);
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    /**
     * [P0.1 FIX]: Relationship to UserInvestment (actual share allocations)
     * This tracks the real FIFO-allocated shares from BulkPurchase inventory
     */
    public function userInvestments(): HasMany
    {
        return $this->hasMany(UserInvestment::class);
    }

    // --- ACCESSORS ---

    /**
     * [P2.1 WARNING]: N+1 Query Risk - Use eager loading instead.
     *
     * Calculate total months completed based on paid payments.
     *
     * @deprecated Use eager loading in controllers to avoid N+1 queries:
     * ```php
     * Subscription::withCount(['payments as months_completed' => function($q) {
     *     $q->where('status', 'paid');
     * }])->get();
     * ```
     *
     * WHY: Calling this accessor in a loop triggers a COUNT query for each subscription.
     * Example: 10 subscriptions = 10 queries instead of 1.
     */
    protected function monthsCompleted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payments()->where('status', 'paid')->count()
        );
    }

    /**
     * [P2.1 WARNING]: N+1 Query Risk - Use eager loading instead.
     *
     * Calculate total amount paid by user.
     *
     * @deprecated Use eager loading in controllers to avoid N+1 queries:
     * ```php
     * Subscription::withSum(['payments as total_paid' => function($q) {
     *     $q->where('status', 'paid');
     * }], 'amount')->get();
     * ```
     *
     * WHY: Calling this accessor in a loop triggers a SUM query for each subscription.
     */
    protected function totalPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payments()->where('status', 'paid')->sum('amount_paise') / 100 // Convert paise to rupees
        );
    }

    /**
     * [P2.1 WARNING]: N+1 Query Risk - Use eager loading instead.
     *
     * Calculate total amount invested from this subscription.
     * [P0.1 FIX]: Now queries UserInvestment (actual allocations) instead of Investment (deal tracking)
     *
     * @deprecated Use eager loading in controllers to avoid N+1 queries:
     * ```php
     * Subscription::withSum(['userInvestments as total_invested' => function($q) {
     *     $q->where('is_reversed', false);
     * }], 'value_allocated')->get();
     * ```
     *
     * WHY: Calling this accessor in a loop triggers a SUM query on userInvestments for each subscription.
     * Example: 10 subscriptions = 10 queries instead of 1.
     *
     * FIXED IN: DealController.php:94-103 (uses eager loading)
     */
    protected function totalInvested(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->userInvestments()->where('is_reversed', false)->sum('value_allocated')
        );
    }

    /**
     * [P2.1 WARNING]: N+1 Query Risk - Use eager loading instead.
     *
     * Calculate available balance for new investments.
     *
     * @deprecated Use eager loading + manual calculation in controllers to avoid N+1 queries:
     * ```php
     * $subscriptions = Subscription::with('plan')
     *     ->withSum(['userInvestments as total_invested' => function($q) {
     *         $q->where('is_reversed', false);
     *     }], 'value_allocated')
     *     ->get();
     *
     * foreach ($subscriptions as $subscription) {
     *     $totalValue = ($subscription->amount ?? $subscription->plan->monthly_amount)
     *         * ($subscription->plan->duration_months ?? 12);
     *     $subscription->available_balance = max(0, $totalValue - ($subscription->total_invested ?? 0));
     * }
     * ```
     *
     * WHY: This accessor calls $this->total_invested, which triggers a SUM query for each subscription.
     * Example: 10 subscriptions = 10 queries instead of 1.
     *
     * FIXED IN: DealController.php:94-110 (uses eager loading + manual calculation)
     */
    protected function availableBalance(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Total subscription value based on plan
                $totalValue = ($this->amount ?? $this->plan->monthly_amount) * ($this->plan->duration_months ?? 12);
                return max(0, $totalValue - $this->total_invested);
            }
        );
    }

    // --- DOMAIN ACTIONS ---

    /**
     * FIX 37: Check if user has sufficient wallet balance for subscription payment
     *
     * @param float|null $amount Optional amount to check (defaults to subscription amount)
     * @return bool True if sufficient balance, false otherwise
     */
    public function hasSufficientWalletBalance(?float $amount = null): bool
    {
        $requiredAmount = $amount ?? $this->amount ?? $this->plan->monthly_amount;
        $wallet = $this->user->wallet;

        if (!$wallet) {
            return false;
        }

        return $wallet->available_balance >= $requiredAmount;
    }

    /**
     * FIX 37: Validate wallet balance before payment
     *
     * @param float|null $amount Optional amount to validate
     * @throws \RuntimeException If insufficient balance
     */
    public function validateWalletBalance(?float $amount = null): void
    {
        if (!$this->hasSufficientWalletBalance($amount)) {
            $requiredAmount = $amount ?? $this->amount ?? $this->plan->monthly_amount;
            $availableBalance = $this->user->wallet ? $this->user->wallet->available_balance : 0;

            throw new \RuntimeException(
                "Insufficient wallet balance for subscription payment. " .
                "Required: ₹{$requiredAmount}, Available: ₹{$availableBalance}"
            );
        }
    }

    /**
     * Pause the subscription.
     * FIX 38: Added pause count validation
     */
    public function pause(int $months): void
    {
        if ($months < 1 || $months > 3) {
            throw new \InvalidArgumentException("Pause duration must be between 1 and 3 months.");
        }

        if ($this->status !== 'active') {
            throw new \DomainException("Only active subscriptions can be paused.");
        }

        // FIX 38: Check pause count limit (max 2 pauses per subscription)
        $maxPauses = (int) setting('subscription_max_pause_count', 2);
        if ($this->pause_count >= $maxPauses) {
            throw new \DomainException(
                "Subscription has reached maximum pause limit ({$maxPauses} times). " .
                "You cannot pause this subscription again."
            );
        }

        // Shift dates
        $newNextPayment = $this->next_payment_date->copy()->addMonths($months);
        $newEndDate = $this->end_date->copy()->addMonths($months);

        $this->update([
            'status' => 'paused',
            'pause_start_date' => now(),
            'pause_end_date' => now()->addMonths($months),
            'next_payment_date' => $newNextPayment,
            'end_date' => $newEndDate,
            'pause_count' => $this->pause_count + 1, // FIX 38: Increment pause count
        ]);

        \Log::info('Subscription paused', [
            'subscription_id' => $this->id,
            'user_id' => $this->user_id,
            'pause_count' => $this->pause_count + 1,
            'pause_months' => $months,
        ]);
    }

    /**
     * Resume the subscription.
     */
    public function resume(): void
    {
        if ($this->status !== 'paused') {
            throw new \DomainException("Subscription is not paused.");
        }

        $this->update([
            'status' => 'active',
            'pause_start_date' => null,
            'pause_end_date' => null,
        ]);
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'is_auto_debit' => false
        ]);
    }
}
