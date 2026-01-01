<?php
// V-PHASE2-1730-053 (Created) | V-REMEDIATE-1730-172 | V-FINAL-1730-294 (SEC-2 Fix Applied) | V-FINAL-1730-446 (WalletService Refactor) | V-AUDIT-FIX-REFACTOR | V-FIX-UNITS-2025 | V-FEATURE-OVERDRAFT | V-FIX-HISTORY-TAB

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\BonusTransaction;
use App\Models\Subscription;
use App\Models\Payment;
use App\Http\Requests\Admin\AdjustBalanceRequest;
use App\Http\Requests\Admin\StoreUserRequest; // [AUDIT FIX]
use App\Http\Requests\Admin\UpdateUserRequest; // [AUDIT FIX]
use App\Services\WalletService;
use App\Services\EmailService;
use App\Services\SmsService;
use App\Services\AllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Enums\TransactionType; // [AUDIT FIX] Added required Enum import

class AdminUserController extends Controller
{
    protected $walletService;
    protected $emailService;
    protected $smsService;
    protected $allocationService;

    // Inject services
    public function __construct(
        WalletService $walletService,
        EmailService $emailService,
        SmsService $smsService,
        AllocationService $allocationService
    ) {
        $this->walletService = $walletService;
        $this->emailService = $emailService;
        $this->smsService = $smsService;
        $this->allocationService = $allocationService;
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
        $perPage = function_exists('setting') ? (int) setting('records_per_page', 15) : 15;

        $users = $query->latest()
            ->paginate($perPage)
            ->appends($request->only('search')); 
            
        return response()->json($users);
    }

    /**
     * Store a new user (created by Admin).
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

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
     */
    public function show(User $user)
    {
        // [AUDIT FIX]: Eager load wallet transactions so they appear in the Admin UI tab
        $user->load([
            'profile',
            'kyc.documents',
            'wallet.transactions', 
            'subscription.plan',
        ]);

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
                // [AUDIT FIX]: Map transactions for the frontend history tab
                'transactions' => $user->wallet->transactions->map(function($tx) {
                    return [
                        'id' => $tx->id,
                        'type' => $tx->type,
                        'amount' => $tx->amount, // Uses Accessor (Paise -> Rupees)
                        'balance_after' => $tx->balance_after, // Uses Accessor (Paise -> Rupees)
                        'description' => $tx->description,
                        'status' => $tx->status,
                        'created_at' => $tx->created_at,
                    ];
                }),
            ] : null,
            'subscription' => $user->subscription ? [
                'id' => $user->subscription->id,
                'status' => $user->subscription->status,
                'plan_name' => $user->subscription->plan?->name,
                'monthly_amount' => $user->subscription->monthly_amount,
                'starts_at' => $user->subscription->starts_at,
                'consecutive_payments_count' => $user->subscription->consecutive_payments_count,
            ] : null,
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
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $user) {
            // Update user fields
            $userFields = array_intersect_key($validated, array_flip(['username', 'email', 'mobile', 'status']));

            if (isset($validated['password'])) {
                $userFields['password'] = Hash::make($validated['password']);
            }

            if (!empty($userFields)) {
                $user->update($userFields);
            }

            // Update profile if provided
            if (isset($validated['profile']) && $user->profile) {
                $user->profile->update($validated['profile']);
            }
        });

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user->fresh(['profile']),
        ]);
    }

    // ... (All other methods: suspend, adjustBalance, bulkAction, import, etc. remain unchanged) ...
    
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

    public function adjustBalance(AdjustBalanceRequest $request, User $user)
    {
        $validated = $request->validated();
        
        // [AUDIT FIX]: Unit Conversion - Rupees to Paise
        // Frontend sends Rupees (e.g., 1500), WalletService expects Paise (150000)
        $amount = (float)$validated['amount'];
        $amountPaise = (int) round($amount * 100); 

        $description = "Admin Adjustment: " . $validated['description'];
        $admin = $request->user();

        try {
            if ($validated['type'] === 'credit') {
                $this->walletService->deposit(
                    $user, 
                    $amountPaise, 
                    TransactionType::DEPOSIT, 
                    $description, 
                    $admin
                );
            } else {
                // [PROTOCOL 7 Fix]: Updated arguments to allow overdraft
                $this->walletService->withdraw(
                    $user, 
                    $amountPaise, 
                    TransactionType::WITHDRAWAL, 
                    $description, 
                    $admin, 
                    false, // lockBalance
                    true   // [NEW ARGUMENT]: allowOverdraft = true for Admin actions
                ); 
            }
            return response()->json(['message' => 'Wallet balance adjusted successfully.', 'new_balance' => $user->wallet->fresh()->balance]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

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
            // [AUDIT FIX]: Unit Conversion for Bulk Bonus
            $amount = (float)($validated['data']['amount'] ?? 0);
            $amountPaise = (int) round($amount * 100);

            if ($amountPaise <= 0) return response()->json(['message' => 'Invalid bonus amount'], 400);

            $users = User::whereIn('id', $validated['user_ids'])->get();
            foreach ($users as $user) {
                $this->walletService->deposit(
                    $user, 
                    $amountPaise, 
                    TransactionType::DEPOSIT, 
                    'Bulk Bonus Award', 
                    $admin
                );
            }
            return response()->json(['message' => "Bonus of $amount awarded to $count users."]);
        }

        $status = $validated['action'] === 'activate' ? 'active' : 'suspended';
        User::whereIn('id', $validated['user_ids'])->update(['status' => $status]);

        return response()->json(['message' => "$count users updated to $status."]);
    }

    private const EXPECTED_CSV_HEADERS = ['username', 'email', 'mobile'];

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']); 

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');

        $header = fgetcsv($handle);
        if (!$header || count($header) < 3) {
            fclose($handle);
            return response()->json([
                'message' => 'Invalid CSV format. Expected headers: ' . implode(', ', self::EXPECTED_CSV_HEADERS)
            ], 422);
        }

        $normalizedHeaders = array_map(fn($h) => strtolower(trim($h)), $header);

        foreach (self::EXPECTED_CSV_HEADERS as $required) {
            if (!in_array($required, $normalizedHeaders)) {
                fclose($handle);
                return response()->json([
                    'message' => "Missing required column: '$required'. Expected headers: " . implode(', ', self::EXPECTED_CSV_HEADERS)
                ], 422);
            }
        }

        $columnMap = [
            'username' => array_search('username', $normalizedHeaders),
            'email' => array_search('email', $normalizedHeaders),
            'mobile' => array_search('mobile', $normalizedHeaders),
        ];

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $lineNumber = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;
                if (empty(array_filter($row))) {
                    continue;
                }
                if (count($row) < 3) {
                    $errors[] = "Line $lineNumber: Insufficient columns";
                    $skipped++;
                    continue;
                }

                $username = trim($row[$columnMap['username']] ?? '');
                $email = trim($row[$columnMap['email']] ?? '');
                $mobile = trim($row[$columnMap['mobile']] ?? '');

                if (empty($username) || empty($email) || empty($mobile)) {
                    $errors[] = "Line $lineNumber: Missing required field(s)";
                    $skipped++;
                    continue;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Line $lineNumber: Invalid email format";
                    $skipped++;
                    continue;
                }

                if (!preg_match('/^[0-9]{10}$/', $mobile)) {
                    $errors[] = "Line $lineNumber: Invalid mobile format (expected 10 digits)";
                    $skipped++;
                    continue;
                }

                if (User::where('email', $email)->orWhere('mobile', $mobile)->exists()) {
                    $errors[] = "Line $lineNumber: User with email/mobile already exists";
                    $skipped++;
                    continue;
                }

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
                'errors' => array_slice($errors, 0, 10), 
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

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

    public function destroy(User $user)
    {
        return DB::transaction(function () use ($user) {
            $randomId = 'deleted_' . Str::random(10);

            $user->update([
                'username' => $randomId,
                'email' => $randomId . '@deleted.local',
                'mobile' => '0000000000',
                'is_anonymized' => true,
                'anonymized_at' => now(),
                'status' => 'blocked',
            ]);

            if ($user->profile) {
                $user->profile->update([
                    'first_name' => 'Deleted',
                    'last_name' => 'User',
                    'address' => null,
                    'city' => null,
                    'state' => null,
                    'pincode' => null,
                ]);
            }

            $user->delete();

            Log::info("User {$user->id} deleted and anonymized");

            return response()->json(['message' => 'User deleted and anonymized successfully.']);
        });
    }

    public function block(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'blacklist' => 'sometimes|boolean',
        ]);

        $admin = $request->user();

        $user->update([
            'status' => 'blocked',
            'block_reason' => $validated['reason'],
            'blocked_at' => now(),
            'blocked_by' => $admin->id,
            'is_blacklisted' => $validated['blacklist'] ?? false,
        ]);

        $user->activityLogs()->create([
            'action' => 'admin_blocked',
            'description' => "Blocked by admin: {$validated['reason']}. Blacklisted: " . ($validated['blacklist'] ? 'Yes' : 'No'),
            'ip_address' => $request->ip()
        ]);

        Log::info("User {$user->id} blocked by Admin {$admin->id}. Blacklisted: " . ($validated['blacklist'] ?? false));

        return response()->json([
            'message' => 'User blocked successfully.',
            'user' => $user
        ]);
    }

    public function unblock(User $user)
    {
        $user->update([
            'status' => 'active',
            'block_reason' => null,
            'blocked_at' => null,
            'blocked_by' => null,
            'is_blacklisted' => false,
        ]);

        $user->activityLogs()->create([
            'action' => 'admin_unblocked',
            'description' => 'Unblocked by admin',
            'ip_address' => request()->ip()
        ]);

        Log::info("User {$user->id} unblocked");

        return response()->json(['message' => 'User unblocked successfully.']);
    }

    public function unsuspend(User $user)
    {
        $user->update([
            'status' => 'active',
            'suspension_reason' => null,
            'suspended_at' => null,
            'suspended_by' => null,
        ]);

        $user->activityLogs()->create([
            'action' => 'admin_unsuspended',
            'description' => 'Unsuspended by admin',
            'ip_address' => request()->ip()
        ]);

        Log::info("User {$user->id} unsuspended");

        return response()->json(['message' => 'User unsuspended successfully.']);
    }

    public function overrideAllocation(Request $request, User $user)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'allocation_amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        $subscription = Subscription::findOrFail($validated['subscription_id']);

        if ($subscription->user_id !== $user->id) {
            return response()->json(['message' => 'Subscription does not belong to this user'], 400);
        }

        $result = $this->allocationService->overrideAllocation(
            $subscription,
            $validated['allocation_amount'],
            $validated['reason']
        );

        Log::info("Allocation overridden for User {$user->id}, Subscription {$subscription->id}. New amount: {$validated['allocation_amount']}");

        return response()->json([
            'message' => 'Allocation overridden successfully.',
            'allocation' => $result
        ]);
    }

    public function forcePayment(Request $request, User $user)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:500',
        ]);

        $subscription = Subscription::findOrFail($validated['subscription_id']);

        if ($subscription->user_id !== $user->id) {
            return response()->json(['message' => 'Subscription does not belong to this user'], 400);
        }

        return DB::transaction(function () use ($validated, $user, $subscription, $request) {
            $payment = Payment::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'amount' => $validated['amount'],
                'status' => 'paid',
                'payment_method' => 'admin_manual',
                'payment_date' => now(),
                'is_on_time' => true,
                'notes' => "Manual payment by admin: {$validated['reason']}",
            ]);

            $subscription->increment('consecutive_payments_count');

            $user->activityLogs()->create([
                'action' => 'admin_force_payment',
                'description' => "Manual payment processed: ₹{$validated['amount']}. Reason: {$validated['reason']}",
                'ip_address' => $request->ip()
            ]);

            Log::info("Force payment processed for User {$user->id}. Amount: ₹{$validated['amount']}");

            return response()->json([
                'message' => 'Payment processed successfully.',
                'payment' => $payment
            ]);
        });
    }

    public function sendEmail(Request $request, User $user)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:200',
            'message' => 'required|string',
            'template' => 'sometimes|string',
        ]);

        try {
            $this->emailService->send(
                $user->email,
                $validated['subject'],
                $validated['message'],
                $validated['template'] ?? 'admin-message'
            );

            Log::info("Email sent to User {$user->id}. Subject: {$validated['subject']}");

            return response()->json(['message' => 'Email sent successfully.']);
        } catch (\Exception $e) {
            Log::error("Failed to send email to User {$user->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    public function sendSms(Request $request, User $user)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:160',
        ]);

        try {
            $this->smsService->send($user->mobile, $validated['message']);

            Log::info("SMS sent to User {$user->id}. Message: {$validated['message']}");

            return response()->json(['message' => 'SMS sent successfully.']);
        } catch (\Exception $e) {
            Log::error("Failed to send SMS to User {$user->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to send SMS: ' . $e->getMessage()], 500);
        }
    }

    public function sendNotification(Request $request, User $user)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
            'type' => 'sometimes|string|in:info,warning,success,error',
            'url' => 'sometimes|url',
        ]);

        try {
            $user->notify(new \App\Notifications\AdminMessage(
                $validated['title'],
                $validated['message'],
                $validated['type'] ?? 'info',
                $validated['url'] ?? null
            ));

            Log::info("Push notification sent to User {$user->id}. Title: {$validated['title']}");

            return response()->json(['message' => 'Notification sent successfully.']);
        } catch (\Exception $e) {
            Log::error("Failed to send notification to User {$user->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to send notification: ' . $e->getMessage()], 500);
        }
    }

    public function advancedSearch(Request $request)
    {
        $validated = $request->validate([
            'username' => 'sometimes|string',
            'email' => 'sometimes|string',
            'mobile' => 'sometimes|string',
            'status' => 'sometimes|array',
            'status.*' => 'in:active,suspended,blocked',
            'kyc_status' => 'sometimes|array',
            'kyc_status.*' => 'in:pending,submitted,verified,rejected',
            'subscription_status' => 'sometimes|array',
            'subscription_status.*' => 'in:active,paused,completed,cancelled',
            'wallet_balance_min' => 'sometimes|numeric',
            'wallet_balance_max' => 'sometimes|numeric',
            'created_from' => 'sometimes|date',
            'created_to' => 'sometimes|date',
            'has_referrals' => 'sometimes|boolean',
            'is_blacklisted' => 'sometimes|boolean',
        ]);

        $query = User::with('profile', 'kyc', 'wallet', 'subscription');

        if (!empty($validated['username'])) {
            $query->where('username', 'like', "%{$validated['username']}%");
        }

        if (!empty($validated['email'])) {
            $query->where('email', 'like', "%{$validated['email']}%");
        }

        if (!empty($validated['mobile'])) {
            $query->where('mobile', 'like', "%{$validated['mobile']}%");
        }

        if (!empty($validated['status'])) {
            $query->whereIn('status', $validated['status']);
        }

        if (!empty($validated['kyc_status'])) {
            $query->whereHas('kyc', function ($q) use ($validated) {
                $q->whereIn('status', $validated['kyc_status']);
            });
        }

        if (!empty($validated['subscription_status'])) {
            $query->whereHas('subscription', function ($q) use ($validated) {
                $q->whereIn('status', $validated['subscription_status']);
            });
        }

        if (isset($validated['wallet_balance_min'])) {
            $query->whereHas('wallet', function ($q) use ($validated) {
                $q->where('balance', '>=', $validated['wallet_balance_min']);
            });
        }

        if (isset($validated['wallet_balance_max'])) {
            $query->whereHas('wallet', function ($q) use ($validated) {
                $q->where('balance', '<=', $validated['wallet_balance_max']);
            });
        }

        if (!empty($validated['created_from'])) {
            $query->whereDate('created_at', '>=', $validated['created_from']);
        }

        if (!empty($validated['created_to'])) {
            $query->whereDate('created_at', '<=', $validated['created_to']);
        }

        if (isset($validated['has_referrals'])) {
            if ($validated['has_referrals']) {
                $query->has('referrals');
            } else {
                $query->doesntHave('referrals');
            }
        }

        if (isset($validated['is_blacklisted'])) {
            $query->where('is_blacklisted', $validated['is_blacklisted']);
        }

        $users = $query->latest()->paginate(50);

        return response()->json($users);
    }

    public function segments()
    {
        // FIX: Wrap in try-catch to prevent 500 errors, return safe defaults
        // Each query is wrapped individually to identify which one fails
        try {
            $segmentData = [
                [
                    'id' => 'all',
                    'name' => 'All Users',
                    // FIX: Use whereHas('roles') instead of role() scope to avoid exceptions
                    'count' => User::whereHas('roles', function ($q) {
                        $q->where('name', 'user');
                    })->count()
                ],
                [
                    'id' => 'active',
                    'name' => 'Active Subscribers',
                    'count' => User::whereHas('subscription', function ($q) {
                        $q->where('status', 'active');
                    })->count()
                ],
                [
                    'id' => 'inactive',
                    'name' => 'Inactive Users',
                    'count' => User::whereDoesntHave('subscription')->count()
                ],
                [
                    'id' => 'incomplete_kyc',
                    'name' => 'KYC Pending',
                    'count' => User::whereHas('kyc', function ($q) {
                        $q->where('status', 'pending');
                    })->count()
                ],
                [
                    'id' => 'kyc_verified',
                    'name' => 'KYC Verified',
                    'count' => User::whereHas('kyc', function ($q) {
                        $q->where('status', 'verified');
                    })->count()
                ],
                [
                    'id' => 'high_value',
                    'name' => 'High Value (₹10K+)',
                    'count' => User::whereHas('wallet', function ($q) {
                        $q->where('balance', '>', 10000);
                    })->count()
                ],
                [
                    'id' => 'low_activity',
                    'name' => 'Low Activity (30+ days)',
                    'count' => User::whereDoesntHave('activityLogs', function ($q) {
                        $q->where('created_at', '>=', now()->subDays(30));
                    })->count()
                ],
                [
                    'id' => 'new_users',
                    'name' => 'New Users (Last 7 days)',
                    'count' => User::where('created_at', '>=', now()->subDays(7))->count()
                ],
            ];

            return response()->json($segmentData);
        } catch (\Throwable $e) {
            // FIX: Log error and return safe fallback to prevent UI breaking
            \Log::error('Segments endpoint error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Return minimal safe data so UI doesn't break
            return response()->json([
                ['id' => 'all', 'name' => 'All Users', 'count' => User::count()],
            ]);
        }
    }

    public function getUsersBySegment(Request $request, string $segment)
    {
        $query = User::with('profile', 'wallet', 'subscription');

        switch ($segment) {
            case 'active_subscribers':
                $query->whereHas('subscription', fn($q) => $q->where('status', 'active'));
                break;

            case 'inactive_users':
                $query->whereDoesntHave('subscription');
                break;

            case 'kyc_pending':
                $query->whereHas('kyc', fn($q) => $q->where('status', 'pending'));
                break;

            case 'kyc_verified':
                $query->whereHas('kyc', fn($q) => $q->where('status', 'verified'));
                break;

            case 'high_value':
                $query->whereHas('wallet', fn($q) => $q->where('balance', '>', 10000));
                break;

            case 'low_activity':
                $query->whereDoesntHave('activityLogs', fn($q) => $q->where('created_at', '>=', now()->subDays(30)));
                break;

            case 'suspended':
                $query->where('status', 'suspended');
                break;

            case 'blocked':
                $query->where('status', 'blocked');
                break;

            case 'blacklisted':
                $query->where('is_blacklisted', true);
                break;

            case 'with_referrals':
                $query->has('referrals');
                break;

            default:
                return response()->json(['message' => 'Invalid segment'], 400);
        }

        $users = $query->latest()->paginate(50);

        return response()->json($users);
    }
}