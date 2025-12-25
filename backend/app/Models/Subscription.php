<?php
// V-PHASE3-1730-073 (Created) | V-FINAL-1730-333 (Rich Domain Model)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'plan_id',
        'subscription_code',
        'status',
        'amount',
        'billing_cycle',
        'auto_renew',
        'activated_at',
        'start_date',
        'end_date',
        'next_payment_date',
        'bonus_multiplier',
        'consecutive_payments_count',
        'pause_start_date',
        'pause_end_date',
        'cancelled_at',
        'cancellation_reason',
        'is_auto_debit',
        'razorpay_subscription_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_payment_date' => 'date',
        'pause_start_date' => 'date',
        'pause_end_date' => 'date',
        'cancelled_at' => 'datetime',
        'is_auto_debit' => 'boolean',
        'bonus_multiplier' => 'decimal:2',
    ];

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

    // --- ACCESSORS ---

    /**
     * Calculate total months completed based on paid payments.
     */
    protected function monthsCompleted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payments()->where('status', 'paid')->count()
        );
    }

    /**
     * Calculate total amount paid by user.
     */
    protected function totalPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payments()->where('status', 'paid')->sum('amount')
        );
    }

    /**
     * Calculate total amount invested from this subscription.
     */
    protected function totalInvested(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->investments()->whereIn('status', ['active', 'pending'])->sum('total_amount')
        );
    }

    /**
     * Calculate available balance for new investments.
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
     * Pause the subscription.
     */
    public function pause(int $months): void
    {
        if ($months < 1 || $months > 3) {
            throw new \InvalidArgumentException("Pause duration must be between 1 and 3 months.");
        }

        if ($this->status !== 'active') {
            throw new \DomainException("Only active subscriptions can be paused.");
        }

        // Shift dates
        $newNextPayment = $this->next_payment_date->copy()->addMonths($months);
        $newEndDate = $this->end_date->copy()->addMonths($months);

        $this->update([
            'status' => 'paused',
            'pause_start_date' => now(),
            'pause_end_date' => now()->addMonths($months),
            'next_payment_date' => $newNextPayment,
            'end_date' => $newEndDate
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