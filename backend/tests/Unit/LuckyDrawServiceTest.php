<?php
// V-FINAL-1730-TEST-41 (Created)

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Services\LuckyDrawService;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

class LuckyDrawServiceTest extends UnitTestCase
{
    protected $service;
    protected $plan;
    protected $draw;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LuckyDrawService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

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

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_create_monthly_draw_sets_correct_date()
    {
        $date = now()->endOfMonth();
        $this->assertDatabaseHas('lucky_draws', [
            'name' => 'Test Draw',
            'draw_date' => $date->toDateString()
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocate_entries_based_on_plan()
    {
        $user = User::factory()->create();
        $sub = Subscription::factory()->create(['user_id' => $user->id, 'plan_id' => $this->plan->id]);
        
        // Late payment (no bonus entries)
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $sub->id,
            'is_on_time' => false
        ]);
        
        $this->service->allocateEntries($payment);
        
        $this->assertDatabaseHas('lucky_draw_entries', [
            'user_id' => $user->id,
            'lucky_draw_id' => $this->draw->id,
            'base_entries' => 5, // From plan
            'bonus_entries' => 0
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_allocate_bonus_entries_for_streaks()
    {
        $user = User::factory()->create();
        $sub = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $this->plan->id,
            'consecutive_payments_count' => 6 // 6-month streak!
        ]);
        
        // On-time payment
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $sub->id, 
            'is_on_time' => true
        ]);
        
        $this->service->allocateEntries($payment);
        
        $this->assertDatabaseHas('lucky_draw_entries', [
            'user_id' => $user->id,
            'lucky_draw_id' => $this->draw->id,
            'base_entries' => 5, // From plan
            'bonus_entries' => 6  // 1 (on-time) + 5 (streak)
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_select_winners_uses_weighted_random()
    {
        $userA = User::factory()->create();
        $subA = Subscription::factory()->create(['user_id' => $userA->id]);
        $payA = Payment::factory()->create(['user_id' => $userA->id, 'subscription_id' => $subA->id]);
        
        LuckyDrawEntry::create([
            'user_id' => $userA->id, 
            'lucky_draw_id' => $this->draw->id, 
            'payment_id' => $payA->id, 
            'base_entries' => 100
        ]);
        
        $userB = User::factory()->create();
        $subB = Subscription::factory()->create(['user_id' => $userB->id]);
        $payB = Payment::factory()->create(['user_id' => $userB->id, 'subscription_id' => $subB->id]);
        
        LuckyDrawEntry::create([
            'user_id' => $userB->id, 
            'lucky_draw_id' => $this->draw->id, 
            'payment_id' => $payB->id, 
            'base_entries' => 1
        ]);

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

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_distribute_prizes_to_wallets()
    {
        $user = User::factory()->create();
        $user->wallet->update(['balance_paise' => 0, 'locked_balance_paise' => 0]);
        
        $sub = Subscription::factory()->create(['user_id' => $user->id]);
        $pay = Payment::factory()->create(['user_id' => $user->id, 'subscription_id' => $sub->id]);

        LuckyDrawEntry::create([
            'user_id' => $user->id, 
            'lucky_draw_id' => $this->draw->id, 
            'payment_id' => $pay->id, 
            'base_entries' => 1
        ]);

        $winners = [$user->id];
        
        $walletService = app(\App\Services\WalletService::class);
        $this->service->distributePrizes($this->draw, $winners, $walletService);
        
        // 1. Wallet balance updated
        $this->assertEquals(1000, $user->wallet->fresh()->balance);
        
        // 2. Bonus transaction created
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $user->id,
            'type' => 'lucky_draw',
            'amount' => 1000
        ]);
        
        // 3. Draw status updated
        $this->assertEquals('completed', $this->draw->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_send_winner_notifications()
    {
        $user = User::factory()->create();
        \Illuminate\Support\Facades\Log::shouldReceive('info')->once()->with("Queueing winner notification for User #{$user->id}");

        $this->service->sendWinnerNotifications([$user->id]);
    }
}
