<?php
// V-FINAL-1730-TEST-18

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Models\Plan;
use App\Models\PlanConfig;
use App\Models\PlanFeature;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PlanTest extends UnitTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_plan_has_configs_relationship()
    {
        $plan = Plan::factory()->create();
        PlanConfig::create([
            'plan_id' => $plan->id,
            'config_key' => 'test_key',
            'value' => ['data' => 123]
        ]);

        $this->assertTrue($plan->configs()->exists());
        $this->assertEquals('test_key', $plan->configs->first()->config_key);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_plan_has_features_relationship()
    {
        $plan = Plan::factory()->create();
        PlanFeature::create([
            'plan_id' => $plan->id,
            'feature_text' => 'Zero Fees'
        ]);

        $this->assertTrue($plan->features()->exists());
        $this->assertEquals('Zero Fees', $plan->features->first()->feature_text);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_plan_has_subscriptions_relationship()
    {
        $plan = Plan::factory()->create();
        $user = User::factory()->create();
        Subscription::factory()->create([
            'plan_id' => $plan->id,
            'user_id' => $user->id
        ]);

        $this->assertTrue($plan->subscriptions()->exists());
        $this->assertEquals($user->id, $plan->subscriptions->first()->user_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_plan_scope_active_returns_only_active()
    {
        $initialActive = Plan::active()->count();

        Plan::factory()->create(['is_active' => true]);
        Plan::factory()->create(['is_active' => false]);

        $this->assertEquals($initialActive + 1, Plan::active()->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_plan_calculates_total_investment()
    {
        $plan = Plan::factory()->create([
            'monthly_amount' => 5000,
            'duration_months' => 36
        ]);

        // 5000 * 36 = 180,000
        $this->assertEquals(180000, $plan->total_investment);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_plan_gets_config_value_by_key()
    {
        $plan = Plan::factory()->create();
        $plan->configs()->create([
            'config_key' => 'bonus_rate',
            'value' => 10
        ]);

        $this->assertEquals(10, $plan->getConfig('bonus_rate'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_plan_returns_default_if_config_missing()
    {
        $plan = Plan::factory()->create();
        
        $value = $plan->getConfig('non_existent_key', 'default_value');
        
        $this->assertEquals('default_value', $value);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_plan_slug_is_unique()
    {
        Plan::factory()->create(['slug' => 'plan-a']);

        $this->expectException(QueryException::class);

        Plan::factory()->create(['slug' => 'plan-a']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_plan_validates_monthly_amount_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Monthly amount cannot be negative");

        Plan::factory()->create(['monthly_amount' => -100]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_plan_validates_duration_positive()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Duration must be at least 1 month");

        Plan::factory()->create(['duration_months' => 0]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_plan_soft_deletes_correctly()
    {
        $plan = Plan::factory()->create();
        $planId = $plan->id;

        $plan->delete();

        $this->assertNull(Plan::find($planId));
        $this->assertNotNull(Plan::withTrashed()->find($planId));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_plan_can_be_archived()
    {
        $plan = Plan::factory()->create(['is_active' => true]);
        
        $plan->archive();
        
        $this->assertFalse($plan->fresh()->is_active);
    }
}
