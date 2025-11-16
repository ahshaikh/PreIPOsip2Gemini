<?php
// V-FINAL-1730-601 (Created)

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Services\ReferralService;

class TestDataSetSeeder extends Seeder
{
    /**
     * Run the "chaos" test data seeds.
     * This creates a variety of user states for testing.
     */
    public function run(): void
    {
        $planA = Plan::find(1);
        $planB = Plan::find(2);
        $planC = Plan::find(3);
        $planD = Plan::find(4);
        
        $referralService = app(ReferralService::class);

        // --- 1. The "Whale" User (High Value, 20+ Referrals) ---
        $whale = User::factory()->create(['email' => 'whale@preipo.com']);
        $whale->assignRole('user');
        $whale->kyc->update(['status' => 'verified']);
        $whaleSub = Subscription::factory()->create([
            'user_id' => $whale->id,
            'plan_id' => $planD->id,
            'amount' => $planD->monthly_amount,
            'status' => 'active',
            'start_date' => now()->subMonths(12),
            'next_payment_date' => now()->addDays(5),
            'consecutive_payments_count' => 12,
        ]);
        // Give them 12 paid payments
        Payment::factory()->count(12)->create([
            'user_id' => $whale->id,
            'subscription_id' => $whaleSub->id,
            'status' => 'paid',
            'amount' => $planD->monthly_amount,
            'is_on_time' => true,
        ]);
        // Give them 20 referrals
        $this->createReferrals($whale, 20);
        $referralService->updateReferrerMultiplier($whale); // This should set them to 3.0x

        // --- 2. The "Churned" User (Cancelled) ---
        $churned = User::factory()->create(['email' => 'churned@preipo.com']);
        $churned->assignRole('user');
        $churned->kyc->update(['status' => 'verified']);
        $churnedSub = Subscription::factory()->create([
            'user_id' => $churned->id,
            'plan_id' => $planA->id,
            'amount' => $planA->monthly_amount,
            'status' => 'cancelled',
            'start_date' => now()->subMonths(6),
            'cancelled_at' => now()->subMonth(),
        ]);
        Payment::factory()->count(5)->create(['subscription_id' => $churnedSub->id, 'status' => 'paid']);

        // --- 3. The "Paused" User ---
        $paused = User::factory()->create(['email' => 'paused@preipo.com']);
        $paused->assignRole('user');
        $paused->kyc->update(['status' => 'verified']);
        Subscription::factory()->create([
            'user_id' => $paused->id,
            'plan_id' => $planB->id,
            'amount' => $planB->monthly_amount,
            'status' => 'paused',
            'start_date' => now()->subMonths(8),
            'pause_start_date' => now()->subDays(10),
            'pause_end_date' => now()->addMonths(2),
        ]);

        // --- 4. The "Payment Failed" User (Inconsistent) ---
        $failed = User::factory()->create(['email' => 'failed@preipo.com']);
        $failed->assignRole('user');
        $failed->kyc->update(['status' => 'verified']);
        $failedSub = Subscription::factory()->create([
            'user_id' => $failed->id,
            'plan_id' => $planA->id,
            'amount' => $planA->monthly_amount,
            'status' => 'active',
            'start_date' => now()->subMonths(2),
            'next_payment_date' => now()->subDays(5), // Payment is 5 days overdue
            'consecutive_payments_count' => 0, // Streak broken
        ]);
        // Paid 1st month, failed 2nd
        Payment::factory()->create(['subscription_id' => $failedSub->id, 'status' => 'paid']);
        Payment::factory()->create(['subscription_id' => $failedSub->id, 'status' => 'failed', 'retry_count' => 3]);

        // --- 5. The "New User" (KYC Submitted) ---
        $newUser = User::factory()->create(['email' => 'newuser@preipo.com']);
        $newUser->assignRole('user');
        $newUser->kyc->update(['status' => 'submitted']); // Pending KYC
        // No subscription yet

        // --- 6. The "Rejected KYC" User ---
        $rejected = User::factory()->create(['email' => 'rejected@preipo.com']);
        $rejected->assignRole('user');
        $rejected->kyc->update(['status' => 'rejected', 'rejection_reason' => 'PAN image was blurry.']);
    }

    /**
     * Helper to create N completed referrals for a user.
     */
    private function createReferrals(User $referrer, int $count)
    {
        $referees = User::factory()->count($count)->create();
        foreach ($referees as $referee) {
            $referee->assignRole('user');
            $referee->kyc->update(['status' => 'verified']); // Assume they are verified
            
            // Create the referral link
            \App\Models\Referral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $referee->id,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}