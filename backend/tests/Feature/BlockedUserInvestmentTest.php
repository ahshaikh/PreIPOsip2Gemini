<?php

// V-DISPUTE-RISK-2026-TEST-006: Blocked User Investment Prevention Feature Tests

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use App\Models\BulkPurchase;
use App\Models\Subscription;
use App\Models\Investment;
use App\Services\AllocationService;
use App\Services\RiskGuardService;
use App\Exceptions\RiskBlockedException;
class BlockedUserInvestmentTest extends TestCase
{
    protected AllocationService $allocationService;
    protected RiskGuardService $riskGuard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->allocationService = app(AllocationService::class);
        $this->riskGuard = app(RiskGuardService::class);
    }

    // ==================== ALLOCATION BLOCKING TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function blocked_user_cannot_receive_share_allocation()
    {
        $user = $this->createBlockedUser();
        $product = $this->createProductWithInventory();
        $data = $this->createInvestmentWithPayment($user);

        $this->expectException(RiskBlockedException::class);

        // V-WAVE2-DOCTRINE: Use canonical allocateShares() method with payment for FK requirement
        $this->allocationService->allocateShares($user, $product, 1000.00, $data['investment'], $data['payment']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function blocked_user_allocation_does_not_mutate_inventory()
    {
        $user = $this->createBlockedUser();
        $product = $this->createProductWithInventory(10000);
        $data = $this->createInvestmentWithPayment($user);

        $inventoryBefore = BulkPurchase::where('product_id', $product->id)->sum('value_remaining');

        try {
            // V-WAVE2-DOCTRINE: Use canonical allocateShares() method with payment for FK requirement
            $this->allocationService->allocateShares($user, $product, 1000.00, $data['investment'], $data['payment']);
        } catch (RiskBlockedException $e) {
            // Expected
        }

        $inventoryAfter = BulkPurchase::where('product_id', $product->id)->sum('value_remaining');

        $this->assertEquals($inventoryBefore, $inventoryAfter);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_blocked_user_can_receive_allocation()
    {
        $user = $this->createActiveUser();
        $product = $this->createProductWithInventory(10000);
        $data = $this->createInvestmentWithPayment($user);

        // V-WAVE2-DOCTRINE: Use canonical allocateShares() method with payment for FK requirement
        $this->allocationService->allocateShares($user, $product, 1000.00, $data['investment'], $data['payment']);

        // Verify allocation was created
        $this->assertDatabaseHas('user_investments', [
            'user_id' => $user->id,
            'payment_id' => $data['payment']->id,
        ]);
    }

    // ==================== LEGACY ALLOCATION BLOCKING TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function blocked_user_cannot_receive_legacy_allocation()
    {
        $user = $this->createBlockedUser();
        $payment = $this->createPaidPayment($user);

        // Create inventory
        $this->createProductWithInventory(10000);

        $this->expectException(RiskBlockedException::class);

        $this->allocationService->allocateSharesLegacy($payment, 1000.00);
    }

    // ==================== GUARD FRESHNESS TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function guard_detects_recently_blocked_user()
    {
        // Start as active user
        $user = $this->createActiveUser();
        $product = $this->createProductWithInventory(10000);
        $data = $this->createInvestmentWithPayment($user);

        // Block the user after guard was created
        $user->update([
            'is_blocked' => true,
            'blocked_reason' => 'Blocked mid-transaction',
        ]);

        // Guard should detect the blocking even with stale object
        $this->expectException(RiskBlockedException::class);

        // V-WAVE2-DOCTRINE: Use canonical allocateShares() method with payment for FK requirement
        $this->allocationService->allocateShares($user, $product, 1000.00, $data['investment'], $data['payment']);
    }

    // ==================== TRANSACTION ATOMICITY TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function no_partial_allocation_on_blocked_user()
    {
        $user = $this->createBlockedUser();
        $product = $this->createProductWithInventory(10000);
        $data = $this->createInvestmentWithPayment($user);

        $userInvestmentsBefore = $user->userInvestments()->count();

        try {
            // V-WAVE2-DOCTRINE: Use canonical allocateShares() method with payment for FK requirement
            $this->allocationService->allocateShares($user, $product, 1000.00, $data['investment'], $data['payment']);
        } catch (RiskBlockedException $e) {
            // Expected
        }

        $userInvestmentsAfter = $user->userInvestments()->count();

        // No new allocations should exist
        $this->assertEquals($userInvestmentsBefore, $userInvestmentsAfter);
    }

    // ==================== ADMIN API TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_view_blocked_users_list()
    {
        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        // Create blocked users
        $this->createBlockedUser();
        $this->createBlockedUser();

        $response = $this->actingAs($adminUser, 'sanctum')
            ->getJson('/api/v1/admin/disputes/blocked-users');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'username', 'email', 'risk_score', 'is_blocked', 'blocked_reason']
            ]
        ]);
    }

    // ==================== UNBLOCKING TESTS ====================

    #[\PHPUnit\Framework\Attributes\Test]
    public function unblocked_user_can_receive_allocation_again()
    {
        $user = $this->createBlockedUser();
        $product = $this->createProductWithInventory(10000);
        $data = $this->createInvestmentWithPayment($user);

        // Unblock the user
        $user->update([
            'is_blocked' => false,
            'blocked_reason' => null,
        ]);

        // V-WAVE2-DOCTRINE: Use canonical allocateShares() method with payment for FK requirement
        $this->allocationService->allocateShares($user, $product, 1000.00, $data['investment'], $data['payment']);

        // Verify allocation was created
        $this->assertDatabaseHas('user_investments', [
            'user_id' => $user->id,
            'payment_id' => $data['payment']->id,
        ]);
    }

    // ==================== HELPER METHODS ====================

    protected function createBlockedUser(): User
    {
        $user = User::factory()->create([
            'is_blocked' => true,
            'risk_score' => 85,
            'blocked_reason' => 'Multiple chargebacks detected',
            'last_risk_update_at' => now(),
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'balance_paise' => 500000,
            'locked_balance_paise' => 0,
        ]);

        return $user;
    }

    protected function createActiveUser(): User
    {
        $user = User::factory()->create([
            'is_blocked' => false,
            'risk_score' => 0,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'balance_paise' => 500000,
            'locked_balance_paise' => 0,
        ]);

        return $user;
    }

    protected function createProductWithInventory(float $value = 10000): Product
    {
        // V-WAVE2-FIX: Use factory's withInventory() state which properly handles
        // status transitions and inventory creation in correct order
        return Product::factory()->withInventory((int) $value)->create([
            'face_value_per_unit' => 100,
        ]);
    }

    /**
     * Create an Investment with associated Payment for allocateShares() FK requirements.
     * V-WAVE2-DOCTRINE: Returns array with investment and payment for proper allocation.
     */
    protected function createInvestmentWithPayment(User $user): array
    {
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'status' => Payment::STATUS_PAID,
            'amount_paise' => 100000,
            'paid_at' => now(),
        ]);

        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'status' => 'pending',
        ]);

        return ['investment' => $investment, 'payment' => $payment];
    }

    /**
     * @deprecated Use createInvestmentWithPayment() instead
     */
    protected function createInvestment(User $user): Investment
    {
        return $this->createInvestmentWithPayment($user)['investment'];
    }

    protected function createPaidPayment(User $user): Payment
    {
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        return Payment::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'status' => Payment::STATUS_PAID,
            'amount_paise' => 100000,
            'paid_at' => now(),
        ]);
    }
}
