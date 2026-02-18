<?php

// V-DISPUTE-RISK-2026-TEST-001: Risk Scoring Service Unit Tests

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RiskScoringService;
use App\Models\User;
use App\Models\Payment;
use App\Models\Dispute;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RiskScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RiskScoringService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->service = new RiskScoringService();
        $this->user = User::factory()->create([
            'risk_score' => 0,
            'is_blocked' => false,
        ]);
    }

    // ==================== SCORE CALCULATION TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_score_returns_zero_for_clean_user()
    {
        $result = $this->service->calculateScore($this->user);

        $this->assertEquals(0, $result['score']);
        $this->assertEmpty($result['factors']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_score_includes_chargeback_base_weight()
    {
        // Create a confirmed chargeback payment
        $this->createChargebackPayment($this->user);

        $result = $this->service->calculateScore($this->user);

        $baseWeight = config('risk.weights.chargeback_base', 25);
        $this->assertGreaterThanOrEqual($baseWeight, $result['score']);
        $this->assertContains('chargeback_history', $result['factors']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_score_applies_repeat_weight_for_multiple_chargebacks()
    {
        // Create multiple chargebacks
        $this->createChargebackPayment($this->user);
        $this->createChargebackPayment($this->user);
        $this->createChargebackPayment($this->user);

        $result = $this->service->calculateScore($this->user);

        $baseWeight = config('risk.weights.chargeback_base', 25);
        $repeatWeight = config('risk.weights.chargeback_repeat', 15);
        $expectedMin = $baseWeight + (2 * $repeatWeight); // 1 base + 2 repeat

        $this->assertGreaterThanOrEqual($expectedMin, $result['score']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_score_caps_at_100()
    {
        // Create many chargebacks to exceed 100
        for ($i = 0; $i < 10; $i++) {
            $this->createChargebackPayment($this->user);
        }

        $result = $this->service->calculateScore($this->user);

        $this->assertEquals(100, $result['score']);
        $this->assertGreaterThan(100, $result['uncapped_score']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_score_is_deterministic()
    {
        $this->createChargebackPayment($this->user);

        $result1 = $this->service->calculateScore($this->user);
        $result2 = $this->service->calculateScore($this->user);

        $this->assertEquals($result1['score'], $result2['score']);
        $this->assertEquals($result1['factors'], $result2['factors']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_score_includes_new_account_factor()
    {
        // Create a new user (account age < 30 days)
        $newUser = User::factory()->create([
            'created_at' => now()->subDays(10),
        ]);

        $this->createChargebackPayment($newUser);

        $result = $this->service->calculateScore($newUser);

        $this->assertContains('new_account_risk', $result['factors']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_score_excludes_new_account_factor_for_established_users()
    {
        // Create an established user (account age > 30 days)
        $oldUser = User::factory()->create([
            'created_at' => now()->subDays(60),
        ]);

        $this->createChargebackPayment($oldUser);

        $result = $this->service->calculateScore($oldUser);

        $this->assertNotContains('new_account_risk', $result['factors']);
    }

    // ==================== THRESHOLD TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function should_block_returns_true_above_threshold()
    {
        $blockingThreshold = config('risk.thresholds.blocking', 70);

        $this->assertTrue($this->service->shouldBlock($blockingThreshold));
        $this->assertTrue($this->service->shouldBlock($blockingThreshold + 10));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function should_block_returns_false_below_threshold()
    {
        $blockingThreshold = config('risk.thresholds.blocking', 70);

        $this->assertFalse($this->service->shouldBlock($blockingThreshold - 1));
        $this->assertFalse($this->service->shouldBlock(0));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function is_high_risk_returns_correct_values()
    {
        $highRiskThreshold = config('risk.thresholds.high_risk', 50);

        $this->assertTrue($this->service->isHighRisk($highRiskThreshold));
        $this->assertTrue($this->service->isHighRisk($highRiskThreshold + 10));
        $this->assertFalse($this->service->isHighRisk($highRiskThreshold - 1));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_risk_category_returns_correct_category()
    {
        $blockingThreshold = config('risk.thresholds.blocking', 70);
        $highRiskThreshold = config('risk.thresholds.high_risk', 50);
        $reviewThreshold = config('risk.thresholds.review', 30);

        $this->assertEquals('blocked', $this->service->getRiskCategory($blockingThreshold + 10));
        $this->assertEquals('high', $this->service->getRiskCategory($highRiskThreshold + 5));
        $this->assertEquals('review', $this->service->getRiskCategory($reviewThreshold + 5));
        $this->assertEquals('low', $this->service->getRiskCategory($reviewThreshold - 10));
    }

    // ==================== OPEN DISPUTE SCORING TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_score_includes_open_disputes()
    {
        // Create an open dispute for the user
        $this->createDispute($this->user, 'open', 'medium');

        $result = $this->service->calculateScore($this->user);

        $this->assertContains('pending_disputes', $result['factors']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function calculate_score_weights_critical_disputes_higher()
    {
        // User A: one normal open dispute
        $userA = User::factory()->create();
        $this->createDispute($userA, 'open', 'low');

        // User B: one critical open dispute
        $userB = User::factory()->create();
        $this->createDispute($userB, 'open', 'critical');

        $scoreA = $this->service->calculateScore($userA);
        $scoreB = $this->service->calculateScore($userB);

        $this->assertGreaterThan($scoreA['score'], $scoreB['score']);
    }

    // ==================== RISK SUMMARY TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_risk_summary_returns_complete_data()
    {
        $summary = $this->service->getRiskSummary($this->user);

        $this->assertArrayHasKey('user_id', $summary);
        $this->assertArrayHasKey('current_score', $summary);
        $this->assertArrayHasKey('risk_category', $summary);
        $this->assertArrayHasKey('is_blocked', $summary);
        $this->assertArrayHasKey('factors', $summary);
        $this->assertArrayHasKey('thresholds', $summary);
    }

    // ==================== CHARGEBACK HISTORY TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_chargeback_history_returns_only_confirmed_chargebacks()
    {
        // Create confirmed chargeback
        $this->createChargebackPayment($this->user);

        // Create pending chargeback (should not be included)
        Payment::factory()->create([
            'user_id' => $this->user->id,
            'status' => Payment::STATUS_CHARGEBACK_PENDING,
        ]);

        // Create normal paid payment (should not be included)
        Payment::factory()->create([
            'user_id' => $this->user->id,
            'status' => Payment::STATUS_PAID,
        ]);

        $history = $this->service->getChargebackHistory($this->user);

        $this->assertCount(1, $history);
    }

    // ==================== HELPER METHODS ====================

    protected function createChargebackPayment(User $user): Payment
    {
        return Payment::factory()->create([
            'user_id' => $user->id,
            'status' => Payment::STATUS_CHARGEBACK_CONFIRMED,
            'amount_paise' => 100000, // 1000 INR
            'chargeback_amount_paise' => 100000,
            'chargeback_reason' => 'Test chargeback',
            'chargeback_confirmed_at' => now(),
        ]);
    }

    protected function createDispute(User $user, string $status, string $severity): Dispute
    {
        // FIX: Create company fixture instead of assuming one exists
        $company = Company::factory()->create();

        return Dispute::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => $status,
            'severity' => $severity,
            'category' => 'other',
            'title' => 'Test Dispute',
            'description' => 'Test dispute description',
            'opened_at' => now(),
        ]);
    }
}
