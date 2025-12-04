<?php
// V-FINAL-1730-TEST-34

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AllocationService;
use App\Models\User;
use App\Models\Product;
use App\Models\BulkPurchase;
use App\Models\UserInvestment;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $user;
    protected $product;
    protected $purchase;
    protected $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AllocationService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->user = User::factory()->create();
        
        $this->product = Product::factory()->create([
            'face_value_per_unit' => 100
        ]);
        
        $this->purchase = BulkPurchase::create([
            'product_id' => $this->product->id,
            'face_value_purchased' => 100000,
            'actual_cost_paid' => 80000,
            'extra_allocation_percentage' => 25, // Total Value = 125,000
        ]); // value_remaining is 125,000

        $sub = Subscription::factory()->create(['user_id' => $this->user->id]);
        $this->payment = Payment::factory()->create(['subscription_id' => $sub->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocate_to_user_creates_investment_record()
    {
        $this->service->allocateShares($this->payment, 1000);

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
        $this->service->allocateShares($this->payment, 1000);

        $this->assertDatabaseHas('bulk_purchases', [
            'id' => $this->purchase->id,
            'value_remaining' => 124000, // 125,000 - 1,000
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocate_to_user_handles_insufficient_inventory()
    {
        // Try to allocate 200,000 when only 125,000 is available
        $this->service->allocateShares($this->payment, 200000);

        // 1. No investment should be created
        $this->assertDatabaseMissing('user_investments', [
            'payment_id' => $this->payment->id
        ]);
        
        // 2. Inventory should NOT change
        $this->assertDatabaseHas('bulk_purchases', [
            'id' => $this->purchase->id,
            'value_remaining' => 125000
        ]);
        
        // 3. Payment should be FLAGGED for admin review
        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'is_flagged' => true,
            'flag_reason' => 'Allocation Failed: Insufficient Inventory.'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocation_logs_audit_trail_on_success()
    {
        $this->service->allocateShares($this->payment, 1000);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'allocation_success',
            'target_id' => $this->payment->id,
            'description' => 'Allocated 10 units of '.$this->product->name.' (₹1000)'
        ]);
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocation_logs_audit_trail_on_failure()
    {
        $this->service->allocateShares($this->payment, 200000); // Fail

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'allocation_failed',
            'description' => 'Failed to allocate ₹200000: Insufficient Inventory.'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocate_bonus_and_investment_deducts_from_same_pool()
    {
        // This test confirms our V1 logic: Investment (1000) and Bonus (100)
        // are combined (1100) and drawn from the *same* inventory pool.
        
        $totalValue = 1100;
        $this->service->allocateShares($this->payment, $totalValue);

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