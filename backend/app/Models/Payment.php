<?php
// // V-PHASE3-1730-074 (Created) | V-FINAL-1730-335

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

/**
 * @mixin IdeHelperPayment
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'amount',
        'currency',
        'status', // pending, paid, failed, refunded
        'gateway', // razorpay, stripe, manual
        'gateway_order_id',
        'gateway_payment_id',
        'gateway_signature',
        'method', // card, upi, netbanking
        'payment_method', // upi, card, netbanking, wallet
        'payment_metadata',
        'payment_proof_path', // For manual bank transfer proofs
        'paid_at',
        'refunded_at',
        'refunded_by',
        'is_on_time',
        'is_flagged',
        'flag_reason',
        'retry_count',
        'failure_reason',
        'payment_type',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'is_on_time' => 'boolean',
        'is_flagged' => 'boolean',
        'payment_metadata' => 'array',
    ];

    // --- RELATIONSHIPS ---

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * V-FIX: Relationship for bonus transactions linked to this payment.
     * Used by Admin PaymentController for refund bonus reversals.
     */
    public function bonuses()
    {
        return $this->hasMany(BonusTransaction::class);
    }

    /**
     * Relationship for user investments linked to this payment.
     * Used by AllocationService for refund allocation reversals.
     */
    public function investments()
    {
        return $this->hasMany(UserInvestment::class);
    }

    /**
     * FIX 44: Relationship for payment sagas
     * Tracks multi-step payment operations
     */
    public function sagas()
    {
        return $this->hasMany(PaymentSaga::class);
    }

    /**
     * FIX 44: Get current active saga for this payment
     */
    public function currentSaga()
    {
        return $this->hasOne(PaymentSaga::class)->latest();
    }

    // --- SCOPES ---

    public function scopePaid(Builder $query): void
    {
        $query->where('status', 'paid');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    public function scopeFailed(Builder $query): void
    {
        $query->where('status', 'failed');
    }

    // --- LOGIC ---

    /**
     * Determine if the payment was made on time based on the subscription schedule.
     * This allows re-checking logic even after the fact.
     */
    public function verifyOnTimeStatus(): bool
    {
        if (!$this->paid_at || !$this->subscription) {
            return false;
        }

        // Grace period from settings (default 2 days)
        $gracePeriod = 2; 
        $dueDate = $this->subscription->next_payment_date;

        // Payment is on time if paid_at <= due_date + grace_period
        // Note: This logic assumes the payment corresponds to the *current* next_payment_date
        // In a complex system, we'd link payments to specific billing cycles.
        return $this->paid_at->lte(Carbon::parse($dueDate)->addDays($gracePeriod));
    }

    /**
     * Get the calculated due date for this payment.
     * Usually the created_at date for scheduled payments.
     */
    protected function dueDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at // Simplified: Payment is "due" when created
        );
    }

    // --- FIX 44, 45: SAGA MANAGEMENT METHODS ---

    /**
     * FIX 44: Create a new saga for this payment
     */
    public function createSaga(array $sagaData = []): PaymentSaga
    {
        return PaymentSaga::createForPayment($this, $sagaData);
    }

    /**
     * FIX 44: Check if payment has an active saga
     */
    public function hasActiveSaga(): bool
    {
        return $this->sagas()->whereIn('status', ['pending', 'in_progress', 'rolling_back'])->exists();
    }

    /**
     * FIX 44: Get active saga or create new one
     */
    public function getOrCreateSaga(array $sagaData = []): PaymentSaga
    {
        $activeSaga = $this->sagas()
            ->whereIn('status', ['pending', 'in_progress'])
            ->latest()
            ->first();

        if ($activeSaga) {
            return $activeSaga;
        }

        return $this->createSaga($sagaData);
    }

    /**
     * FIX 45: Trigger rollback for failed payment saga
     */
    public function rollbackFailedSaga(): ?PaymentSaga
    {
        $failedSaga = $this->sagas()
            ->where('status', 'failed')
            ->latest()
            ->first();

        if ($failedSaga && $failedSaga->canRollback()) {
            $failedSaga->rollback();
            return $failedSaga;
        }

        return null;
    }
}