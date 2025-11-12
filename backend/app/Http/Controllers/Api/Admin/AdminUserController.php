<?php
// V-REMEDIATE-1730-172 (God Mode Enabled)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::role('user')->with('profile', 'kyc', 'wallet');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(setting('records_per_page', 25));
            
        return response()->json($users);
    }

    /**
     * Display the specified user with ALL details ("God View").
     */
    public function show(User $user)
    {
        // Load every relationship relevant to admin oversight
        $user->load([
            'profile', 
            'kyc.documents', 
            'wallet.transactions', 
            'subscription.plan', 
            'activityLogs' => function($query) {
                $query->latest()->limit(50);
            },
            'referrals',
            'tickets'
        ]);
        
        return response()->json($user);
    }

    /**
     * Update the specified user's admin-level details.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,suspended,blocked',
            // Add other editable fields if necessary
        ]);
        
        $user->update($validated);
        
        return response()->json([
            'message' => 'User status updated.',
            'user' => $user,
        ]);
    }

    /**
     * Suspend a user (Quick Action).
     */
    public function suspend(Request $request, User $user)
    {
        $request->validate(['reason' => 'required|string|max:255']);
        
        $user->update(['status' => 'suspended']);
        
        // Log this action
        $user->activityLogs()->create([
            'action' => 'admin_suspended',
            'description' => 'Suspended by admin: ' . $request->reason,
            'ip_address' => $request->ip()
        ]);

        return response()->json(['message' => 'User has been suspended.']);
    }

    /**
     * "God Mode" Wallet Adjustment.
     * Allows admin to manually credit or debit a user's wallet.
     */
    public function adjustBalance(Request $request, User $user)
    {
        $validated = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        $wallet = $user->wallet; // Assumes wallet exists (created in seeders/registration)

        DB::transaction(function () use ($wallet, $user, $validated) {
            $amount = $validated['amount'];
            
            if ($validated['type'] === 'debit') {
                if ($wallet->balance < $amount) {
                    abort(400, "Insufficient funds for debit.");
                }
                $wallet->decrement('balance', $amount);
                $transactionAmount = -$amount; // Negative for debit
            } else {
                $wallet->increment('balance', $amount);
                $transactionAmount = $amount; // Positive for credit
            }

            // Create the transaction record
            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'admin_adjustment',
                'amount' => $transactionAmount,
                'balance_after' => $wallet->balance,
                'description' => "Admin Adjustment: " . $validated['description'],
                'reference_type' => User::class, // Linked to the admin user logically
                'reference_id' => auth()->id(),
            ]);
        });

        return response()->json(['message' => 'Wallet balance adjusted successfully.', 'new_balance' => $wallet->balance]);
    }
}