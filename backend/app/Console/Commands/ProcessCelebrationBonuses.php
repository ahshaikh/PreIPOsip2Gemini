<?php
// V-FINAL-1730-348 (Created) | V-FINAL-1730-457 (WalletService Refactor)

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Subscription;
use App\Models\BonusTransaction;
use App\Models\CelebrationEvent;
use App\Services\WalletService; // <-- IMPORT
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCelebrationBonuses extends Command
{
    protected $signature = 'app:process-celebration-bonuses';
    protected $description = 'Awards birthday, anniversary, and festival bonuses.';

    public function handle(WalletService $walletService)
    {
        if (!setting('celebration_bonus_enabled', true)) return;
        $today = Carbon::today();
        
        $this->processBirthdayBonuses($today, $walletService);
        $this.processAnniversaryBonuses($today, $walletService);
        $this.processFestivalBonuses($today, $walletService);
        
        $this.info('Celebration bonuses processed successfully.');
        return 0;
    }

    private function processBirthdayBonuses(Carbon $today, WalletService $walletService)
    {
        $birthdays = UserProfile::whereMonth('dob', $today->month)
                            ->whereDay('dob', $today->day)
                            ->whereHas('user.subscription', fn($q) => $q->where('status', 'active'))
                            ->with('user.subscription.plan.configs', 'user.wallet')
                            ->get();

        foreach ($birthdays as $profile) {
            $config = $profile->user->subscription->plan->getConfig('celebration_bonus_config');
            $amount = $config['birthday_amount'] ?? 50;
            $this->awardBonus($profile->user, $amount, 'celebration', "Happy Birthday!", $walletService);
        }
    }

    private function processAnniversaryBonuses(Carbon $today, WalletService $walletService)
    {
        $subscriptions = Subscription::where('status', 'active')
                                     ->whereMonth('start_date', $today->month)
                                     ->whereDay('start_date', $today->day)
                                     ->whereYear('start_date', '<', $today->year)
                                     ->with('user.wallet', 'plan.configs')
                                     ->get();
            
        foreach ($subscriptions as $sub) {
            $yearsActive = $sub->start_date->diffInYears($today);
            $config = $sub->plan->getConfig('celebration_bonus_config');
            $baseAmount = $config['anniversary_amount'] ?? 100;
            $amount = $baseAmount * $yearsActive; 
            $this->awardBonus($sub->user, $amount, 'celebration', "Happy {$yearsActive} Year Anniversary!", $walletService);
        }
    }

    private function processFestivalBonuses(Carbon $today, WalletService $walletService)
    {
        $events = CelebrationEvent::activeToday($today)->get();
        if ($events->isEmpty()) return;

        foreach ($events as $event) {
            $subscriptions = Subscription::where('status', 'active')
                                ->with('user.wallet', 'plan')
                                ->get();

            foreach ($subscriptions as $sub) {
                $planKey = $sub->plan->slug ?? 'plan_a';
                $amount = $event->bonus_amount_by_plan[$planKey] ?? 0;
                
                if ($amount > 0) {
                    $this->awardBonus($sub->user, $amount, 'celebration', "Happy {$event->name}!", $walletService);
                }
            }
        }
    }

    private function awardBonus($user, $amount, $type, $msg, WalletService $walletService)
    {
        if (!$user || !$user->subscription || !$user->wallet) return;

        DB::transaction(function() use ($user, $amount, $type, $msg, $walletService) {
            $bonus = BonusTransaction::create([
                'user_id' => $user->id,
                'subscription_id' => $user->subscription->id,
                'type' => $type,
                'amount' => $amount,
                'description' => $msg,
            ]);

            // Use the service to deposit
            $walletService->deposit($user, $amount, 'bonus_credit', $msg, $bonus);
        });
    }
}