<?php
// V-REMEDIATE-1730-164

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Subscription;
use App\Models\BonusTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class ProcessCelebrationBonuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-celebration-bonuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for user birthdays and subscription anniversaries and awards bonuses.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!setting('celebration_bonus_enabled', true)) {
            $this->info('Celebration Bonus module is disabled.');
            return 0;
        }

        $today = Carbon::today();
        $this->processBirthdayBonuses($today);
        $this->processAnniversaryBonuses($today);
        
        $this->info('Celebration bonuses processed successfully.');
        return 0;
    }

    private function processBirthdayBonuses(Carbon $today)
    {
        $this->info('Processing birthday bonuses...');
        
        $users = UserProfile::whereMonth('dob', $today->month)
                            ->whereDay('dob', $today->day)
                            ->with('user.subscription.plan.configs')
                            ->get();

        foreach ($users as $profile) {
            $user = $profile->user;
            if (!$user || !$user->subscription) continue;

            $config = $user->subscription->plan->configs
                ->where('config_key', 'celebration_bonus_config')
                ->first();

            $amount = $config ? ($config->value['birthday_amount'] ?? 50) : 50; // Default 50

            BonusTransaction::create([
                'user_id' => $user->id,
                'subscription_id' => $user->subscription->id,
                'type' => 'celebration',
                'amount' => $amount,
                'description' => 'Happy Birthday! Here is your bonus.',
            ]);
            
            // TODO: Credit wallet
            Log::info("Birthday bonus of {$amount} processed for user {$user->id}");
        }
    }

    private function processAnniversaryBonuses(Carbon $today)
    {
        $this->info('Processing anniversary bonuses...');

        $subscriptions = Subscription::where('status', 'active')
            ->whereMonth('start_date', $today->month)
            ->whereDay('start_date', $today->day)
            ->whereYear('start_date', '<', $today->year) // Don't run on the day they signed up
            ->with('user', 'plan.configs')
            ->get();
            
        foreach ($subscriptions as $sub) {
            $user = $sub->user;
            $yearsActive = $sub->start_date->diffInYears($today);

            $config = $sub->plan->configs
                ->where('config_key', 'celebration_bonus_config')
                ->first();
                
            $amount = $config ? ($config->value['anniversary_amount'] ?? 100) : 100; // Default 100
            
            // Optional: Milestone for anniversary
            $amount *= $yearsActive; 

            BonusTransaction::create([
                'user_id' => $user->id,
                'subscription_id' => $sub->id,
                'type' => 'celebration',
                'amount' => $amount,
                'description' => "Happy {$yearsActive} Year Anniversary!",
            ]);

            // TODO: Credit wallet
            Log::info("Anniversary bonus of {$amount} processed for user {$user->id}");
        }
    }
}