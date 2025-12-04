<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BonusTransaction;
use App\Notifications\BonusCredited;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminBonusController extends Controller
{
    /**
     * Award a special bonus to a user (Admin only)
     *
     * POST /api/v1/admin/bonuses/award-special
     */
    public function awardSpecialBonus(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $amount = (float) $validated['amount'];
        $reason = $validated['reason'];

        // Create special bonus transaction
        $bonus = BonusTransaction::create([
            'user_id' => $user->id,
            'subscription_id' => $user->subscriptions()->latest()->first()?->id,
            'payment_id' => null, // No specific payment associated
            'type' => 'special_bonus',
            'amount' => $amount,
            'multiplier_applied' => 1.0,
            'base_amount' => $amount,
            'description' => "Special Bonus: {$reason}"
        ]);

        // Send notification to user
        $user->notify(new BonusCredited($amount, 'Special'));

        Log::info("Admin awarded special bonus: ₹{$amount} to User {$user->id}. Reason: {$reason}");

        return response()->json([
            'success' => true,
            'message' => "Special bonus of ₹{$amount} awarded to {$user->username}",
            'bonus' => $bonus,
        ]);
    }

    /**
     * Award bulk special bonuses to multiple users
     *
     * POST /api/v1/admin/bonuses/award-bulk
     */
    public function awardBulkBonus(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $userIds = $validated['user_ids'];
        $amount = (float) $validated['amount'];
        $reason = $validated['reason'];
        $successCount = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (!$user) continue;

            try {
                $bonus = BonusTransaction::create([
                    'user_id' => $user->id,
                    'subscription_id' => $user->subscriptions()->latest()->first()?->id,
                    'payment_id' => null,
                    'type' => 'special_bonus',
                    'amount' => $amount,
                    'multiplier_applied' => 1.0,
                    'base_amount' => $amount,
                    'description' => "Special Bonus: {$reason}"
                ]);

                $user->notify(new BonusCredited($amount, 'Special'));
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Failed to award bonus to User {$userId}: " . $e->getMessage());
            }
        }

        Log::info("Admin awarded bulk bonuses: ₹{$amount} to {$successCount} users. Reason: {$reason}");

        return response()->json([
            'success' => true,
            'message' => "Special bonus of ₹{$amount} awarded to {$successCount} users",
            'awarded_count' => $successCount,
        ]);
    }
}
