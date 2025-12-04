<?php
// V-FINAL-1730-TEST-19

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Plan;
use App\Models\PlanConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;

class PlanConfigTest extends TestCase
{
    use RefreshDatabase;

    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plan = Plan::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_config_belongs_to_plan()
    {
        $config = PlanConfig::create([
            'plan_id' => $this->plan->id,
            'config_key' => 'test_key',
            'value' => ['foo' => 'bar']
        ]);

        $this->assertInstanceOf(Plan::class, $config->plan);
        $this->assertEquals($this->plan->id, $config->plan->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_config_key_is_unique_per_plan()
    {
        PlanConfig::create([
            'plan_id' => $this->plan->id,
            'config_key' => 'duplicate_key',
            'value' => ['a' => 1]
        ]);

        $this->expectException(QueryException::class);

        // Attempt to create same key for same plan
        PlanConfig::create([
            'plan_id' => $this->plan->id,
            'config_key' => 'duplicate_key',
            'value' => ['b' => 2]
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_config_value_is_json_cast()
    {
        $data = ['rate' => 10, 'enabled' => true, 'nested' => ['a' => 1]];
        
        $config = PlanConfig::create([
            'plan_id' => $this->plan->id,
            'config_key' => 'json_test',
            'value' => $data
        ]);

        // Fetch fresh from DB to verify casting
        $retrieved = $config->fresh();
        
        $this->assertIsArray($retrieved->value);
        $this->assertEquals(10, $retrieved->value['rate']);
        $this->assertTrue($retrieved->value['enabled']);
        $this->assertEquals(1, $retrieved->value['nested']['a']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_progressive_config_structure_validates()
    {
        // Progressive bonus requires 'rate' and 'start_month'
        $structure = ['rate' => 0.5, 'start_month' => 4];
        
        $config = PlanConfig::create([
            'plan_id' => $this->plan->id,
            'config_key' => 'progressive_config',
            'value' => $structure
        ]);

        $this->assertEquals(0.5, $config->value['rate']);
        $this->assertEquals(4, $config->value['start_month']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_milestone_config_structure_validates()
    {
        // Milestone config is an array of objects
        $structure = [
            ['month' => 12, 'amount' => 1000],
            ['month' => 24, 'amount' => 2000]
        ];
        
        $config = PlanConfig::create([
            'plan_id' => $this->plan->id,
            'config_key' => 'milestone_config',
            'value' => $structure
        ]);

        $this->assertCount(2, $config->value);
        $this->assertEquals(1000, $config->value[0]['amount']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_consistency_config_structure_validates()
    {
        // Consistency config has base amount and streak multipliers
        $structure = [
            'amount_per_payment' => 10,
            'streaks' => [
                ['months' => 6, 'multiplier' => 3]
            ]
        ];
        
        $config = PlanConfig::create([
            'plan_id' => $this->plan->id,
            'config_key' => 'consistency_config',
            'value' => $structure
        ]);

        $this->assertEquals(10, $config->value['amount_per_payment']);
        $this->assertEquals(3, $config->value['streaks'][0]['multiplier']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_referral_config_structure_validates()
    {
        // Referral tiers structure
        $structure = [
            ['count' => 0, 'multiplier' => 1.0],
            ['count' => 5, 'multiplier' => 2.0]
        ];
        
        $config = PlanConfig::create([
            'plan_id' => $this->plan->id,
            'config_key' => 'referral_tiers',
            'value' => $structure
        ]);

        $this->assertEquals(2.0, $config->value[1]['multiplier']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_config_can_be_updated()
    {
        $config = PlanConfig::create([
            'plan_id' => $this->plan->id,
            'config_key' => 'update_me',
            'value' => ['val' => 1]
        ]);

        $config->update(['value' => ['val' => 2]]);

        $this->assertEquals(2, $config->fresh()->value['val']);
    }
}