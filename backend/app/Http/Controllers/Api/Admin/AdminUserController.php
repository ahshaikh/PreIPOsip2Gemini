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
     * Display the specified user with admin-relevant details.
     * SECURITY: Data is filtered to prevent excessive exposure.
     */
    public function show(User $user)
    {
        // Load relationships with controlled data
        $user->load([
            'profile',
            'kyc.documents',
            'wallet',
            'subscription.plan',
        ]);

        // Build a controlled response (don't expose everything)
        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'status' => $user->status,
            'email_verified_at' => $user->email_verified_at,
            'mobile_verified_at' => $user->mobile_verified_at,
            'created_at' => $user->created_at,
            'profile' => $user->profile ? [
                'first_name' => $user->profile->first_name,
                'last_name' => $user->profile->last_name,
                'avatar_url' => $user->profile->avatar_url,
                'city' => $user->profile->city,
                'state' => $user->profile->state,
            ] : null,
            'kyc' => $user->kyc ? [
                'status' => $user->kyc->status,
                'pan_number' => $user->kyc->pan_number ? '****' . substr($user->kyc->pan_number, -4) : null,
                'verified_at' => $user->kyc->verified_at,
                'rejection_reason' => $user->kyc->rejection_reason,
                'documents_count' => $user->kyc->documents?->count() ?? 0,
            ] : null,
            'wallet' => $user->wallet ? [
                'balance' => $user->wallet->balance,
                'locked_balance' => $user->wallet->locked_balance,
            ] : null,
            'subscription' => $user->subscription ? [
                'id' => $user->subscription->id,
                'status' => $user->subscription->status,
                'plan_name' => $user->subscription->plan?->name,
                'monthly_amount' => $user->subscription->monthly_amount,
                'starts_at' => $user->subscription->starts_at,
                'consecutive_payments_count' => $user->subscription->consecutive_payments_count,
            ] : null,
            // Summary counts instead of full data
            'stats' => [
                'total_payments' => $user->subscription?->payments()->count() ?? 0,
                'total_bonuses' => BonusTransaction::where('user_id', $user->id)->sum('amount'),
                'referral_count' => $user->referrals()->count(),
                'open_tickets' => $user->tickets()->where('status', 'open')->count(),
            ],
        ]);
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
     * Expected CSV headers for user import.
     */
    private const EXPECTED_CSV_HEADERS = ['username', 'email', 'mobile'];

    /**
     * Import users from a CSV file.
     * SECURITY: Validates headers before processing.
     */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']); // Max 5MB

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');

        // --- SECURITY: Validate CSV headers ---
        $header = fgetcsv($handle);
        if (!$header || count($header) < 3) {
            fclose($handle);
            return response()->json([
                'message' => 'Invalid CSV format. Expected headers: ' . implode(', ', self::EXPECTED_CSV_HEADERS)
            ], 422);
        }

        // Normalize headers (lowercase, trim)
        $normalizedHeaders = array_map(fn($h) => strtolower(trim($h)), $header);

        // Check required headers exist
        foreach (self::EXPECTED_CSV_HEADERS as $required) {
            if (!in_array($required, $normalizedHeaders)) {
                fclose($handle);
                return response()->json([
                    'message' => "Missing required column: '$required'. Expected headers: " . implode(', ', self::EXPECTED_CSV_HEADERS)
                ], 422);
            }
        }

        // Map header positions
        $columnMap = [
            'username' => array_search('username', $normalizedHeaders),
            'email' => array_search('email', $normalizedHeaders),
            'mobile' => array_search('mobile', $normalizedHeaders),
        ];
        // --- END HEADER VALIDATION ---

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $lineNumber = 1; // Header was line 1

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Validate row has enough columns
                if (count($row) < 3) {
                    $errors[] = "Line $lineNumber: Insufficient columns";
                    $skipped++;
                    continue;
                }

                $username = trim($row[$columnMap['username']] ?? '');
                $email = trim($row[$columnMap['email']] ?? '');
                $mobile = trim($row[$columnMap['mobile']] ?? '');

                // Validate required fields
                if (empty($username) || empty($email) || empty($mobile)) {
                    $errors[] = "Line $lineNumber: Missing required field(s)";
                    $skipped++;
                    continue;
                }

                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Line $lineNumber: Invalid email format";
                    $skipped++;
                    continue;
                }

                // Validate mobile format (10 digits)
                if (!preg_match('/^[0-9]{10}$/', $mobile)) {
                    $errors[] = "Line $lineNumber: Invalid mobile format (expected 10 digits)";
                    $skipped++;
                    continue;
                }

                // Skip if user already exists
                if (User::where('email', $email)->orWhere('mobile', $mobile)->exists()) {
                    $errors[] = "Line $lineNumber: User with email/mobile already exists";
                    $skipped++;
                    continue;
                }

                // Generate a secure random password
                $randomPassword = Str::random(12) . Str::random(4, '!@#$%^&*');

                $user = User::create([
                    'username' => $username,
                    'email' => $email,
                    'mobile' => $mobile,
                    'password' => Hash::make($randomPassword),
                    'referral_code' => Str::upper(Str::random(10)),
                    'status' => 'active',
                    'email_verified_at' => null,
                    'mobile_verified_at' => null,
                ]);

                UserProfile::create(['user_id' => $user->id]);
                UserKyc::create(['user_id' => $user->id, 'status' => 'pending']);
                Wallet::create(['user_id' => $user->id]);
                $user->assignRole('user');

                // Send password reset email
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
                'skipped' => $skipped,
                'errors' => array_slice($errors, 0, 10), // Return first 10 errors
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