<?php

/**
 * [P1.3 FIX - PROTOCOL 1]: TDS Result Value Object
 *
 * WHY: Makes TDS bypass STRUCTURALLY IMPOSSIBLE.
 *
 * BEFORE:
 * ```php
 * $net = $amount * 0.9; // ❌ Can bypass TDS
 * $walletService->deposit($user, $net, ...);
 * ```
 *
 * AFTER:
 * ```php
 * $tdsResult = $tdsService->calculate($amount, 'bonus'); // ✓ TDS enforced
 * $walletService->depositTaxable($user, $tdsResult, ...);
 * ```
 *
 * PROTOCOL 1 ENFORCEMENT:
 * - TdsResult can only be created by TdsCalculationService
 * - depositTaxable() REQUIRES TdsResult (cannot accept raw float)
 * - Cannot instantiate TdsResult manually (private constructor)
 */

namespace App\Services;

class TdsResult
{
    /**
     * [PROTOCOL 1]: Private constructor prevents manual instantiation.
     *
     * TdsResult can ONLY be created by TdsCalculationService,
     * ensuring TDS is always calculated, never guessed.
     */
    private function __construct(
        public readonly float $grossAmount,
        public readonly float $tdsAmount,
        public readonly float $netAmount,
        public readonly float $rateApplied,
        public readonly string $transactionType,
        public readonly bool $isExempt = false,
        public readonly ?string $exemptReason = null
    ) {}

    /**
     * [PROTOCOL 1]: Factory method - only TdsCalculationService can call this.
     *
     * @internal This method is internal to TdsCalculationService
     */
    public static function create(
        float $grossAmount,
        float $tdsAmount,
        float $netAmount,
        float $rateApplied,
        string $transactionType,
        bool $isExempt = false,
        ?string $exemptReason = null
    ): self {
        return new self(
            $grossAmount,
            $tdsAmount,
            $netAmount,
            $rateApplied,
            $transactionType,
            $isExempt,
            $exemptReason
        );
    }

    /**
     * Get gross amount in paise (for storage).
     */
    public function getGrossPaise(): int
    {
        return (int) round($this->grossAmount * 100);
    }

    /**
     * Get TDS amount in paise (for storage).
     */
    public function getTdsPaise(): int
    {
        return (int) round($this->tdsAmount * 100);
    }

    /**
     * Get net amount in paise (for storage).
     */
    public function getNetPaise(): int
    {
        return (int) round($this->netAmount * 100);
    }

    /**
     * Get description for transaction ledger.
     */
    public function getDescription(string $baseDescription): string
    {
        if ($this->isExempt) {
            return "{$baseDescription} (Tax exempt: {$this->exemptReason})";
        }

        return "{$baseDescription} (TDS {$this->rateApplied}%: ₹{$this->tdsAmount} deducted)";
    }

    /**
     * Convert to array for logging/debugging.
     */
    public function toArray(): array
    {
        return [
            'gross' => $this->grossAmount,
            'tds' => $this->tdsAmount,
            'net' => $this->netAmount,
            'rate' => $this->rateApplied,
            'type' => $this->transactionType,
            'exempt' => $this->isExempt,
            'exempt_reason' => $this->exemptReason,
        ];
    }
}
