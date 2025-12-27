<?php

/**
 * [P1.1 FIX]: Renamed from BonusCalculatorService to eliminate duplicate class names.
 *
 * This service provides bonus simulation functionality without persisting to database.
 * The production bonus calculator is App\Services\BonusCalculatorService.
 *
 * @deprecated This class was renamed from BonusCalculatorService to BonusSimulatorService
 * @see \App\Services\BonusCalculatorService For production bonus calculations
 */

namespace App\Services\Bonuses;

use App\Models\User;
use App\Enums\BonusType; // [AUDIT FIX]: Use Enums for type safety
use App\Services\Bonuses\Strategies\MilestoneStrategy;
use App\Services\Bonuses\Strategies\ProgressiveStrategy;
use Exception;

class BonusSimulatorService
{
    /**
     * [PROTOCOL 1 FIX]: Hard guard - prevent usage in production.
     *
     * WHY: Makes accidental production usage IMPOSSIBLE.
     * Simulator is for testing/preview only. Using it in production
     * would mean bonuses are calculated but never credited.
     */
    public function __construct()
    {
        if (!app()->environment('local', 'testing')) {
            throw new \RuntimeException(
                'PROTOCOL 1 VIOLATION: BonusSimulatorService can only be used in local/testing environments. ' .
                'For production bonus calculations, use App\Services\BonusCalculatorService. ' .
                'Current environment: ' . app()->environment()
            );
        }
    }

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
     * [AUDIT FIX]: Used by the new Simulator API.
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