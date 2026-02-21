<?php
// V-FINAL-1730-TEST-32
// STORY 4.2: Added provenance enforcement tests

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\BulkPurchase;
use App\Exceptions\BulkPurchaseProvenanceException;
class BulkPurchaseTest extends TestCase
{
    protected $product;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create();
        $this->admin = User::factory()->create();
    }

    /**
     * Helper to create a bulk purchase with valid provenance.
     * STORY 4.2: Updated to include mandatory provenance fields.
     */
    private function createPurchase($overrides = [])
    {
        $defaults = [
            'product_id' => $this->product->id,
            'admin_id' => $this->admin->id,
            'face_value_purchased' => 100000,
            'actual_cost_paid' => 80000,
            'extra_allocation_percentage' => 25,
            'purchase_date' => now(),
            // STORY 4.2: Provenance fields (required for compliance)
            'source_type' => 'manual_entry',
            'manual_entry_reason' => 'Test purchase for unit testing',
            'source_documentation' => 'test-document-ref-001',
        ];

        // This will trigger the 'creating' boot method
        return BulkPurchase::create(array_merge($defaults, $overrides));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bulk_purchase_belongs_to_product()
    {
        $purchase = $this->createPurchase();
        $this->assertInstanceOf(Product::class, $purchase->product);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bulk_purchase_calculates_discount_percentage()
    {
        // 100k (face) - 80k (cost) = 20k discount. 20k / 100k = 20%
        $purchase = $this->createPurchase();
        $this->assertEquals(20.00, $purchase->discount_percentage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bulk_purchase_calculates_extra_allocation_percentage()
    {
        $purchase = $this->createPurchase(['extra_allocation_percentage' => 30]);
        $this->assertEquals(30.00, $purchase->extra_allocation_percentage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bulk_purchase_calculates_total_value_received()
    {
        // 100k (face) * (1 + 0.25) = 125,000
        $purchase = $this->createPurchase();
        $this->assertEquals(125000, $purchase->total_value_received);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bulk_purchase_calculates_gross_margin()
    {
        // 125k (total) - 80k (cost) = 45,000 margin
        $purchase = $this->createPurchase();
        $this->assertEquals(45000, $purchase->gross_margin);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bulk_purchase_calculates_gross_margin_percentage()
    {
        // 45k (margin) / 80k (cost) * 100 = 56.25%
        $purchase = $this->createPurchase();
        $this->assertEquals(56.25, $purchase->gross_margin_percentage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bulk_purchase_tracks_allocated_amount()
    {
        $purchase = $this->createPurchase(); // Total: 125k, Remaining: 125k
        
        // Simulate AllocationService decrementing
        $purchase->decrement('value_remaining', 25000);
        
        // Allocated = Total - Remaining = 125k - 100k = 25k
        $this->assertEquals(25000, $purchase->fresh()->allocated_amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bulk_purchase_calculates_available_amount()
    {
        $purchase = $this->createPurchase(); // Total: 125k, Remaining: 125k
        
        $purchase->decrement('value_remaining', 25000);
        
        // Available = value_remaining
        $this->assertEquals(100000, $purchase->fresh()->available_amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bulk_purchase_validates_cost_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Actual cost cannot be negative");
        
        $this->createPurchase(['actual_cost_paid' => -100]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bulk_purchase_validates_face_value_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Face value must be positive");

        $this->createPurchase(['face_value_purchased' => 0]);
    }

    // =========================================================================
    // STORY 4.2: Provenance Enforcement Tests
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_bulk_purchase_requires_source_type()
    {
        $this->expectException(BulkPurchaseProvenanceException::class);
        $this->expectExceptionMessage("'source_type' is required");

        BulkPurchase::create([
            'product_id' => $this->product->id,
            'admin_id' => $this->admin->id,
            'face_value_purchased' => 100000,
            'actual_cost_paid' => 80000,
            'extra_allocation_percentage' => 25,
            'purchase_date' => now(),
            // Missing source_type
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_manual_entry_requires_reason()
    {
        $this->expectException(BulkPurchaseProvenanceException::class);
        $this->expectExceptionMessage("'manual_entry_reason' is required");

        BulkPurchase::create([
            'product_id' => $this->product->id,
            'admin_id' => $this->admin->id,
            'face_value_purchased' => 100000,
            'actual_cost_paid' => 80000,
            'extra_allocation_percentage' => 25,
            'purchase_date' => now(),
            'source_type' => 'manual_entry',
            // Missing manual_entry_reason
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_manual_entry_requires_documentation()
    {
        $this->expectException(BulkPurchaseProvenanceException::class);
        $this->expectExceptionMessage("'source_documentation' is required");

        BulkPurchase::create([
            'product_id' => $this->product->id,
            'admin_id' => $this->admin->id,
            'face_value_purchased' => 100000,
            'actual_cost_paid' => 80000,
            'extra_allocation_percentage' => 25,
            'purchase_date' => now(),
            'source_type' => 'manual_entry',
            'manual_entry_reason' => 'Reason provided',
            // Missing source_documentation
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_company_listing_source_requires_listing_id()
    {
        $this->expectException(BulkPurchaseProvenanceException::class);
        $this->expectExceptionMessage("'company_share_listing_id' is required");

        BulkPurchase::create([
            'product_id' => $this->product->id,
            'admin_id' => $this->admin->id,
            'face_value_purchased' => 100000,
            'actual_cost_paid' => 80000,
            'extra_allocation_percentage' => 25,
            'purchase_date' => now(),
            'source_type' => 'company_listing',
            // Missing company_share_listing_id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_manual_entry_with_valid_provenance_succeeds()
    {
        $purchase = BulkPurchase::create([
            'product_id' => $this->product->id,
            'admin_id' => $this->admin->id,
            'face_value_purchased' => 100000,
            'actual_cost_paid' => 80000,
            'extra_allocation_percentage' => 25,
            'purchase_date' => now(),
            'source_type' => 'manual_entry',
            'manual_entry_reason' => 'Bulk inventory acquisition from secondary market',
            'source_documentation' => 'invoice-2024-001.pdf',
        ]);

        $this->assertInstanceOf(BulkPurchase::class, $purchase);
        $this->assertEquals('manual_entry', $purchase->source_type);
        $this->assertNotNull($purchase->manual_entry_reason);
        $this->assertNotNull($purchase->source_documentation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_company_listing_with_valid_listing_id_succeeds()
    {
        // Create a mock company share listing (using a valid ID)
        $listingId = 1;

        $purchase = BulkPurchase::create([
            'product_id' => $this->product->id,
            'admin_id' => $this->admin->id,
            'face_value_purchased' => 100000,
            'actual_cost_paid' => 80000,
            'extra_allocation_percentage' => 25,
            'purchase_date' => now(),
            'source_type' => 'company_listing',
            'company_share_listing_id' => $listingId,
        ]);

        $this->assertInstanceOf(BulkPurchase::class, $purchase);
        $this->assertEquals('company_listing', $purchase->source_type);
        $this->assertEquals($listingId, $purchase->company_share_listing_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_provenance_exception_has_context()
    {
        try {
            BulkPurchase::create([
                'product_id' => $this->product->id,
                'admin_id' => $this->admin->id,
                'face_value_purchased' => 100000,
                'actual_cost_paid' => 80000,
                'extra_allocation_percentage' => 25,
                'purchase_date' => now(),
                'source_type' => 'manual_entry',
                // Missing required provenance
            ]);

            $this->fail('Expected BulkPurchaseProvenanceException was not thrown');
        } catch (BulkPurchaseProvenanceException $e) {
            $context = $e->context();

            $this->assertArrayHasKey('source_type', $context);
            $this->assertArrayHasKey('missing_field', $context);
            $this->assertEquals('manual_entry', $context['source_type']);
            $this->assertEquals('manual_entry_reason', $context['missing_field']);
        }
    }
}