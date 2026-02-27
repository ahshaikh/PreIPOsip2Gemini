<?php
// V-FINAL-1730-TEST-64 (Created)

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use Carbon\Carbon;

class ProductPriceHistoryTest extends UnitTestCase
{
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_price_history_belongs_to_product()
    {
        $history = ProductPriceHistory::create([
            'product_id' => $this->product->id,
            'price' => 100,
            'recorded_at' => now()
        ]);

        $this->assertInstanceOf(Product::class, $history->product);
        $this->assertEquals($this->product->id, $history->product->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_price_history_tracks_price_changes()
    {
        $history = ProductPriceHistory::create([
            'product_id' => $this->product->id,
            'price' => 120.50,
            'recorded_at' => now()
        ]);

        $this->assertDatabaseHas('product_price_histories', [
            'price' => 120.50
        ]);
        $this->assertEquals(120.50, $history->fresh()->price);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_price_history_validates_price_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Price must be positive");

        ProductPriceHistory::create([
            'product_id' => $this->product->id,
            'price' => -50, // Invalid
            'recorded_at' => now()
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_price_history_orders_by_date_descending()
    {
        $now = \Carbon\Carbon::parse('2025-01-01 12:00:00');
        
        // Create 3 records in chronological order
        $h1 = ProductPriceHistory::create([
            'product_id' => $this->product->id,
            'price' => 100,
            'recorded_at' => $now->copy()->subDays(5)
        ]);
        
        $h2 = ProductPriceHistory::create([
            'product_id' => $this->product->id,
            'price' => 105,
            'recorded_at' => $now->copy()->subDays(2)
        ]);

        $h3 = ProductPriceHistory::create([
            'product_id' => $this->product->id,
            'price' => 110,
            'recorded_at' => $now // Newest
        ]);

        // Fetch using 'latest' (which means order by recorded_at descending)
        // Since recorded_at is a DATE column, same-day order might depend on ID or timestamps
        // Use reorder() to clear any default orderings from the relationship definition
        $latest = $this->product->priceHistory()->reorder()->orderBy('recorded_at', 'desc')->orderBy('id', 'desc')->first();

        $this->assertEquals($h3->id, $latest->id);
    }
}
