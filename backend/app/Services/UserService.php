<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\BonusTransaction;
use App\Jobs\SendOtpJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class UserService
{
    protected $walletService;
    protected $allocationService;

    public function __construct(WalletService $walletService, AllocationService $allocationService)
    {
        $this->walletService = $walletService;
        $this->allocationService = $allocationService;
    }

    // --- USER CREATION & UPDATES ---

    public function createUser(array $data, string $role = 'user'): User
    {
        return DB::transaction(function () use ($data, $role) {
            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'mobile' => $data['mobile'],
                'password' => Hash::make($data['password']),
                'status' => 'active',
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
            ]);

            UserProfile::create(['user_id' => $user->id]);
            UserKyc::create(['user_id' => $user->id, 'status' => 'pending']);
            Wallet::create(['user_id' => $user->id]);
            $user->assignRole($role);
            
            return $user;
        });
    }

    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $userFields = array_intersect_key($data, array_flip(['username', 'email', 'mobile', 'status']));
            
            if (isset($data['password'])) {
                $userFields['password'] = Hash::make($data['password']);
            }
            
            if (!empty($userFields)) {
                $user->update($userFields);
            }

            if (isset($data['profile']) && $user->profile) {
                $user->profile->update($data['profile']);
            }

            return $user->fresh(['profile']);
        });
    }

    // --- SEARCH & SEGMENTATION ---

    public function advancedSearch(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = User::with('profile', 'kyc', 'wallet', 'subscription');

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function($q) use ($s) {
                $q->where('username', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('mobile', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['username'])) $query->where('username', 'like', "%{$filters['username']}%");
        if (!empty($filters['email'])) $query->where('email', 'like', "%{$filters['email']}%");
        if (!empty($filters['mobile'])) $query->where('mobile', 'like', "%{$filters['mobile']}%");
        
        if (!empty($filters['status'])) $query->whereIn('status', $filters['status']);
        
        if (!empty($filters['kyc_status'])) {
            $query->whereHas('kyc', fn($q) => $q->whereIn('status', $filters['kyc_status']));
        }

        if (!empty($filters['subscription_status'])) {
            $query->whereHas('subscription', fn($q) => $q->whereIn('status', $filters['subscription_status']));
        }

        if (isset($filters['wallet_balance_min'])) {
            $query->whereHas('wallet', fn($q) => $q->where('balance', '>=', $filters['wallet_balance_min']));
        }

        if (!empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['has_referrals'])) {
            $filters['has_referrals'] ? $query->has('referrals') : $query->doesntHave('referrals');
        }

        if (isset($filters['is_blacklisted'])) {
            $query->where('is_blacklisted', $filters['is_blacklisted']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getUserSegments(): array
    {
        return [
            'active_subscribers' => User::whereHas('subscription', fn($q) => $q->where('status', 'active'))->count(),
            'inactive_users' => User::whereDoesntHave('subscription')->count(),
            'kyc_pending' => User::whereHas('kyc', fn($q) => $q->where('status', 'pending'))->count(),
            'kyc_verified' => User::whereHas('kyc', fn($q) => $q->where('status', 'verified'))->count(),
            'high_value' => User::whereHas('wallet', fn($q) => $q->where('balance', '>', 10000))->count(),
            'low_activity' => User::whereDoesntHave('activityLogs', fn($q) => $q->where('created_at', '>=', now()->subDays(30)))->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'blocked' => User::where('status', 'blocked')->count(),
            'blacklisted' => User::where('is_blacklisted', true)->count(),
            'with_referrals' => User::has('referrals')->count(),
            'total_users' => User::role('user')->count(),
        ];
    }

    public function getUsersBySegment(string $segment, int $perPage = 50)
    {
        $query = User::with('profile', 'wallet', 'subscription');

        switch ($segment) {
            case 'active_subscribers': $query->whereHas('subscription', fn($q) => $q->where('status', 'active')); break;
            case 'inactive_users': $query->whereDoesntHave('subscription'); break;
            case 'kyc_pending': $query->whereHas('kyc', fn($q) => $q->where('status', 'pending')); break;
            case 'kyc_verified': $query->whereHas('kyc', fn($q) => $q->where('status', 'verified')); break;
            case 'high_value': $query->whereHas('wallet', fn($q) => $q->where('balance', '>', 10000)); break;
            case 'low_activity': $query->whereDoesntHave('activityLogs', fn($q) => $q->where('created_at', '>=', now()->subDays(30))); break;
            case 'suspended': $query->where('status', 'suspended'); break;
            case 'blocked': $query->where('status', 'blocked'); break;
            case 'blacklisted': $query->where('is_blacklisted', true); break;
            case 'with_referrals': $query->has('referrals'); break;
            default: throw new \InvalidArgumentException('Invalid segment');
        }

        return $query->latest()->paginate($perPage);
    }

    // --- STATUS MANAGEMENT (BLOCK/SUSPEND) ---

    public function suspendUser(User $user, string $reason, User $admin)
    {
        $user->update(['status' => 'suspended', 'suspension_reason' => $reason, 'suspended_at' => now(), 'suspended_by' => $admin->id]);
        $this->logAction($user, 'admin_suspended', "Suspended: $reason", $admin);
    }

    public function unsuspendUser(User $user, User $admin)
    {
        $user->update(['status' => 'active', 'suspension_reason' => null, 'suspended_at' => null, 'suspended_by' => null]);
        $this->logAction($user, 'admin_unsuspended', "Unsuspended", $admin);
    }

    public function blockUser(User $user, string $reason, bool $blacklist, User $admin)
    {
        $user->update(['status' => 'blocked', 'block_reason' => $reason, 'blocked_at' => now(), 'blocked_by' => $admin->id, 'is_blacklisted' => $blacklist]);
        $this->logAction($user, 'admin_blocked', "Blocked: $reason. Blacklist: " . ($blacklist ? 'Yes' : 'No'), $admin);
    }

    public function unblockUser(User $user, User $admin)
    {
        $user->update(['status' => 'active', 'block_reason' => null, 'blocked_at' => null, 'blocked_by' => null, 'is_blacklisted' => false]);
        $this->logAction($user, 'admin_unblocked', "Unblocked", $admin);
    }

    public function anonymizeAndDelete(User $user)
    {
        return DB::transaction(function () use ($user) {
            $randomId = 'deleted_' . Str::random(10);
            $user->update(['username' => $randomId, 'email' => $randomId . '@deleted.local', 'mobile' => '0000000000', 'is_anonymized' => true, 'anonymized_at' => now(), 'status' => 'blocked']);
            if ($user->profile) {
                $user->profile->update(['first_name' => 'Deleted', 'last_name' => 'User', 'address' => null, 'city' => null, 'state' => null, 'pincode' => null]);
            }
            $user->delete();
            Log::info("User {$user->id} deleted and anonymized");
        });
    }

    // --- ACTIONS (BULK, NOTIFICATIONS, PAYMENTS) ---

    public function processBulkAction(array $userIds, string $action, ?array $data, User $admin): array
    {
        $count = count($userIds);
        $users = User::whereIn('id', $userIds)->get();

        if ($action === 'bonus') {
            $amount = $data['amount'] ?? 0;
            if ($amount <= 0) throw new \InvalidArgumentException('Invalid bonus amount');
            foreach ($users as $user) {
                $this->walletService->deposit($user, $amount, 'manual_bonus', 'Bulk Bonus Award', $admin);
            }
            return ['message' => "Bonus of $amount awarded to $count users."];
        }

        $status = match ($action) {
            'activate' => 'active',
            'suspend' => 'suspended',
            default => throw new \InvalidArgumentException('Invalid action type')
        };

        foreach($users as $user) {
            $user->update(['status' => $status]);
        }

        return ['message' => "$count users updated to $status."];
    }

    public function sendNotification(User $user, array $data)
    {
        $user->notify(new \App\Notifications\AdminMessage(
            $data['title'],
            $data['message'],
            $data['type'] ?? 'info',
            $data['url'] ?? null
        ));
        Log::info("Push notification sent to User {$user->id}");
    }

    public function forcePayment(User $user, int $subscriptionId, float $amount, string $reason, User $admin)
    {
        $subscription = Subscription::findOrFail($subscriptionId);
        if ($subscription->user_id !== $user->id) throw new \Exception('Subscription does not belong to this user');

        return DB::transaction(function () use ($user, $subscription, $amount, $reason, $admin) {
            $payment = Payment::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'status' => 'paid',
                'payment_method' => 'admin_manual',
                'payment_date' => now(),
                'is_on_time' => true,
                'notes' => "Manual payment by admin: {$reason}",
            ]);
            $subscription->increment('consecutive_payments_count');
            $this->logAction($user, 'admin_force_payment', "Manual payment: ₹{$amount}", $admin);
            return $payment;
        });
    }

    // --- IMPORT / EXPORT ---

    public function importUsersFromCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle); 
        $columnMap = [
            'username' => array_search('username', array_map('strtolower', $header)),
            'email' => array_search('email', array_map('strtolower', $header)),
            'mobile' => array_search('mobile', array_map('strtolower', $header)),
        ];

        $imported = 0; $skipped = 0; $errors = []; $lineNumber = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;
                if (empty(array_filter($row)) || count($row) < 3) { $skipped++; continue; }

                $username = trim($row[$columnMap['username']] ?? '');
                $email = trim($row[$columnMap['email']] ?? '');
                $mobile = trim($row[$columnMap['mobile']] ?? '');

                if (User::where('email', $email)->orWhere('mobile', $mobile)->exists()) {
                    $errors[] = "Line $lineNumber: Duplicate"; $skipped++; continue;
                }

                $this->createUser([
                    'username' => $username, 'email' => $email, 'mobile' => $mobile,
                    'password' => Str::random(12)
                ], 'user');
                
                // Password reset logic handled inside creation or separate call
                $imported++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        } finally {
            fclose($handle);
        }
        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => array_slice($errors, 0, 10)];
    }

    public function getExportQuery()
    {
        return User::role('user')->with('profile');
    }

    // --- HELPER ---
    private function logAction(User $user, string $action, string $desc, User $admin)
    {
        $user->activityLogs()->create([
            'action' => $action,
            'description' => $desc,
            'ip_address' => request()->ip(),
            'performed_by' => $admin->id
        ]);
    }
}