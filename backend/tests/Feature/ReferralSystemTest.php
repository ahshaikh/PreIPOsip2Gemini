<?php
// V-FINAL-1730-TEST-04 (Deterministic & Ledger-Safe)

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\ReferralCampaign;
use App\Models\BonusTransaction;
use App\Models\Setting;
use App\Jobs\ProcessReferralJob;
use Illuminate\Support\Facades\DB;

class ReferralSystemTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);

        // Ensure referral module is enabled (explicit & deterministic)
        Setting::updateOrCreate(
            ['key' => 'referral_enabled'],
            ['value' => '1', 'type' => 'boolean', 'group' => 'referral']
        );

        Setting::updateOrCreate(
            ['key' => 'referral_kyc_required'],
            ['value' => '1', 'type' => 'boolean', 'group' => 'referral']
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function referrer_gets_bonus_and_tier_upgrade_on_referee_payment()
    {
        // =====================================================
        // 1️⃣ Create Active Referral Campaign
        // =====================================================
        $campaign = ReferralCampaign::factory()->create([
            'is_active' => 1,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        // =====================================================
        // 2️⃣ Create Referrer (User A)
        // =====================================================
        $referrer = User::factory()->create();
        $plan = Plan::first();

        // Ensure verified KYC (required by job)
        $referrer->kyc()->create([
            'status' => 'verified'
        ]);

        // Ensure wallet starts at zero
        $referrer->wallet->update([
            'balance_paise' => 0,
            'locked_balance_paise' => 0,
        ]);

        // Active subscription required for multiplier logic
        Subscription::factory()->create([
            'user_id' => $referrer->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'bonus_multiplier' => 1.0
        ]);

        // =====================================================
        // 3️⃣ Create Referee (User B)
        // =====================================================
        $referee = User::factory()->create([
            'referred_by' => $referrer->id
        ]);

        $referee->kyc()->create([
            'status' => 'verified'
        ]);

        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referee->id,
            'status' => 'pending',
            'referral_campaign_id' => $campaign->id,
        ]);

        // Capture wallet state before execution
        $walletBefore = $referrer->wallet->balance_paise;

        // =====================================================
        // 4️⃣ Execute Job (Production-Accurate Execution)
        // =====================================================
        ProcessReferralJob::dispatchSync($referee);

        $referrer->refresh();

        // =====================================================
        // 5️⃣ Assertions (State Transition Based)
        // =====================================================

        // A. Referral marked as completed
        $this->assertDatabaseHas('referrals', [
            'id' => $referral->id,
            'status' => 'completed'
        ]);

        // B. Wallet balance increased
        $this->assertGreaterThan(
            $walletBefore,
            $referrer->wallet->balance_paise,
            'Wallet was not credited by referral job'
        );

        // C. BonusTransaction created
        $this->assertDatabaseHas('bonus_transactions', [
            'user_id' => $referrer->id,
            'type' => 'referral'
        ]);

        // D. Multiplier logic executed (remains 1.0 for first referral)
        $this->assertGreaterThan(
            1.0,
            (float) $referrer->subscription->bonus_multiplier,
            'Referral tier multiplier was not upgraded'
        );
    }
}