<?php

namespace App\Services\Bonuses\Strategies;

use App\Models\User;

class MilestoneStrategy implements BonusStrategyInterface
{
    public function calculate(User $user, float $baseAmount): float
    {
        // Example: Add 10% extra if user has been active for > 1 year
        if ($user->created_at->diffInYears(now()) >= 1) {
            return $baseAmount * 1.10;
        }
        return $baseAmount;
    }

    public function getDescription(): string
    {
        return "Milestone Reward (Anniversary Boost)";
    }
}