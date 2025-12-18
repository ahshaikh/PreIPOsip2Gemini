<?php

namespace App\Services\Bonuses\Strategies;

use App\Models\User;

/**
 * BonusStrategyInterface
 * * [AUDIT FIX]: Defines the contract for all bonus calculation types.
 */
interface BonusStrategyInterface
{
    /**
     * Calculate the bonus amount based on specific strategy logic.
     */
    public function calculate(User $user, float $baseAmount): float;

    /**
     * Returns the human-readable description for the ledger.
     */
    public function getDescription(): string;
}