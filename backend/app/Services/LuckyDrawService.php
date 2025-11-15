<?php
// V-FINAL-1730-366 (Created) | V-FINAL-1730-459 (WalletService Refactor)

namespace App\Services;

use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\BonusTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WalletService; // Service to safely credit wallets
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LuckyDrawService
{
    /**
     * Create a new draw for the current month.
     */
    public function createMonthlyDraw(string $name, $date, array $prizeStructure)
    {
        Log::info("Creating new lucky draw: $name");
        return LuckyDraw::create([
            'name' => $name,
            'draw_date' => $date,
            'prize_structure' => $prizeStructure,
            'status' => 'open'
        ]);
    }

    /**
     * Allocate entries (base + bonus) for a payment.
     * This is called by GenerateLuckyDrawEntryJob.
     */
    public function allocateEntries(Payment $payment)
    {
        if (!setting('lucky_draw_enabled', true)) return;
        
        $currentDraw = LuckyDraw::where('status', 'open')->latest()->first();
        if (!$currentDraw) {
            Log::info("No active lucky draw to allocate entries to.");
            return;
        }

        $subscription = $payment->subscription->load('plan.configs');
        
        // 1. Get Base Entries from Plan Config
        $config = $subscription->plan->getConfig('lucky_draw_entries', ['count' => 1]);
        $baseEntries = (int)$config['count'];
        
        $bonusEntries = 0;

        // 2. Get Bonus Entries
        if ($payment->is_on_time) {
            $bonusEntries += (int)setting('lucky_draw_ontime_bonus', 1); // 1 bonus for on-time
            
            // 5 bonus entries for every 6-month streak
            if ($subscription->consecutive_payments_count % 6 === 0 && $subscription->consecutive_payments_count > 0) {
                $bonusEntries += (int)setting('lucky_draw_streak_bonus', 5);
            }
        }

        // 3. Create or Update the single entry row
        LuckyDrawEntry::updateOrCreate(
            [
                'user_id' => $payment->user_id,
                'lucky_draw_id' => $currentDraw->id,
            ],
            [
                'payment_id' => $payment->id, // Track last payment
                'base_entries' => DB::raw("base_entries + $baseEntries"),
                'bonus_entries' => DB::raw("bonus_entries + $bonusEntries"),
            ]
        );

        Log::info("Allocated {$baseEntries}+{$bonusEntries} entries for User #{$payment->user_id} to Draw #{$currentDraw->id}");
    }

    /**
     * Select winners using weighted random.
     * This builds a "virtual hat" and pulls unique winners.
     */
    public function selectWinners(LuckyDraw $draw): array
    {
        $hat = [];
        $entries = $draw->entries()->get();
        
        foreach ($entries as $entry) {
            // Get total entries (e.g., 5 base + 3 bonus = 8)
            $total = $entry->base_entries + $entry->bonus_entries;
            for ($i = 0; $i < $total; $i++) {
                $hat[] = $entry->user_id; // Add user to hat *once per entry*
            }
        }

        if (empty($hat)) {
            throw new \Exception("No entries to draw from.");
        }

        shuffle($hat); // Shuffle the hat
        
        // Get unique user IDs, in random order. A user cannot win twice.
        $uniqueWinners = array_unique($hat);
        
        $totalPrizes = 0;
        foreach ($draw->prize_structure as $tier) {
            $totalPrizes += (int)$tier['count'];
        }

        if (count($uniqueWinners) < $totalPrizes) {
            throw new \Exception("Not enough unique participants ({$totalPrizes}) to fill prize pool.");
        }

        // Return the list of winning User IDs
        return array_slice($uniqueWinners, 0, $totalPrizes);
    }

    /**
     * Distribute prizes to winners' wallets.
     * Requires WalletService for safe, atomic deposits.
     */
    public function distributePrizes(LuckyDraw $draw, array $winnerUserIds, WalletService $walletService): void
    {
        $prizeIndex = 0;

        DB::transaction(function () use ($draw, $winnerUserIds, &$prizeIndex, $walletService) {
            
            foreach ($draw->prize_structure as $tier) {
                $rank = (int)$tier['rank'];
                $amount = (float)$tier['amount'];
                $count = (int)$tier['count'];

                for ($i = 0; $i < $count; $i++) {
                    $winnerId = $winnerUserIds[$prizeIndex];
                    
                    // Find the winner's entry record
                    $entry = $draw->entries()->where('user_id', $winnerId)->with('user', 'payment.subscription')->first();
                    $user = $entry->user;
                    
                    // 1. Mark Entry as Winner
                    $entry->update([
                        'is_winner' => true,
                        'prize_rank' => $rank,
                        'prize_amount' => $amount
                    ]);

                    // 2. Create Bonus Transaction (Ledger)
                    $bonus = BonusTransaction::create([
                        'user_id' => $winnerId,
                        'subscription_id' => $entry->payment->subscription_id,
                        'type' => 'lucky_draw',
                        'amount' => $amount,
                        'description' => "Lucky Draw Winner - Rank {$rank} ({$draw->name})",
                    ]);

                    // 3. Credit Wallet (Safe Deposit)
                    $walletService->deposit(
                        $user,
                        $amount,
                        'bonus_credit', // Transaction type
                        "Lucky Draw Prize (Rank {$rank})",
                        $bonus // Link to the bonus transaction
                    );
                    
                    $prizeIndex++;
                }
            }
            
            $draw->update(['status' => 'completed']);
        });
    }

    /**
     * Send Notifications to winners.
     */
    public function sendWinnerNotifications(array $winnerUserIds): void
    {
        $winners = User::whereIn('id', $winnerUserIds)->get();
        foreach ($winners as $winner) {
            // In V2, we would dispatch a job here:
            // SendLuckyDrawWinnerJob::dispatch($winner);
            Log::info("Queueing winner notification for User #{$winner->id}");
        }
    }
}