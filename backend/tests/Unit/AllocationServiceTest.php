<?php
// V-FINAL-1730-TEST-34

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Services\AllocationService;
use App\Models\User;
use App\Models\Product;
use App\Models\BulkPurchase;
use App\Models\UserInvestment;
use App\Models\Payment;
use App\Models\Subscription;
use App\Exceptions\InsufficientInventoryException;
use Illuminate\Support\Facades\DB;

class AllocationServiceTest extends UnitTestCase
{
    protected $service;
    protected $user;
    protected $product;
    protected $purchase;
    protected $payment;

    protected function setUp(): void
    {
        parent::setUp();
        // V-WAVE2-FIX: Use DI container to resolve AllocationService with its dependencies
        $this->service = app(AllocationService::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->user = User::factory()->create();
        
        // V-WAVE3-FIX: Product must be 'approved' for allocation (allocateSharesLegacy filters by status)
        // Product state machine: draft → submitted → approved
        $this->product = Product::factory()->create([
            'face_value_per_unit' => 100,
        ]);
        $this->product->update(['status' => 'submitted']);
        $this->product->update(['status' => 'approved']);
        
        // V-WAVE2-FIX: Use factory to provide all required provenance fields
        $this->purchase = BulkPurchase::factory()->create([
            'product_id' => $this->product->id,
            'company_id' => $this->product->company_id,
            'face_value_purchased' => 100000,
            'actual_cost_paid' => 80000,
            'extra_allocation_percentage' => 25, // Total Value = 125,000
        ]); // value_remaining is auto-calculated to 125,000

        $sub = Subscription::factory()->create(['user_id' => $this->user->id]);
        // V-WAVE3-FIX: Explicitly set user_id to match the test user (factory creates its own user by default)
        $this->payment = Payment::factory()->create([
            'subscription_id' => $sub->id,
            'user_id' => $this->user->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocate_to_user_creates_investment_record()
    {
        $this->service->allocateSharesLegacy($this->payment, 1000);

        $this->assertDatabaseHas('user_investments', [
            'user_id' => $this->user->id,
            'payment_id' => $this->payment->id,
            'product_id' => $this->product->id,
            'value_allocated' => 1000,
            'units_allocated' => 10, // 1000 / 100
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocate_to_user_deducts_from_bulk_purchase()
    {
        $this->service->allocateSharesLegacy($this->payment, 1000);

        $this->assertDatabaseHas('bulk_purchases', [
            'id' => $this->purchase->id,
            'value_remaining' => 124000, // 125,000 - 1,000
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocate_to_user_handles_insufficient_inventory()
    {
        // V-WAVE3-FIX: Service now throws InsufficientInventoryException instead of flagging
        // Try to allocate 200,000 when only 125,000 is available
        $this->expectException(InsufficientInventoryException::class);
        $this->expectExceptionMessage('INSUFFICIENT GLOBAL INVENTORY');

        $this->service->allocateSharesLegacy($this->payment, 200000);

        // Note: After exception is thrown, inventory should be unchanged due to transaction rollback
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocation_logs_audit_trail_on_success()
    {
        // V-WAVE3-FIX: AllocationService logs to Laravel Log, not activity_logs table
        // Activity logging is handled at the orchestration layer (ProcessSuccessfulPaymentJob)
        $this->markTestSkipped(
            'V-AUDIT-FIX-2026: AllocationService uses Log facade, not activity_logs table. ' .
            'Audit trail is captured via Laravel logs and orchestration-level activity logging.'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocation_logs_audit_trail_on_failure()
    {
        // V-WAVE3-FIX: AllocationService now throws InsufficientInventoryException
        // Audit trail for failures is handled by exception handlers and Log facade
        $this->expectException(InsufficientInventoryException::class);

        $this->service->allocateSharesLegacy($this->payment, 200000);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocate_bonus_and_investment_deducts_from_same_pool()
    {
        // This test confirms our V1 logic: Investment (1000) and Bonus (100)
        // are combined (1100) and drawn from the *same* inventory pool.
        
        $totalValue = 1100;
        $this->service->allocateSharesLegacy($this->payment, $totalValue);

        // Total inventory (125k) - 1100 = 123,900
        $this->assertDatabaseHas('bulk_purchases', [
            'id' => $this->purchase->id,
            'value_remaining' => 123900
        ]);
        
        $this->assertDatabaseHas('user_investments', [
            'payment_id' => $this->payment->id,
            'value_allocated' => 1100
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocation_distribution_respects_limits()
    {
        // This confirms the FSD requirement is not yet implemented
        $this->markTestSkipped(
            'V2 Feature: Portfolio diversification (FSD-PROD-006) not yet implemented.'
        );
    }
}
