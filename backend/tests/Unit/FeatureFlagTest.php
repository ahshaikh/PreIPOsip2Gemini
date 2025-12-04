<?php
// V-FINAL-1730-TEST-58 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;

class FeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    protected $user1;
    protected $user2;

    protected function setUp(): void
    {
        parent::setUp();
        // Create users with known IDs for predictable hashing
        $this->user1 = User::factory()->create(['id' => 1]);
        $this->user2 = User::factory()->create(['id' => 2]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_feature_flag_checks_if_enabled()
    {
        // 1. Simple ON flag
        $flagOn = FeatureFlag::factory()->create([
            'key' => 'test.on',
            'is_active' => true,
            'percentage' => null // 100% on
        ]);

        // 2. Simple OFF flag
        $flagOff = FeatureFlag::factory()->create([
            'key' => 'test.off',
            'is_active' => false,
            'percentage' => null
        ]);

        $this->assertTrue($flagOn->isEnabled($this->user1));
        $this->assertFalse($flagOff->isEnabled($this->user1));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_feature_flag_validates_identifier_unique()
    {
        FeatureFlag::factory()->create(['key' => 'unique.flag']);

        $this->expectException(QueryException::class);
        
        // Try to create a duplicate
        FeatureFlag::factory()->create(['key' => 'unique.flag']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_feature_flag_tracks_enabled_for_percentage()
    {
        // 1. Create a 50% rollout flag
        $flag = FeatureFlag::factory()->create([
            'key' => 'beta.feature',
            'is_active' => true,
            'percentage' => 50 // 50% on
        ]);

        // 2. Test 100 users to check the distribution
        // This is a statistical test
        $users = User::factory()->count(100)->create();
        $enabledCount = 0;

        foreach ($users as $user) {
            if ($flag->isEnabled($user)) {
                $enabledCount++;
            }
        }

        // Assert that the result is "around" 50
        // It's a hash, so it won't be exactly 50, but it shouldn't be 0 or 100.
        $this->assertTrue($enabledCount > 30 && $enabledCount < 70);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_percentage_flag_is_off_for_guests()
    {
        // A 99% rollout flag
        $flag = FeatureFlag::factory()->create([
            'key' => 'beta.feature',
            'is_active' => true,
            'percentage' => 99
        ]);

        // Null user (guest) should always be false
        $this->assertFalse($flag->isEnabled(null));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_percentage_flag_is_off_if_globally_disabled()
    {
        // A 100% rollout flag that is globally disabled
        $flag = FeatureFlag::factory()->create([
            'key' => 'beta.feature',
            'is_active' => false, // OFF
            'percentage' => 100
        ]);

        $this->assertFalse($flag->isEnabled($this->user1));
    }
}