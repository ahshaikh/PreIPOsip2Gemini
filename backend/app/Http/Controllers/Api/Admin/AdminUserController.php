<?php
// V-FINAL-1730-216 (Bulk & Import/Export Added)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\BonusTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserController extends Controller
{
    // ... (index, show, update, suspend methods remain same as before, included below for completeness) ...

    public function index(Request $request)
    {
        $query = User::role('user')->with('profile', 'kyc', 'wallet');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate(setting('records_per_page', 25));
    }

    public function show(User $user)
    {
        $user->load([
            'profile', 'kyc.documents', 'wallet.transactions', 
            'subscription.plan', 'activityLogs', 'referrals', 'tickets'
        ]);
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate(['status' => 'required|in:active,suspended,blocked']);
        $user->update($validated);
        return response()->json(['message' => 'User status updated.', 'user' => $user]);
    }

    public function suspend(Request $request, User $user)
    {
        $request->validate(['reason' => 'required|string|max:255']);
        $user->update(['status' => 'suspended']);
        $user->activityLogs()->create([
            'action' => 'admin_suspended',
            'description' => 'Suspended by admin: ' . $request->reason,
            'ip_address' => $request->ip()
        ]);
        return response()->json(['message' => 'User has been suspended.']);
    }

    public function adjustBalance(Request $request, User $user)
    {
        $validated = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        $wallet = $user->wallet;

        DB::transaction(function () use ($wallet, $user, $validated) {
            $amount = $validated['amount'];
            if ($validated['type'] === 'debit') {
                if ($wallet->balance < $amount) abort(400, "Insufficient funds.");
                $wallet->decrement('balance', $amount);
                $txnAmount = -$amount;
            } else {
                $wallet->increment('balance', $amount);
                $txnAmount = $amount;
            }

            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => 'admin_adjustment',
                'amount' => $txnAmount,
                'balance_after' => $wallet->balance,
                'description' => "Admin: " . $validated['description'],
                'reference_type' => User::class,
                'reference_id' => auth()->id(),
            ]);
        });

        return response()->json(['message' => 'Balance adjusted.', 'new_balance' => $wallet->balance]);
    }

    // --- NEW: Bulk Actions ---
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'action' => 'required|in:activate,suspend,bonus',
            'data' => 'nullable|array' // e.g., amount for bonus
        ]);

        $count = count($validated['user_ids']);

        if ($validated['action'] === 'bonus') {
            $amount = $validated['data']['amount'] ?? 0;
            if ($amount <= 0) return response()->json(['message' => 'Invalid bonus amount'], 400);

            foreach ($validated['user_ids'] as $id) {
                // Create simple bonus
                BonusTransaction::create([
                    'user_id' => $id,
                    'type' => 'manual_bonus',
                    'amount' => $amount,
                    'subscription_id' => 0, // Placeholder
                    'description' => 'Bulk Bonus Award'
                ]);
                // Credit Wallet
                $user = User::find($id);
                if ($user && $user->wallet) {
                    $user->wallet->increment('balance', $amount);
                }
            }
            return response()->json(['message' => "Bonus of $amount awarded to $count users."]);
        }

        // Status changes
        $status = $validated['action'] === 'activate' ? 'active' : 'suspended';
        User::whereIn('id', $validated['user_ids'])->update(['status' => $status]);

        return response()->json(['message' => "$count users updated to $status."]);
    }

    // --- NEW: Import CSV ---
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle); // Skip header

        $imported = 0;
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Assuming CSV: Username, Email, Mobile
                if (count($row) < 3) continue;
                
                $user = User::create([
                    'username' => $row[0],
                    'email' => $row[1],
                    'mobile' => $row[2],
                    'password' => Hash::make('Welcome123'), // Default password
                    'referral_code' => Str::upper(Str::random(8)),
                    'status' => 'active'
                ]);
                
                UserProfile::create(['user_id' => $user->id]);
                UserKyc::create(['user_id' => $user->id]);
                Wallet::create(['user_id' => $user->id]);
                $user->assignRole('user');
                
                $imported++;
            }
            DB::commit();
            fclose($handle);
            return response()->json(['message' => "Imported $imported users successfully."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    // --- NEW: Export CSV ---
    public function export()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_export.csv"',
        ];

        $callback = function() {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Username', 'Email', 'Mobile', 'Status', 'Joined At']);

            User::role('user')->chunk(100, function($users) use ($handle) {
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->id, 
                        $user->username, 
                        $user->email, 
                        $user->mobile, 
                        $user->status, 
                        $user->created_at
                    ]);
                }
            });
            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}