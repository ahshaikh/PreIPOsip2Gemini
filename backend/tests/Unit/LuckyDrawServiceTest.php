<?php
// V-FINAL-1730-TEST-41 (Created)

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\LuckyDrawService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

class LuckyDrawServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $plan;
    protected $draw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LuckyDrawService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this.seed(\Database\Seeders\SettingsSeeder::class);

        // Config: 5 entries base, 1 for on-time, 5 for streak
        $this->plan = Plan::factory()->create();
        $this->plan->configs()->create([
            'config_key' => 'lucky_draw_entries',
            'value' => ['count' => 5]
        ]);
        
        $this->draw = $this->service->createMonthlyDraw(
            'Test Draw',
            now()->endOfMonth(),
            [['rank' => 1, 'count' => 1, 'amount' => 1000]]
        );
    }

    /** @test */
    public function test_create_monthly_draw_sets_correct_date()
    {
        $date = now()->endOfMonth();
        $this->assertDatabaseHas('lucky_draws', [
            'name' => 'Test Draw',
            'draw_date' => $date->toDateString()
        ]);
    }

    /** @test */
    public function test_allocate_entries_based_on_plan()
    {
        $user = User::factory()->create();
        $sub = Subscription::factory()->create(['user_id' => $user->id, 'plan_id' => $this->plan->id]);
        
        // Late payment (no bonus entries)
        $payment = Payment::factory()->create(['subscription_id' => $sub->id, 'is_on_time' => false]);
        
        $this->service->allocateEntries($payment);
        
        $this->assertDatabaseHas('lucky_draw_entries', [
            'user_id' => $user->id,
            'lucky_draw_id' => $this->draw->id,
            'base_entries' => 5, // From plan
            'bonus_entries' => 0
        ]);
    }

    /** @test */
    public function test_allocate_bonus_entries_for_streaks()
    {
        $user = User::factory()->create();
        $sub = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $this->plan->id,
            'consecutive_payments_count' => 6 // 6-month streak!
        ]);
        
        // On-time payment
        $payment = Payment::factory()->create(['subscription_id' => $sub->id, 'is_on_time' => true]);
        
        $this->service->allocateEntries($payment);
        
        $this->assertDatabaseHas('lucky_draw_entries', [
            'user_id' => $user->id,
            'lucky_draw_id' => $this->draw->id,
            'base_entries' => 5, // From plan
            'bonus_entries' => 6  // 1 (on-time) + 5 (streak)
        ]);
    }

    /** @test */
    public function test_select_winners_uses_weighted_random()
    {
        // User A: 100 entries
        $userA = User::factory()->create();
        LuckyDrawEntry::create(['user_id' => $userA->id, 'lucky_draw_id' => $this->draw->id, 'payment_id' => 1, 'base_entries' => 100]);
        
        // User B: 1 entry
        $userB = User::factory()->create();
        LuckyDrawEntry::create(['user_id' => $userB->id, 'lucky_draw_id' => $this->draw->id, 'payment_id' => 2, 'base_entries' => 1]);

        // Run 100 draws. User A should win almost all of them.
        $wins = ['A' => 0, 'B' => 0];
        for ($i = 0; $i < 100; $i++) {
            $winnerId = $this->service->selectWinners($this->draw)[0];
            if ($winnerId == $userA->id) $wins['A']++;
            if ($winnerId == $userB->id) $wins['B']++;
        }

        // Test that User A won significantly more (not 50/50)
        $this->assertTrue($wins['A'] > 90);
        $this->assertTrue($wins['B'] < 10);
    }

    /** @test */
    public function test_distribute_prizes_to_wallets()
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 0]);
        LuckyDrawEntry::create(['user_id' => $user->id, 'lucky_draw_id' => $this->draw->id, 'payment_id' => 1, 'base_entries' => 1]);

        $winners = [$user->id];
        
        $this->service->distributePrizes($this->draw, $winners);
        
        // 1. Wallet balance updated
        $this->assertEquals(1000, $user->wallet->fresh()->balance);
        
        // 2. Bonus transaction created
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $user->id,
            'type' => 'lucky_draw',
            'amount' => 1000
        ]);
        
        // 3. Draw status updated
        $this.assertEquals('completed', $this.draw->fresh()->status);
    }

    /** @test */
    public function test_send_winner_notifications()
    {
        Log::shouldReceive('info')->once()->with('Queueing winner notification for User #1');

        $this->service->sendWinnerNotifications([1]);
    }
}