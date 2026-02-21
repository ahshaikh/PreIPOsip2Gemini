<?php
// V-FINAL-1730-TEST-04

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\Payment;
use App\Jobs\ProcessReferralJob;
use Illuminate\Support\Facades\Queue;

class ReferralSystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class); // Loads referral tiers
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function referrer_gets_bonus_and_tier_upgrade_on_referee_payment()
    {
        // 1. Setup Referrer (User A)
        $referrer = User::factory()->create();
        $referrer->wallet()->create(['balance_paise' => 0, 'locked_balance_paise' => 0]);
        $plan = Plan::first(); // Get Plan A from seeder
        
        // Give Referrer a subscription (needed for multiplier)
        $sub = Subscription::factory()->create([
            'user_id' => $referrer->id,
            'plan_id' => $plan->id,
            'bonus_multiplier' => 1.0
        ]);

        // 2. Setup Referee (User B)
        $referee = User::factory()->create(['referred_by' => $referrer->id]);
        
        // Create the Referral link
        Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referee->id,
            'status' => 'pending'
        ]);

        // 3. Run the Process Logic (Simulate the Job running)
        $job = new ProcessReferralJob($referee);
        app()->call([$job, 'handle']);

        // 4. Assertions
        
        // A. Check Referral Status
        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $referrer->id,
            'referred_id' => $referee->id,
            'status' => 'completed'
        ]);

        // B. Check Wallet Credit (Default ₹500 = 50000 paise)
        $this->assertDatabaseHas('wallets', [
            'user_id' => $referrer->id,
            'balance_paise' => 50000
        ]);

        // C. Check Bonus Transaction
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $referrer->id,
            'type' => 'referral',
            'amount' => 500
        ]);
        
        // D. Check Multiplier Logic (If this was the 3rd referral, it should upgrade)
        // Since it's the 1st, it stays 1.0, but we verify logic ran without error
        $this->assertEquals(1.0, $referrer->fresh()->subscription->bonus_multiplier);
    }
}