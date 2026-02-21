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
    public const STATUS_SETTLED = 'settled'; // CORRECTION 5: Settlement state

    /**
     * V-PAYMENT-INTEGRITY-2026 HARDENING #6: Chargeback Statuses
     * Chargebacks are bank-initiated reversals, distinct from merchant-initiated refunds.
     *
     * Lifecycle:
     * - CHARGEBACK_PENDING: Bank has notified of dispute, funds under review
     * - CHARGEBACK_CONFIRMED: Bank has ruled in customer's favor, funds reversed
     *
     * Chargebacks can occur AFTER settlement (unlike refunds which require paid/settled status).
     */
    public const STATUS_CHARGEBACK_PENDING = 'chargeback_pending';
    public const STATUS_CHARGEBACK_CONFIRMED = 'chargeback_confirmed';

    /**
     * V-PAYMENT-INTEGRITY-2026: Valid state transitions
     * Key = current status, Value = array of valid next statuses
     *
     * CORRECTION 1: `failed` is now a TERMINAL state.
     * If retry is needed, a NEW Payment record must be created.
     * This prevents adversarial replay of failed payment identifiers.
     *
     * Settlement: `paid → settled` allowed when gateway confirms settlement.
     *
     * HARDENING #6: Chargeback transitions added.
     * - paid → chargeback_pending (bank dispute initiated)
     * - settled → chargeback_pending (bank dispute after settlement)
     * - chargeback_pending → chargeback_confirmed (bank rules for customer)
     * - chargeback_pending → paid/settled (bank rules for merchant)
     */
    public const VALID_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_PAID, self::STATUS_FAILED, self::STATUS_CANCELLED],
        self::STATUS_PROCESSING => [self::STATUS_PAID, self::STATUS_FAILED],
        self::STATUS_PAID => [self::STATUS_REFUNDED, self::STATUS_SETTLED, self::STATUS_CHARGEBACK_PENDING],
        self::STATUS_FAILED => [], // TERMINAL - retry requires new Payment record
        self::STATUS_REFUNDED => [], // Terminal
        self::STATUS_CANCELLED => [], // Terminal
        self::STATUS_SETTLED => [self::STATUS_REFUNDED, self::STATUS_CHARGEBACK_PENDING], // Settled can be refunded or charged back
        self::STATUS_CHARGEBACK_PENDING => [self::STATUS_CHARGEBACK_CONFIRMED, self::STATUS_PAID, self::STATUS_SETTLED], // Resolved either way
        self::STATUS_CHARGEBACK_CONFIRMED => [], // Terminal - funds reversed by bank
    ];

    /**
     * V-PAYMENT-INTEGRITY-2026: Terminal statuses (no further transitions)
     *
     * CORRECTION 1: `failed` is now terminal. Retry requires new Payment.
     * HARDENING #6: `chargeback_confirmed` is terminal.
     */
    public const TERMINAL_STATUSES = [
        self::STATUS_FAILED, // CORRECTION 1: Failed is terminal
        self::STATUS_REFUNDED,
        self::STATUS_CANCELLED,
        self::STATUS_CHARGEBACK_CONFIRMED, // HARDENING #6: Chargeback confirmed is terminal
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
        // HARDENING #6: Chargeback tracking fields
        'chargeback_initiated_at',
        'chargeback_confirmed_at',
        'chargeback_gateway_id',
        'chargeback_reason',
        'chargeback_amount_paise',
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
        'chargeback_amount_paise' => 'integer', // HARDENING #6
        'paid_at' => 'datetime',
        'settled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'chargeback_initiated_at' => 'datetime', // HARDENING #6
        'chargeback_confirmed_at' => 'datetime', // HARDENING #6
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
     *
     * P0-FIX-3: Must subtract BOTH prior refunds AND confirmed chargebacks
     * to prevent over-dispute (refund + chargeback > payment amount).
     */
    public function getRefundableAmountPaise(): int
    {
        $refunded = $this->refund_amount_paise ?? 0;

        // P0-FIX-3: Only count confirmed chargebacks (pending chargebacks may be reversed)
        $chargedBack = $this->isChargebackConfirmed() ? ($this->chargeback_amount_paise ?? 0) : 0;

        return max(0, $this->amount_paise - $refunded - $chargedBack);
    }

    // =========================================================================
    // CORRECTION 5: Settlement State Enforcement
    // =========================================================================

    /**
     * V-PAYMENT-INTEGRITY-2026 CORRECTION 5: Check if payment can be settled.
     *
     * Settlement requires:
     * - Payment status is 'paid' (not already settled/refunded)
     * - Gateway settlement confirmation received
     */
    public function canSettle(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * V-PAYMENT-INTEGRITY-2026 CORRECTION 5: Mark payment as settled.
     *
     * CRITICAL: This method MUST only be called when gateway confirms settlement.
     * DO NOT call this for arbitrary status updates.
     *
     * @param string $settlementId Gateway settlement reference
     * @throws \RuntimeException If payment cannot be settled
     */
    public function markAsSettled(string $settlementId): void
    {
        if (!$this->canSettle()) {
            Log::critical('SETTLEMENT STATE VIOLATION', [
                'payment_id' => $this->id,
                'current_status' => $this->status,
                'attempted_settlement_id' => $settlementId,
            ]);

            throw new \RuntimeException(
                "Cannot settle Payment #{$this->id}: current status is '{$this->status}', " .
                "expected 'paid'. Settlement requires 'paid' status."
            );
        }

        $this->update([
            'status' => self::STATUS_SETTLED,
            'settled_at' => now(),
            'settlement_id' => $settlementId,
            'settlement_status' => 'settled',
        ]);

        Log::info('PAYMENT SETTLED', [
            'payment_id' => $this->id,
            'settlement_id' => $settlementId,
            'settled_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * V-PAYMENT-INTEGRITY-2026 CORRECTION 5: Check if payment is settled.
     */
    public function isSettled(): bool
    {
        return $this->status === self::STATUS_SETTLED || $this->settlement_status === 'settled';
    }

    // =========================================================================
    // HARDENING #6: Chargeback State Machine
    // =========================================================================

    /**
     * V-PAYMENT-INTEGRITY-2026 HARDENING #6: Check if payment can receive chargeback.
     *
     * Chargebacks can occur on:
     * - paid payments (before settlement)
     * - settled payments (after funds transferred to merchant)
     *
     * Chargebacks CANNOT occur on:
     * - pending/processing (no funds captured)
     * - failed/cancelled (no funds captured)
     * - refunded (funds already returned)
     * - already in chargeback process
     */
    public function canChargeback(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_SETTLED]);
    }

    /**
     * V-PAYMENT-INTEGRITY-2026 HARDENING #6: Check if chargeback is pending.
     */
    public function isChargebackPending(): bool
    {
        return $this->status === self::STATUS_CHARGEBACK_PENDING;
    }

    /**
     * V-PAYMENT-INTEGRITY-2026 HARDENING #6: Check if chargeback is confirmed.
     */
    public function isChargebackConfirmed(): bool
    {
        return $this->status === self::STATUS_CHARGEBACK_CONFIRMED;
    }

    /**
     * V-PAYMENT-INTEGRITY-2026 HARDENING #6: Mark payment as chargeback pending.
     *
     * CRITICAL: This method MUST only be called when bank notifies of dispute.
     *
     * @param string $chargebackGatewayId Gateway chargeback/dispute reference
     * @param string $reason Reason for chargeback
     * @param int|null $amountPaise Disputed amount (defaults to full payment amount)
     * @throws \RuntimeException If payment cannot be charged back
     */
    public function markAsChargebackPending(
        string $chargebackGatewayId,
        string $reason,
        ?int $amountPaise = null
    ): void {
        if (!$this->canChargeback()) {
            Log::critical('CHARGEBACK STATE VIOLATION', [
                'payment_id' => $this->id,
                'current_status' => $this->status,
                'attempted_chargeback_id' => $chargebackGatewayId,
            ]);

            throw new \RuntimeException(
                "Cannot initiate chargeback for Payment #{$this->id}: current status is '{$this->status}'. " .
                "Chargeback requires 'paid' or 'settled' status."
            );
        }

        $this->update([
            'status' => self::STATUS_CHARGEBACK_PENDING,
            'chargeback_initiated_at' => now(),
            'chargeback_gateway_id' => $chargebackGatewayId,
            'chargeback_reason' => $reason,
            'chargeback_amount_paise' => $amountPaise ?? $this->amount_paise,
        ]);

        Log::channel('financial_contract')->warning('CHARGEBACK INITIATED', [
            'payment_id' => $this->id,
            'chargeback_gateway_id' => $chargebackGatewayId,
            'reason' => $reason,
            'amount_paise' => $amountPaise ?? $this->amount_paise,
            'user_id' => $this->user_id,
        ]);
    }

    /**
     * V-PAYMENT-INTEGRITY-2026 HARDENING #6: Confirm chargeback (bank ruled for customer).
     *
     * CRITICAL: This method MUST only be called when bank confirms chargeback.
     * After calling this, wallet and bonus reversals MUST be performed.
     *
     * @throws \RuntimeException If chargeback is not pending
     */
    /**
     * V-AUDIT-FIX-2026: Returns true if state transition occurred, false if already confirmed (idempotent).
     */
    public function confirmChargeback(): bool
    {
        // V-AUDIT-FIX-2026: Idempotent - already confirmed is OK (no-op)
        // This prevents duplicate webhook processing from throwing exceptions
        if ($this->isChargebackConfirmed()) {
            Log::info("Payment #{$this->id} chargeback already confirmed. Skipping state transition.");
            return false;
        }

        if (!$this->isChargebackPending()) {
            Log::critical('CHARGEBACK CONFIRMATION VIOLATION', [
                'payment_id' => $this->id,
                'current_status' => $this->status,
            ]);

            throw new \RuntimeException(
                "Cannot confirm chargeback for Payment #{$this->id}: current status is '{$this->status}'. " .
                "Expected 'chargeback_pending'."
            );
        }

        $this->update([
            'status' => self::STATUS_CHARGEBACK_CONFIRMED,
            'chargeback_confirmed_at' => now(),
        ]);

        Log::channel('financial_contract')->critical('CHARGEBACK CONFIRMED', [
            'payment_id' => $this->id,
            'chargeback_gateway_id' => $this->chargeback_gateway_id,
            'amount_paise' => $this->chargeback_amount_paise,
            'user_id' => $this->user_id,
            'action_required' => 'WALLET_AND_BONUS_REVERSAL',
        ]);

        return true;
    }

    /**
     * V-PAYMENT-INTEGRITY-2026 HARDENING #6: Resolve chargeback in merchant's favor.
     *
     * Bank ruled for merchant - restore original payment status.
     *
     * @param string $previousStatus Status to restore ('paid' or 'settled')
     * @throws \RuntimeException If chargeback is not pending
     */
    public function resolveChargebackInFavor(string $previousStatus): void
    {
        if (!$this->isChargebackPending()) {
            throw new \RuntimeException(
                "Cannot resolve chargeback for Payment #{$this->id}: not in chargeback_pending status."
            );
        }

        if (!in_array($previousStatus, [self::STATUS_PAID, self::STATUS_SETTLED])) {
            throw new \RuntimeException(
                "Invalid restore status '{$previousStatus}'. Must be 'paid' or 'settled'."
            );
        }

        $this->update([
            'status' => $previousStatus,
            // Keep chargeback metadata for audit trail
        ]);

        Log::channel('financial_contract')->info('CHARGEBACK RESOLVED IN FAVOR', [
            'payment_id' => $this->id,
            'chargeback_gateway_id' => $this->chargeback_gateway_id,
            'restored_status' => $previousStatus,
        ]);
    }

    // =========================================================================
    // CORRECTION 2: Integer Paise Enforcement (No Decimal Fallback)
    // =========================================================================

    /**
     * V-PAYMENT-INTEGRITY-2026 CORRECTION 2: Get authoritative amount in paise.
     *
     * CRITICAL: This method THROWS if amount_paise is null.
     * NO FALLBACK to float conversion. Money must NEVER depend on float.
     *
     * @throws \RuntimeException If amount_paise is null
     */
    public function getAmountPaiseStrict(): int
    {
        if ($this->amount_paise === null) {
            Log::critical('PAYMENT AMOUNT INTEGRITY VIOLATION', [
                'payment_id' => $this->id,
                'amount' => $this->amount,
                'amount_paise' => null,
                'error' => 'amount_paise is NULL - float fallback PROHIBITED',
            ]);

            throw new \RuntimeException(
                "Payment #{$this->id} has NULL amount_paise. " .
                "Float fallback is PROHIBITED. Data integrity violation."
            );
        }

        return $this->amount_paise;
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