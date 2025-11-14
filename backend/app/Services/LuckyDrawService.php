<?php
// V-FINAL-1730-366 (Created) | V-FINAL-1730-459 (WalletService Refactor)

namespace App\Services;

use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
// ... (other imports)
use App\Models\BonusTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate-Support\Facades\Log;

class LuckyDrawService
{
    // ... (createMonthlyDraw, allocateEntries, selectWinners remain same) ...
    public function createMonthlyDraw(...) { /* ... */ }
    public function allocateEntries(...) { /* ... */ }
    public function selectWinners(...) { /* ... */ }

    /**
     * Test 5: Distribute prizes to winners' wallets.
     * We now require the WalletService to be passed in.
     */
    public function distributePrizes(LuckyDraw $draw, array $winnerUserIds, WalletService $walletService): void
    {
        $prizeIndex = 0;

        DB::transaction(function () use ($draw, $winnerUserIds, &$prizeIndex, $walletService) {
            foreach ($draw->prize_structure as $tier) {
                // ... (loop logic) ...
                for ($i = 0; $i < (int)$tier['count']; $i++) {
                    $winnerId = $winnerUserIds[$prizeIndex];
                    $entry = $draw->entries()->where('user_id', $winnerId)->with('user')->first();
                    $user = $entry->user;
                    $amount = $tier['amount'];
                    $rank = $tier['rank'];
                    
                    // 1. Mark Entry as Winner
                    $entry->update([
                        'is_winner' => true,
                        'prize_rank' => $rank,
                        'prize_amount' => $amount
                    ]);

                    // 2. Create Bonus Transaction
                    $bonus = BonusTransaction::create([
                        'user_id' => $winnerId,
                        'subscription_id' => $entry->payment->subscription_id,
                        'type' => 'lucky_draw',
                        'amount' => $amount,
                        'description' => "Lucky Draw Winner - Rank {$rank} ({$draw->name})",
                    ]);

                    // 3. Credit Wallet (Using the Service)
                    $walletService->deposit(
                        $user,
                        $amount,
                        'bonus_credit',
                        "Lucky Draw Prize (Rank {$rank})",
                        $bonus
                    );
                    
                    $prizeIndex++;
                }
            }
            $draw->update(['status' => 'completed']);
        });
    }

    // ... (sendWinnerNotifications remains same) ...
    public function sendWinnerNotifications(...) { /* ... */ }
}