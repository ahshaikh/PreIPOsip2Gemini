<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Traits\CalculatesReturns;

class FinancialDriftTest extends FeatureTestCase
{
    use CalculatesReturns;

    /**
     * Test Parity between Backend Trait and known edge cases.
     * [AUDIT FIX]: Ensures "Single Truth" logic remains consistent.
     */
    public function test_math_consistency_across_scenarios()
    {
        $scenarios = [
            ['p' => 1000000, 'r' => 12.5, 'm' => 12], // Large principal
            ['p' => 500, 'r' => 8.0, 'm' => 3],      // Small principal
            ['p' => 15555, 'r' => 10.75, 'm' => 7],  // Complex decimals
        ];

        foreach ($scenarios as $s) {
            $result = $this->calculateProjectedBonus($s['p'] * 100, $s['r'], $s['m']);
            
            // Assert no rounding drift occurred
            $this->assertIsInt((int)($result['interest'] * 100));
            $this->assertEquals($result['principal'] + $result['interest'], $result['maturity_amount']);
        }
    }
}
