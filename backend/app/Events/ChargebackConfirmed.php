<?php

/**
 * V-DISPUTE-RISK-2026-003: Chargeback Confirmed Domain Event
 *
 * Fired AFTER successful chargeback processing (all reversals complete).
 * Triggers risk profile update for the user.
 *
 * CRITICAL: This event is dispatched INSIDE the DB::transaction that processes
 * the chargeback. Listeners should NOT start new transactions but should
 * participate in the existing one to maintain atomicity.
 *
 * IMMUTABLE: Event payload captures the state at time of chargeback confirmation.
 */

namespace App\Events;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChargebackConfirmed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The payment that received the chargeback.
     */
    public Payment $payment;

    /**
     * The user who initiated the chargeback.
     */
    public User $user;

    /**
     * Chargeback amount in paise (immutable snapshot).
     */
    public int $chargebackAmountPaise;

    /**
     * Chargeback reason from gateway (immutable snapshot).
     */
    public ?string $reason;

    /**
     * Timestamp when chargeback was confirmed.
     */
    public string $confirmedAt;

    /**
     * Create a new event instance.
     *
     * @param Payment $payment The payment that received the chargeback
     * @param User $user The user associated with the payment
     * @param int $chargebackAmountPaise Amount in paise
     * @param string|null $reason Reason code from gateway
     */
    public function __construct(
        Payment $payment,
        User $user,
        int $chargebackAmountPaise,
        ?string $reason = null
    ) {
        $this->payment = $payment;
        $this->user = $user;
        $this->chargebackAmountPaise = $chargebackAmountPaise;
        $this->reason = $reason;
        $this->confirmedAt = now()->toIso8601String();
    }
}
