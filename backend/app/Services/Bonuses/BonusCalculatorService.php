<?php

namespace App\Services\Bonuses;

use App\Models\User;
use App\Enums\BonusType; // [AUDIT FIX]: Use Enums for type safety
use App\Services\Bonuses\Strategies\MilestoneStrategy;
use App\Services\Bonuses\Strategies\ProgressiveStrategy;
use Exception;

class BonusCalculatorService
{
    /**
     * Get the appropriate strategy based on the bonus type.
     */
    public function getStrategy(BonusType $type)
    {
        return match($type) {
            BonusType::MILESTONE => new MilestoneStrategy(),
            BonusType::PROGRESSIVE => new ProgressiveStrategy(),
            default => throw new Exception("Invalid Bonus Strategy"),
        };
    }

    /**
     * Simulate a bonus without persisting to the database.
     * * [AUDIT FIX]: Used by the new Simulator API.
     */
    public function simulate(User $user, BonusType $type, float $amount): array
    {
        $strategy = $this->getStrategy($type);
        $finalAmount = $strategy->calculate($user, $amount);

        return [
            'base_amount' => $amount,
            'final_amount' => $finalAmount,
            'description' => $strategy->getDescription(),
        ];
    }
}