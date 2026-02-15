<?php
// V-PHASE3-1730-074 (Created) | V-FINAL-1730-335
// V-PAYMENT-INTEGRITY-2026: State Machine + Paise + Settlement Tracking

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Payment Model with External Boundary Integrity
 *
 * V-PAYMENT-INTEGRITY-2026:
 * - State machine enforcement (no backward transitions)
 * - Integer paise storage (amount_paise is authoritative)
 * - Settlement tracking for reconciliation
 * - Refund bounds validation
 *
 * STATE MACHINE:
 * pending → processing → paid → settled
 * pending → failed
 * paid → refunded (partial or full)
 * NO BACKWARD TRANSITIONS ALLOWED
 *
 * @property int $amount_paise Authoritative amount in paise
 * @property float $amount Virtual accessor (amount_paise / 100)
 * @property string $status Current status
 * @property string $settlement_status Settlement status (pending, settled)
 *
 * @mixin IdeHelperPayment
 */
class Payment extends Model
{
    use HasFactory;

    /**
     * V-PAYMENT-INTEGRITY-2026: Valid payment statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * V-PAYMENT-INTEGRITY-2026: Valid state transitions
     * Key = current status, Value = array of valid next statuses
     */
    public const VALID_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_PAID, self::STATUS_FAILED, self::STATUS_CANCELLED],
        self::STATUS_PROCESSING => [self::STATUS_PAID, self::STATUS_FAILED],
        self::STATUS_PAID => [self::STATUS_REFUNDED], // Terminal except for refunds
        self::STATUS_FAILED => [self::STATUS_PENDING], // Can retry
        self::STATUS_REFUNDED => [], // Terminal
        self::STATUS_CANCELLED => [], // Terminal
    ];

    /**
     * V-PAYMENT-INTEGRITY-2026: Terminal statuses (no further transitions)
     */
    public const TERMINAL_STATUSES = [
        self::STATUS_REFUNDED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'user_id',
        'subscription_id',
        'amount',
        'amount_paise', // V-PRECISION-2026: Authoritative integer storage
        'currency',
        'expected_currency', // V-PAYMENT-INTEGRITY-2026: For validation
        'status',
        'gateway',
        'gateway_order_id',
        'gateway_payment_id',
        'gateway_signature',
        'method',
        'payment_method',
        'payment_metadata',
        'payment_proof_path',
        'paid_at',
        'settled_at', // V-PAYMENT-INTEGRITY-2026: Settlement tracking
        'settlement_id',
        'settlement_status',
        'refunded_at',
        'refunded_by',
        'refund_amount_paise', // V-PAYMENT-INTEGRITY-2026: Track refunded amount
        'refund_gateway_id',
        'is_on_time',
        'is_flagged',
        'flag_reason',
        'retry_count',
        'failure_reason',
        'payment_type',
    ];

    protected $casts = [
        'amount_paise' => 'integer',
        'refund_amount_paise' => 'integer',
        'paid_at' => 'datetime',
        'settled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'is_on_time' => 'boolean',
        'is_flagged' => 'boolean',
        'payment_metadata' => 'array',
    ];

    /**
     * Virtual rupee accessor for backward compatibility.
     */
    protected $appends = ['amount_rupees'];

    // =========================================================================
    // BOOT: State Machine Enforcement
    // =========================================================================

    protected static function booted(): void
    {
        static::updating(function (Payment $payment) {
            // V-PAYMENT-INTEGRITY-2026: Enforce state machine
            if ($payment->isDirty('status')) {
                $oldStatus = $payment->getOriginal('status');
                $newStatus = $payment->status;

                if (!$payment->isValidTransition($oldStatus, $newStatus)) {
                    Log::critical('PAYMENT STATE MACHINE VIOLATION', [
                        'payment_id' => $payment->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'valid_transitions' => self::VALID_TRANSITIONS[$oldStatus] ?? [],
                    ]);

                    throw new \RuntimeException(
                        "Invalid payment state transition: '{$oldStatus}' → '{$newStatus}'. " .
                        "Valid transitions from '{$oldStatus}': [" .
                        implode(', ', self::VALID_TRANSITIONS[$oldStatus] ?? []) . "]"
                    );
                }
            }
        });
    }

    /**
     * V-PAYMENT-INTEGRITY-2026: Check if transition is valid
     */
    public function isValidTransition(?string $from, string $to): bool
    {
        // Initial creation (no previous status)
        if ($from === null) {
            return in_array($to, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
        }

        $validNext = self::VALID_TRANSITIONS[$from] ?? [];
        return in_array($to, $validNext);
    }

    /**
     * V-PAYMENT-INTEGRITY-2026: Check if payment is in terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES);
    }

    /**
     * V-PAYMENT-INTEGRITY-2026: Check if payment can be refunded
     */
    public function canRefund(): bool
    {
        return $this->status === self::STATUS_PAID && !$this->isFullyRefunded();
    }

    /**
     * V-PAYMENT-INTEGRITY-2026: Check if fully refunded
     */
    public function isFullyRefunded(): bool
    {
        return $this->refund_amount_paise >= $this->amount_paise;
    }

    /**
     * V-PAYMENT-INTEGRITY-2026: Get remaining refundable amount in paise
     */
    public function getRefundableAmountPaise(): int
    {
        return max(0, $this->amount_paise - ($this->refund_amount_paise ?? 0));
    }

    // =========================================================================
    // VIRTUAL ACCESSORS (Paise → Rupees, READ-ONLY)
    // =========================================================================

    /**
     * Virtual amount (₹) backed by amount_paise.
     * READ-ONLY: For display/API serialization only.
     *
     * ⚠️ FINANCIAL INTEGRITY: No setter provided.
     * All writes MUST use amount_paise directly.
     */
    protected function amountRupees(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) =>
                ($attributes['amount_paise'] ?? 0) / 100,
        );
    }

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