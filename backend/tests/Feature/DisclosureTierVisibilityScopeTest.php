<?php
/**
 * STORY 3.1: Disclosure Tier Visibility Scope Tests
 *
 * Tests the query scopes on Company and Product models that enforce
 * the visibility invariant: Only tier_2_live and tier_3_featured are public.
 */

namespace Tests\Feature\Models;

use App\Enums\DisclosureTier;
use App\Models\Company;
use App\Models\Product;
use App\Scopes\PublicVisibilityScope;
use App\Scopes\ProductPublicVisibilityScope;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;

class DisclosureTierVisibilityScopeTest extends FeatureTestCase
{
    protected Company $companyPending;
    protected Company $companyUpcoming;
    protected Company $companyLive;
    protected Company $companyFeatured;

    protected function setUp(): void
    {
        parent::setUp();

        // Create companies at each tier level - bypass global scope for setup
        $this->companyPending = Company::withoutGlobalScope(PublicVisibilityScope::class)->create([
            'name' => 'Pending Company',
            'slug' => 'pending-' . uniqid(),
            'disclosure_tier' => DisclosureTier::TIER_0_PENDING->value,
            'status' => 'active',
        ]);

        $this->companyUpcoming = Company::withoutGlobalScope(PublicVisibilityScope::class)->create([
            'name' => 'Upcoming Company',
            'slug' => 'upcoming-' . uniqid(),
            'disclosure_tier' => DisclosureTier::TIER_1_UPCOMING->value,
            'status' => 'active',
        ]);

        $this->companyLive = Company::withoutGlobalScope(PublicVisibilityScope::class)->create([
            'name' => 'Live Company',
            'slug' => 'live-' . uniqid(),
            'disclosure_tier' => DisclosureTier::TIER_2_LIVE->value,
            'status' => 'active',
        ]);

        $this->companyFeatured = Company::withoutGlobalScope(PublicVisibilityScope::class)->create([
            'name' => 'Featured Company',
            'slug' => 'featured-' . uniqid(),
            'disclosure_tier' => DisclosureTier::TIER_3_FEATURED->value,
            'status' => 'active',
        ]);
    }

    // ==================== COMPANY SCOPE TESTS ====================

    /**
     * Test publiclyVisible scope returns only tier_2 and tier_3.
     */
    public function test_company_publicly_visible_scope(): void
    {
        $visibleCompanies = Company::withoutGlobalScope(PublicVisibilityScope::class)
            ->publiclyVisible()
            ->get();

        $this->assertCount(2, $visibleCompanies);
        $this->assertTrue($visibleCompanies->contains('id', $this->companyLive->id));
        $this->assertTrue($visibleCompanies->contains('id', $this->companyFeatured->id));
        $this->assertFalse($visibleCompanies->contains('id', $this->companyPending->id));
        $this->assertFalse($visibleCompanies->contains('id', $this->companyUpcoming->id));
    }

    /**
     * Test byDisclosureTier scope filters correctly.
     */
    public function test_company_by_disclosure_tier_scope(): void
    {
        $pending = Company::withoutGlobalScope(PublicVisibilityScope::class)
            ->byDisclosureTier(DisclosureTier::TIER_0_PENDING)->get();
        $this->assertCount(1, $pending);
        $this->assertEquals($this->companyPending->id, $pending->first()->id);

        $live = Company::withoutGlobalScope(PublicVisibilityScope::class)
            ->byDisclosureTier(DisclosureTier::TIER_2_LIVE)->get();
        $this->assertCount(1, $live);
        $this->assertEquals($this->companyLive->id, $live->first()->id);
    }

    /**
     * Test atOrAboveTier scope filters correctly.
     */
    public function test_company_at_or_above_tier_scope(): void
    {
        // At or above tier_1: should include tier_1, tier_2, tier_3
        $aboveTier1 = Company::withoutGlobalScope(PublicVisibilityScope::class)
            ->atOrAboveTier(DisclosureTier::TIER_1_UPCOMING)->get();
        $this->assertCount(3, $aboveTier1);
        $this->assertFalse($aboveTier1->contains('id', $this->companyPending->id));

        // At or above tier_2: should include tier_2, tier_3
        $aboveTier2 = Company::withoutGlobalScope(PublicVisibilityScope::class)
            ->atOrAboveTier(DisclosureTier::TIER_2_LIVE)->get();
        $this->assertCount(2, $aboveTier2);
        $this->assertTrue($aboveTier2->contains('id', $this->companyLive->id));
        $this->assertTrue($aboveTier2->contains('id', $this->companyFeatured->id));

        // At or above tier_3: should include only tier_3
        $aboveTier3 = Company::withoutGlobalScope(PublicVisibilityScope::class)
            ->atOrAboveTier(DisclosureTier::TIER_3_FEATURED)->get();
        $this->assertCount(1, $aboveTier3);
        $this->assertEquals($this->companyFeatured->id, $aboveTier3->first()->id);
    }

    /**
     * Test investable scope matches publiclyVisible.
     */
    public function test_company_investable_scope(): void
    {
        $investable = Company::withoutGlobalScope(PublicVisibilityScope::class)->investable()->get();
        $publiclyVisible = Company::withoutGlobalScope(PublicVisibilityScope::class)->publiclyVisible()->get();

        $this->assertEquals($publiclyVisible->count(), $investable->count());
        $this->assertEquals(
            $publiclyVisible->pluck('id')->sort()->values()->toArray(),
            $investable->pluck('id')->sort()->values()->toArray()
        );
    }

    /**
     * Test pendingReview scope returns tier_0 and tier_1.
     */
    public function test_company_pending_review_scope(): void
    {
        $pending = Company::withoutGlobalScope(PublicVisibilityScope::class)->pendingReview()->get();

        $this->assertCount(2, $pending);
        $this->assertTrue($pending->contains('id', $this->companyPending->id));
        $this->assertTrue($pending->contains('id', $this->companyUpcoming->id));
        $this->assertFalse($pending->contains('id', $this->companyLive->id));
        $this->assertFalse($pending->contains('id', $this->companyFeatured->id));
    }

    /**
     * Test specific tier scopes.
     */
    public function test_company_specific_tier_scopes(): void
    {
        $upcoming = Company::withoutGlobalScope(PublicVisibilityScope::class)->upcoming()->get();
        $this->assertCount(1, $upcoming);
        $this->assertEquals($this->companyUpcoming->id, $upcoming->first()->id);

        $live = Company::withoutGlobalScope(PublicVisibilityScope::class)->live()->get();
        $this->assertCount(1, $live);
        $this->assertEquals($this->companyLive->id, $live->first()->id);

        $featured = Company::withoutGlobalScope(PublicVisibilityScope::class)->featuredTier()->get();
        $this->assertCount(1, $featured);
        $this->assertEquals($this->companyFeatured->id, $featured->first()->id);
    }

    // ==================== COMPANY HELPER TESTS ====================

    /**
     * Test isPubliclyVisibleByTier helper method.
     */
    public function test_company_is_publicly_visible_by_tier_helper(): void
    {
        $this->assertFalse($this->companyPending->isPubliclyVisibleByTier());
        $this->assertFalse($this->companyUpcoming->isPubliclyVisibleByTier());
        $this->assertTrue($this->companyLive->isPubliclyVisibleByTier());
        $this->assertTrue($this->companyFeatured->isPubliclyVisibleByTier());
    }

    /**
     * Test getDisclosureTierInfo helper method.
     */
    public function test_company_get_disclosure_tier_info_helper(): void
    {
        $info = $this->companyLive->getDisclosureTierInfo();

        $this->assertEquals(DisclosureTier::TIER_2_LIVE->value, $info['current_tier']);
        $this->assertEquals('Live', $info['current_tier_label']);
        $this->assertEquals(2, $info['current_tier_rank']);
        $this->assertTrue($info['is_publicly_visible']);
        $this->assertTrue($info['is_investable']);
        $this->assertEquals(DisclosureTier::TIER_3_FEATURED->value, $info['next_tier']);
        $this->assertTrue($info['can_be_promoted']);
    }

    // ==================== PRODUCT SCOPE TESTS ====================

    /**
     * Test product publiclyVisible scope respects company tier.
     */
    public function test_product_publicly_visible_scope(): void
    {
        // Create products for each company - bypass global scope for setup
        // Use factory to ensure all required fields (name, min_investment, etc.) are populated
        $productPending = Product::factory()->create([
            'company_id' => $this->companyPending->id,
            'status' => 'draft',
        ]);

        $productUpcoming = Product::factory()->create([
            'company_id' => $this->companyUpcoming->id,
            'status' => 'draft',
        ]);

        $productLive = Product::factory()->create([
            'company_id' => $this->companyLive->id,
            'status' => 'draft',
        ]);

        $productFeatured = Product::factory()->create([
            'company_id' => $this->companyFeatured->id,
            'status' => 'draft',
        ]);

        // Manually update status to bypass transition rules for test setup if needed, 
        // though scopes should work regardless of product status (they check company tier).
        // For visibility tests, 'approved' is the realistic state.
        foreach ([$productPending, $productUpcoming, $productLive, $productFeatured] as $p) {
            DB::table('products')->where('id', $p->id)->update(['status' => 'approved']);
        }

        $visibleProducts = Product::withoutGlobalScope(ProductPublicVisibilityScope::class)
            ->publiclyVisible()
            ->get();

        $this->assertCount(2, $visibleProducts);
        $this->assertTrue($visibleProducts->contains('id', $productLive->id));
        $this->assertTrue($visibleProducts->contains('id', $productFeatured->id));
        $this->assertFalse($visibleProducts->contains('id', $productPending->id));
        $this->assertFalse($visibleProducts->contains('id', $productUpcoming->id));
    }

    /**
     * Test product byCompanyDisclosureTier scope.
     */
    public function test_product_by_company_disclosure_tier_scope(): void
    {
        $p1 = Product::factory()->create([
            'company_id' => $this->companyLive->id,
            'status' => 'draft',
        ]);

        $p2 = Product::factory()->create([
            'company_id' => $this->companyLive->id,
            'status' => 'draft',
        ]);

        $p3 = Product::factory()->create([
            'company_id' => $this->companyPending->id,
            'status' => 'draft',
        ]);

        foreach ([$p1, $p2, $p3] as $p) {
            DB::table('products')->where('id', $p->id)->update(['status' => 'approved']);
        }

        $liveProducts = Product::withoutGlobalScope(ProductPublicVisibilityScope::class)
            ->byCompanyDisclosureTier(DisclosureTier::TIER_2_LIVE)->get();
        $this->assertCount(2, $liveProducts);

        $pendingProducts = Product::withoutGlobalScope(ProductPublicVisibilityScope::class)
            ->byCompanyDisclosureTier(DisclosureTier::TIER_0_PENDING)->get();
        $this->assertCount(1, $pendingProducts);
    }

    /**
     * Test product fromCompaniesAtOrAboveTier scope.
     */
    public function test_product_from_companies_at_or_above_tier_scope(): void
    {
        $p1 = Product::factory()->create([
            'company_id' => $this->companyPending->id,
            'status' => 'draft',
        ]);
        $p2 = Product::factory()->create([
            'company_id' => $this->companyUpcoming->id,
            'status' => 'draft',
        ]);
        $p3 = Product::factory()->create([
            'company_id' => $this->companyLive->id,
            'status' => 'draft',
        ]);
        $p4 = Product::factory()->create([
            'company_id' => $this->companyFeatured->id,
            'status' => 'draft',
        ]);

        foreach ([$p1, $p2, $p3, $p4] as $p) {
            DB::table('products')->where('id', $p->id)->update(['status' => 'approved']);
        }

        $products = Product::withoutGlobalScope(ProductPublicVisibilityScope::class)
            ->fromCompaniesAtOrAboveTier(DisclosureTier::TIER_2_LIVE)->get();
        $this->assertCount(2, $products);
    }

    /**
     * Test product isPubliclyVisibleByCompanyTier helper.
     */
    public function test_product_is_publicly_visible_by_company_tier_helper(): void
    {
        $productVisible = Product::factory()->create([
            'company_id' => $this->companyLive->id,
            'status' => 'draft',
        ]);

        $productNotVisible = Product::factory()->create([
            'company_id' => $this->companyPending->id,
            'status' => 'draft',
        ]);

        foreach ([$productVisible, $productNotVisible] as $p) {
            DB::table('products')->where('id', $p->id)->update(['status' => 'approved']);
        }

        $this->assertTrue($productVisible->isPubliclyVisibleByCompanyTier());
        $this->assertFalse($productNotVisible->isPubliclyVisibleByCompanyTier());
    }

    /**
     * Test product getVisibilityStatus helper.
     */
    public function test_product_get_visibility_status_helper(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->companyLive->id,
            'status' => 'draft',
        ]);

        DB::table('products')->where('id', $product->id)->update(['status' => 'approved']);
        $product->refresh();

        $status = $product->getVisibilityStatus();

        $this->assertEquals($this->companyLive->id, $status['company_id']);
        $this->assertEquals($this->companyLive->name, $status['company_name']);
        $this->assertEquals(DisclosureTier::TIER_2_LIVE->value, $status['company_disclosure_tier']);
        $this->assertTrue($status['is_publicly_visible']);
        $this->assertTrue($status['is_investable']);
    }

    // ==================== INVARIANT TESTS ====================

    /**
     * GOVERNANCE INVARIANT: No public visibility without tier_2_live or higher.
     * This test ensures the invariant holds across all query paths.
     */
    public function test_invariant_no_public_visibility_below_tier_2(): void
    {
        // Create many products across all tiers
        for ($i = 0; $i < 5; $i++) {
            $p1 = Product::factory()->create([
                'company_id' => $this->companyPending->id,
                'status' => 'draft',
            ]);
            $p2 = Product::factory()->create([
                'company_id' => $this->companyUpcoming->id,
                'status' => 'draft',
            ]);
            $p3 = Product::factory()->create([
                'company_id' => $this->companyLive->id,
                'status' => 'draft',
            ]);
            $p4 = Product::factory()->create([
                'company_id' => $this->companyFeatured->id,
                'status' => 'draft',
            ]);

            foreach ([$p1, $p2, $p3, $p4] as $p) {
                DB::table('products')->where('id', $p->id)->update(['status' => 'approved']);
            }
        }

        $visibleProducts = Product::withoutGlobalScope(ProductPublicVisibilityScope::class)
            ->publiclyVisible()
            ->get();

        // Verify EVERY visible product belongs to a company at tier_2 or higher
        foreach ($visibleProducts as $product) {
            $companyTier = $product->company->getDisclosureTierEnum();
            $this->assertTrue(
                $companyTier->isPubliclyVisible(),
                "Product ID {$product->id} is visible but company tier is {$companyTier->value}"
            );
        }

        // Verify NO product from tier_0 or tier_1 companies is visible
        $invisibleCompanyIds = [$this->companyPending->id, $this->companyUpcoming->id];
        foreach ($visibleProducts as $product) {
            $this->assertNotContains(
                $product->company_id,
                $invisibleCompanyIds,
                "Product from non-public company should not be visible"
            );
        }
    }

    /**
     * GOVERNANCE INVARIANT: Same results for Company and Product visibility.
     * All visible products must belong to visible companies.
     */
    public function test_invariant_product_visibility_matches_company_visibility(): void
    {
        // Create products
        for ($i = 0; $i < 3; $i++) {
            $p1 = Product::factory()->create([
                'company_id' => $this->companyPending->id,
                'status' => 'draft',
            ]);
            $p2 = Product::factory()->create([
                'company_id' => $this->companyUpcoming->id,
                'status' => 'draft',
            ]);
            $p3 = Product::factory()->create([
                'company_id' => $this->companyLive->id,
                'status' => 'draft',
            ]);
            $p4 = Product::factory()->create([
                'company_id' => $this->companyFeatured->id,
                'status' => 'draft',
            ]);

            foreach ([$p1, $p2, $p3, $p4] as $p) {
                DB::table('products')->where('id', $p->id)->update(['status' => 'approved']);
            }
        }

        $visibleCompanyIds = Company::withoutGlobalScope(PublicVisibilityScope::class)
            ->publiclyVisible()
            ->pluck('id')
            ->toArray();
        $visibleProducts = Product::withoutGlobalScope(ProductPublicVisibilityScope::class)
            ->publiclyVisible()
            ->get();

        foreach ($visibleProducts as $product) {
            $this->assertContains(
                $product->company_id,
                $visibleCompanyIds,
                "Visible product must belong to visible company"
            );
        }
    }
}
