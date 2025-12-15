<?php
// V-FINAL-1730-366 (Created) | V-FINAL-1730-459 (WalletService Refactor) | V-AUDIT-FIX-MODULE10 (Memory Leak Fix)

namespace App\Services;

use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\BonusTransaction;
use App\Models\User;
use App\Services\WalletService;
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
        $config = $subscription->plan->getConfig('lucky_draw_config', []);
        if (empty($config)) {
            $config = $subscription->plan->getConfig('lucky_draw_entries', ['count' => 1]);
            $baseEntries = (int)($config['count'] ?? 1);
        } else {
            $baseEntries = (int)($config['entries_per_payment'] ?? 1);
        }
        
        $bonusEntries = 0;

        // 2. Get Bonus Entries
        if ($payment->is_on_time) {
            $bonusEntries += (int)setting('lucky_draw_ontime_bonus', 1);
            
            if ($subscription->consecutive_payments_count % 6 === 0 && $subscription->consecutive_payments_count > 0) {
                $bonusEntries += (int)setting('lucky_draw_streak_bonus', 5);
            }
        }

        // 3. Create or Update the single entry row
        $entry = LuckyDrawEntry::firstOrCreate(
            [
                'user_id' => $payment->user_id,
                'lucky_draw_id' => $currentDraw->id,
            ],
            [
                'payment_id' => $payment->id,
                'base_entries' => 0,
                'bonus_entries' => 0,
            ]
        );

        $entry->increment('base_entries', (int) $baseEntries);
        $entry->increment('bonus_entries', (int) $bonusEntries);
        $entry->update(['payment_id' => $payment->id]);

        Log::info("Allocated {$baseEntries}+{$bonusEntries} entries for User #{$payment->user_id} to Draw #{$currentDraw->id}");
    }

    /**
     * Select winners using Weighted Random Selection (Alias Method Alternative).
     * * MODULE 10 FIX: Memory Optimization
     * Previous logic created an array with one element per ticket (10k users x 100 entries = 1M integers).
     * New logic works on the user list (10k items), accumulating weights.
     * Complexity: O(Winners * Users) - CPU slightly higher, Memory significantly lower.
     */
    public function selectWinners(LuckyDraw $draw): array
    {
        // 1. Load candidates with their calculated weight
        // This keeps only 1 object per user in memory, regardless of how many entries they have.
        $candidates = $draw->entries()
            ->select('user_id', 'base_entries', 'bonus_entries')
            ->get()
            ->map(function ($entry) {
                return [
                    'user_id' => $entry->user_id,
                    'weight' => $entry->base_entries + $entry->bonus_entries
                ];
            })
            ->filter(fn($c) => $c['weight'] > 0)
            ->values();

        // 2. Calculate Total Prizes Needed
        $totalPrizes = 0;
        foreach ($draw->prize_structure as $tier) {
            $totalPrizes += (int)$tier['count'];
        }

        if ($candidates->count() < $totalPrizes) {
            throw new \Exception("Not enough unique participants ({$candidates->count()}) to fill prize pool ($totalPrizes).");
        }

        $winnerIds = [];

        // 3. Select Unique Winners based on Weight
        for ($i = 0; $i < $totalPrizes; $i++) {
            if ($candidates->isEmpty()) break;

            $totalWeight = $candidates->sum('weight');
            $random = mt_rand(1, $totalWeight);
            
            $currentWeight = 0;
            foreach ($candidates as $key => $candidate) {
                $currentWeight += $candidate['weight'];
                
                // Found the winner
                if ($currentWeight >= $random) {
                    $winnerIds[] = $candidate['user_id'];
                    
                    // Remove this user so they can't win again in the same draw
                    $candidates->forget($key); 
                    break;
                }
            }
        }

        return $winnerIds;
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
                    // Safety check if we ran out of winners
                    if (!isset($winnerUserIds[$prizeIndex])) break;

                    $winnerId = $winnerUserIds[$prizeIndex];
                    
                    // Find the winner's entry record
                    $entry = $draw->entries()->where('user_id', $winnerId)->with('user', 'payment.subscription')->first();
                    
                    if (!$entry || !$entry->user) {
                        Log::error("LuckyDrawService: Winner User ID $winnerId not found or has no user record.");
                        $prizeIndex++;
                        continue;
                    }

                    $user = $entry->user;
                    
                    // 1. Mark Entry as Winner
                    $entry->update([
                        'is_winner' => true,
                        'prize_rank' => $rank,
                        'prize_amount' => $amount
                    ]);

                    // 2. Create Bonus Transaction (Ledger)
                    // Ensure subscription_id exists, fall back to null if payment deleted
                    $subId = $entry->payment ? $entry->payment->subscription_id : null;

                    $bonus = BonusTransaction::create([
                        'user_id' => $winnerId,
                        'subscription_id' => $subId,
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