<?php
// V-PHASE2-1730-053 (Created) | V-REMEDIATE-1730-172 | V-FINAL-1730-294 (SEC-2 Fix Applied) | V-FINAL-1730-446 (WalletService Refactor)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\BonusTransaction;
use App\Http\Requests\Admin\AdjustBalanceRequest;
use App\Services\WalletService; // <-- Service for secure transactions
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserController extends Controller
{
    protected $walletService;

    // Inject the WalletService for secure balance adjustments
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Display a listing of users with search and pagination.
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
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhereHas('profile', function($profileQuery) use ($search) {
                      $profileQuery->where('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $users = $query->latest()
            ->paginate(setting('records_per_page', 25))
            ->appends($request->only('search')); // Append search query to pagination links
            
        return response()->json($users);
    }

    /**
     * Store a new user (created by Admin).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|alpha_dash|min:3|max:50|unique:users,username',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'mobile'   => 'required|string|regex:/^[0-9]{10}$/|unique:users,mobile',
            'password' => 'required|string|min:8',
            'role'     => 'required|string|exists:roles,name',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'],
                'password' => Hash::make($validated['password']),
                'status' => 'active',
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
            ]);

            UserProfile::create(['user_id' => $user->id]);
            UserKyc::create(['user_id' => $user->id, 'status' => 'pending']);
            Wallet::create(['user_id' => $user->id]);
            $user->assignRole($validated['role']);
            
            return $user;
        });

        return response()->json($user, 201);
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
            'wallet.transactions' => fn($q) => $q->latest()->limit(20), 
            'subscription.plan', 
            'activityLogs' => fn($q) => $q->latest()->limit(50),
            'referrals',
            'tickets' => fn($q) => $q->latest()->limit(10)
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
     * "God Mode" Wallet Adjustment. Uses the secure WalletService.
     */
    public function adjustBalance(AdjustBalanceRequest $request, User $user)
    {
        $validated = $request->validated();
        $amount = (float)$validated['amount'];
        $description = "Admin Adjustment: " . $validated['description'];
        $admin = $request->user();

        try {
            if ($validated['type'] === 'credit') {
                $this->walletService->deposit($user, $amount, 'admin_adjustment', $description, $admin);
            } else {
                // false = immediate debit, not a withdrawal request
                $this->walletService->withdraw($user, $amount, 'admin_adjustment', $description, $admin, false); 
            }

            return response()->json(['message' => 'Wallet balance adjusted successfully.', 'new_balance' => $user->wallet->fresh()->balance]);

        } catch (\Exception $e) {
            // Catches "Insufficient funds" or other errors from the service
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Perform a bulk action on multiple users.
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'action' => 'required|in:activate,suspend,bonus',
            'data' => 'nullable|array'
        ]);

        $count = count($validated['user_ids']);
        $admin = $request->user();

        if ($validated['action'] === 'bonus') {
            $amount = $validated['data']['amount'] ?? 0;
            if ($amount <= 0) return response()->json(['message' => 'Invalid bonus amount'], 400);

            $users = User::whereIn('id', $validated['user_ids'])->get();
            foreach ($users as $user) {
                // Use the secure service to award the bonus
                $this->walletService->deposit(
                    $user, 
                    $amount, 
                    'manual_bonus', 
                    'Bulk Bonus Award', 
                    $admin
                );
            }
            return response()->json(['message' => "Bonus of $amount awarded to $count users."]);
        }

        // Status changes
        $status = $validated['action'] === 'activate' ? 'active' : 'suspended';
        User::whereIn('id', $validated['user_ids'])->update(['status' => $status]);

        return response()->json(['message' => "$count users updated to $status."]);
    }

    /**
     * Import users from a CSV file.
     */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle); // Skip header

        $imported = 0;
        $skipped = 0;
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Assuming CSV: Username, Email, Mobile
                if (count($row) < 3) {
                    $skipped++;
                    continue;
                }

                // Skip if user already exists
                if (User::where('email', $row[1])->orWhere('mobile', $row[2])->exists()) {
                    $skipped++;
                    continue;
                }

                // Generate a secure random password for each user
                $randomPassword = Str::random(12) . Str::random(4, '!@#$%^&*');

                $user = User::create([
                    'username' => $row[0],
                    'email' => $row[1],
                    'mobile' => $row[2],
                    'password' => Hash::make($randomPassword),
                    'referral_code' => Str::upper(Str::random(10)),
                    'status' => 'active',
                    // Don't auto-verify - let users verify their own email/mobile
                    'email_verified_at' => null,
                    'mobile_verified_at' => null,
                ]);

                UserProfile::create(['user_id' => $user->id]);
                UserKyc::create(['user_id' => $user->id, 'status' => 'pending']);
                Wallet::create(['user_id' => $user->id]);
                $user->assignRole('user');

                // Send password reset email so user can set their own password
                $user->sendPasswordResetNotification(
                    app('auth.password.broker')->createToken($user)
                );

                $imported++;
            }
            DB::commit();
            fclose($handle);
            return response()->json([
                'message' => "Imported $imported users successfully. Password reset emails have been sent.",
                'imported' => $imported,
                'skipped' => $skipped
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export users to a CSV file.
     */
    public function export()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_export.csv"',
        ];

        $callback = function() {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Username', 'Email', 'Mobile', 'Status', 'Joined At']);

            User::role('user')->with('profile')->chunk(200, function($users) use ($handle) {
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->id, 
                        $user->username, 
                        $user->email, 
                        $user->mobile, 
                        $user->status, 
                        $user->created_at->toDateTimeString()
                    ]);
                }
            });
            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}