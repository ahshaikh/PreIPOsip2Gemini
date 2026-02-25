<?php
/**
 * STORY 3.1: Disclosure Tier Enum Tests
 *
 * Tests the DisclosureTier enum for:
 * - Correct tier ordering (rank)
 * - Visibility rules
 * - Promotion validation
 */

namespace Tests\Unit\Enums;

use App\Enums\DisclosureTier;
use PHPUnit\Framework\TestCase;
use Tests\UnitTestCase;

class DisclosureTierTest extends UnitTestCase
{
    /**
     * Test tier ranking is correct and monotonic.
     */
    public function test_tier_ranking_is_monotonic(): void
    {
        $this->assertEquals(0, DisclosureTier::TIER_0_PENDING->rank());
        $this->assertEquals(1, DisclosureTier::TIER_1_UPCOMING->rank());
        $this->assertEquals(2, DisclosureTier::TIER_2_LIVE->rank());
        $this->assertEquals(3, DisclosureTier::TIER_3_FEATURED->rank());

        // Verify monotonic progression
        $tiers = DisclosureTier::cases();
        for ($i = 1; $i < count($tiers); $i++) {
            $this->assertGreaterThan(
                $tiers[$i - 1]->rank(),
                $tiers[$i]->rank(),
                "Tier ranking must be monotonically increasing"
            );
        }
    }

    /**
     * Test public visibility rules.
     * INVARIANT: Only tier_2_live and tier_3_featured are publicly visible.
     */
    public function test_public_visibility_invariant(): void
    {
        // Non-visible tiers
        $this->assertFalse(DisclosureTier::TIER_0_PENDING->isPubliclyVisible());
        $this->assertFalse(DisclosureTier::TIER_1_UPCOMING->isPubliclyVisible());

        // Visible tiers
        $this->assertTrue(DisclosureTier::TIER_2_LIVE->isPubliclyVisible());
        $this->assertTrue(DisclosureTier::TIER_3_FEATURED->isPubliclyVisible());
    }

    /**
     * Test investability rules match visibility rules.
     */
    public function test_investability_matches_visibility(): void
    {
        foreach (DisclosureTier::cases() as $tier) {
            $this->assertEquals(
                $tier->isPubliclyVisible(),
                $tier->isInvestable(),
                "Tier {$tier->value}: investability should match visibility"
            );
        }
    }

    /**
     * Test next tier progression.
     */
    public function test_next_tier_progression(): void
    {
        $this->assertEquals(DisclosureTier::TIER_1_UPCOMING, DisclosureTier::TIER_0_PENDING->nextTier());
        $this->assertEquals(DisclosureTier::TIER_2_LIVE, DisclosureTier::TIER_1_UPCOMING->nextTier());
        $this->assertEquals(DisclosureTier::TIER_3_FEATURED, DisclosureTier::TIER_2_LIVE->nextTier());
        $this->assertNull(DisclosureTier::TIER_3_FEATURED->nextTier());
    }

    /**
     * Test canPromoteTo allows only one tier advancement.
     */
    public function test_can_promote_to_allows_only_one_tier_advancement(): void
    {
        // Valid promotions (exactly one tier higher)
        $this->assertTrue(DisclosureTier::TIER_0_PENDING->canPromoteTo(DisclosureTier::TIER_1_UPCOMING));
        $this->assertTrue(DisclosureTier::TIER_1_UPCOMING->canPromoteTo(DisclosureTier::TIER_2_LIVE));
        $this->assertTrue(DisclosureTier::TIER_2_LIVE->canPromoteTo(DisclosureTier::TIER_3_FEATURED));

        // Invalid: Same tier
        $this->assertFalse(DisclosureTier::TIER_0_PENDING->canPromoteTo(DisclosureTier::TIER_0_PENDING));
        $this->assertFalse(DisclosureTier::TIER_2_LIVE->canPromoteTo(DisclosureTier::TIER_2_LIVE));

        // Invalid: Tier skip
        $this->assertFalse(DisclosureTier::TIER_0_PENDING->canPromoteTo(DisclosureTier::TIER_2_LIVE));
        $this->assertFalse(DisclosureTier::TIER_0_PENDING->canPromoteTo(DisclosureTier::TIER_3_FEATURED));
        $this->assertFalse(DisclosureTier::TIER_1_UPCOMING->canPromoteTo(DisclosureTier::TIER_3_FEATURED));

        // Invalid: Downgrade
        $this->assertFalse(DisclosureTier::TIER_1_UPCOMING->canPromoteTo(DisclosureTier::TIER_0_PENDING));
        $this->assertFalse(DisclosureTier::TIER_2_LIVE->canPromoteTo(DisclosureTier::TIER_1_UPCOMING));
        $this->assertFalse(DisclosureTier::TIER_3_FEATURED->canPromoteTo(DisclosureTier::TIER_2_LIVE));
    }

    /**
     * Test maximum tier cannot be promoted.
     */
    public function test_maximum_tier_cannot_be_promoted(): void
    {
        $maxTier = DisclosureTier::TIER_3_FEATURED;

        $this->assertNull($maxTier->nextTier());

        foreach (DisclosureTier::cases() as $tier) {
            $this->assertFalse(
                $maxTier->canPromoteTo($tier),
                "Maximum tier should not be able to promote to any tier"
            );
        }
    }

    /**
     * Test publiclyVisibleTiers returns correct tiers.
     */
    public function test_publicly_visible_tiers_returns_correct_tiers(): void
    {
        $visibleTiers = DisclosureTier::publiclyVisibleTiers();

        $this->assertCount(2, $visibleTiers);
        $this->assertContains(DisclosureTier::TIER_2_LIVE, $visibleTiers);
        $this->assertContains(DisclosureTier::TIER_3_FEATURED, $visibleTiers);
        $this->assertNotContains(DisclosureTier::TIER_0_PENDING, $visibleTiers);
        $this->assertNotContains(DisclosureTier::TIER_1_UPCOMING, $visibleTiers);
    }

    /**
     * Test minimumPublicTier is tier_2_live.
     */
    public function test_minimum_public_tier_is_tier_2_live(): void
    {
        $this->assertEquals(DisclosureTier::TIER_2_LIVE, DisclosureTier::minimumPublicTier());
    }

    /**
     * Test tier values are correct strings.
     */
    public function test_tier_values_are_correct_strings(): void
    {
        $this->assertEquals('tier_0_pending', DisclosureTier::TIER_0_PENDING->value);
        $this->assertEquals('tier_1_upcoming', DisclosureTier::TIER_1_UPCOMING->value);
        $this->assertEquals('tier_2_live', DisclosureTier::TIER_2_LIVE->value);
        $this->assertEquals('tier_3_featured', DisclosureTier::TIER_3_FEATURED->value);
    }

    /**
     * Test tier labels are human readable.
     */
    public function test_tier_labels_are_human_readable(): void
    {
        $this->assertEquals('Pending', DisclosureTier::TIER_0_PENDING->label());
        $this->assertEquals('Upcoming', DisclosureTier::TIER_1_UPCOMING->label());
        $this->assertEquals('Live', DisclosureTier::TIER_2_LIVE->label());
        $this->assertEquals('Featured', DisclosureTier::TIER_3_FEATURED->label());
    }

    /**
     * Test values() returns all tier strings.
     */
    public function test_values_returns_all_tier_strings(): void
    {
        $values = DisclosureTier::values();

        $this->assertCount(4, $values);
        $this->assertContains('tier_0_pending', $values);
        $this->assertContains('tier_1_upcoming', $values);
        $this->assertContains('tier_2_live', $values);
        $this->assertContains('tier_3_featured', $values);
    }

    /**
     * Test options() returns structured array for forms.
     */
    public function test_options_returns_structured_array_for_forms(): void
    {
        $options = DisclosureTier::options();

        $this->assertCount(4, $options);

        foreach ($options as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertArrayHasKey('description', $option);
            $this->assertArrayHasKey('publicly_visible', $option);
            $this->assertArrayHasKey('rank', $option);
        }
    }
}
