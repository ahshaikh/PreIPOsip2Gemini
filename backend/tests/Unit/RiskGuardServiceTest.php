<?php

// V-DISPUTE-RISK-2026-TEST-002: Risk Guard Service Unit Tests

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Services\RiskGuardService;
use App\Exceptions\RiskBlockedException;
use App\Models\User;
class RiskGuardServiceTest extends UnitTestCase
{
    protected RiskGuardService $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->guard = new RiskGuardService();
    }

    // ==================== ASSERT USER CAN INVEST TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function assert_user_can_invest_passes_for_non_blocked_user()
    {
        $user = User::factory()->create([
            'is_blocked' => false,
            'risk_score' => 0,
        ]);

        // Should not throw
        $this->guard->assertUserCanInvest($user, 'test_investment');

        // If we get here, test passes
        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function assert_user_can_invest_throws_for_blocked_user()
    {
        $user = User::factory()->create([
            'is_blocked' => true,
            'risk_score' => 80,
            'blocked_reason' => 'Chargeback threshold exceeded',
        ]);

        $this->expectException(RiskBlockedException::class);

        $this->guard->assertUserCanInvest($user, 'test_investment');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function assert_user_can_invest_includes_operation_context_in_exception()
    {
        $user = User::factory()->create([
            'is_blocked' => true,
            'risk_score' => 75,
        ]);

        try {
            $this->guard->assertUserCanInvest($user, 'share_allocation', [
                'product_id' => 123,
                'amount' => 1000,
            ]);
            $this->fail('Expected RiskBlockedException was not thrown');
        } catch (RiskBlockedException $e) {
            $this->assertEquals('share_allocation', $e->getAttemptedOperation());
            $this->assertEquals(123, $e->getOperationContext()['product_id']);
            $this->assertEquals(1000, $e->getOperationContext()['amount']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function assert_user_can_invest_refreshes_user_state()
    {
        $user = User::factory()->create([
            'is_blocked' => false,
        ]);

        // Block the user in database directly
        User::where('id', $user->id)->update(['is_blocked' => true]);

        // Guard should detect the new state
        $this->expectException(RiskBlockedException::class);

        $this->guard->assertUserCanInvest($user, 'test');
    }

    // ==================== IS USER BLOCKED TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function is_user_blocked_returns_true_for_blocked_user()
    {
        $user = User::factory()->create(['is_blocked' => true]);

        $this->assertTrue($this->guard->isUserBlocked($user));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function is_user_blocked_returns_false_for_non_blocked_user()
    {
        $user = User::factory()->create(['is_blocked' => false]);

        $this->assertFalse($this->guard->isUserBlocked($user));
    }

    // ==================== CAN USER INVEST TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function can_user_invest_returns_inverse_of_blocked_status()
    {
        $blockedUser = User::factory()->create(['is_blocked' => true]);
        $activeUser = User::factory()->create(['is_blocked' => false]);

        $this->assertFalse($this->guard->canUserInvest($blockedUser));
        $this->assertTrue($this->guard->canUserInvest($activeUser));
    }

    // ==================== GET BLOCKING DETAILS TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_blocking_details_returns_null_for_non_blocked_user()
    {
        $user = User::factory()->create(['is_blocked' => false]);

        $this->assertNull($this->guard->getBlockingDetails($user));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_blocking_details_returns_complete_info_for_blocked_user()
    {
        $user = User::factory()->create([
            'is_blocked' => true,
            'risk_score' => 85,
            'blocked_reason' => 'Multiple chargebacks detected',
            'last_risk_update_at' => now(),
        ]);

        $details = $this->guard->getBlockingDetails($user);

        $this->assertNotNull($details);
        $this->assertTrue($details['is_blocked']);
        $this->assertEquals(85, $details['risk_score']);
        $this->assertEquals('Multiple chargebacks detected', $details['blocked_reason']);
        $this->assertNotNull($details['last_risk_update_at']);
    }

    // ==================== EXCEPTION TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function risk_blocked_exception_has_correct_http_code()
    {
        $user = User::factory()->create([
            'is_blocked' => true,
            'risk_score' => 70,
        ]);

        try {
            $this->guard->assertUserCanInvest($user, 'test');
            $this->fail('Expected RiskBlockedException was not thrown');
        } catch (RiskBlockedException $e) {
            $this->assertEquals(403, $e->getCode());
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function risk_blocked_exception_provides_user_friendly_message()
    {
        $user = User::factory()->create([
            'is_blocked' => true,
        ]);

        try {
            $this->guard->assertUserCanInvest($user, 'test');
            $this->fail('Expected RiskBlockedException was not thrown');
        } catch (RiskBlockedException $e) {
            $userMessage = $e->getUserMessage();
            $this->assertStringContainsString('restricted', $userMessage);
            $this->assertStringContainsString('contact support', $userMessage);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function risk_blocked_exception_report_context_includes_required_fields()
    {
        $user = User::factory()->create([
            'is_blocked' => true,
            'risk_score' => 90,
            'blocked_reason' => 'Test reason',
        ]);

        try {
            $this->guard->assertUserCanInvest($user, 'investment', ['amount' => 500]);
            $this->fail('Expected RiskBlockedException was not thrown');
        } catch (RiskBlockedException $e) {
            $context = $e->reportContext();

            $this->assertEquals('RiskBlockedException', $context['exception_type']);
            $this->assertEquals('HIGH', $context['alert_level']);
            $this->assertEquals($user->id, $context['user_id']);
            $this->assertEquals(90, $context['risk_score']);
            $this->assertEquals('investment', $context['attempted_operation']);
            $this->assertEquals('No ledger mutation occurred', $context['financial_impact']);
        }
    }
}
