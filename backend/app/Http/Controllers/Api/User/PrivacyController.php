<?php
// V-FINAL-1730-212 | V-FIX-MODULE-19 (Gemini)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Str;
use App\Models\UserInvestment;
use App\Models\Withdrawal;
use App\Models\PrivacyRequest; // Added

class PrivacyController extends Controller
{
    /**
     * V-AUDIT-MODULE17-HIGH: Fixed Export File Storage Security
     *
     * PROBLEM: ZIP files were stored in storage/app/public with predictable filenames (time()).
     * If script crashed before deleteFileAfterSend, file remained publicly accessible with
     * guessable URLs containing sensitive PII (KYC docs, transactions, etc.).
     *
     * SOLUTION: Store exports in private storage (storage/app/private) with random filenames.
     * Stream download from private storage - files are never publicly accessible.
     *
     * FSD-LEGAL-006: User Data Export
     * Generates a ZIP file containing user profile and transaction history.
     */
    public function export(Request $request)
    {
        $user = $request->user();

        // FIX: Module 19 - Request Tracking
        // Log the request initiation
        PrivacyRequest::create([
            'user_id' => $user->id,
            'type' => 'export',
            'status' => 'processing',
            'requested_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        // 1. Gather Data
        $data = [
            'profile' => $user->load('profile'),
            'kyc' => $user->kyc,
            'subscriptions' => $user->subscription()->with('plan')->get(),
            'wallet' => $user->wallet,
            'investments' => $user->investments()->with('product')->get(),
            'transactions' => $user->wallet->transactions()->latest()->get(),
            'bonuses' => $user->bonuses,
            'referrals' => $user->referrals,
            'activity_logs' => $user->activityLogs()->latest()->limit(100)->get(),
        ];

        // 2. Create JSON Content
        $jsonContent = json_encode($data, JSON_PRETTY_PRINT);

        // V-AUDIT-MODULE17-HIGH: Use private storage with random filename (not guessable)
        // Changed from storage/app/public (accessible via URL) to storage/app/private (server-only)
        // Changed from time() (predictable) to Str::random(40) (cryptographically secure)
        $zipFileName = 'exports/user_' . $user->id . '_' . Str::random(40) . '.zip';
        $zipPath = storage_path('app/private/' . $zipFileName);

        // V-AUDIT-MODULE17-HIGH: Ensure private directory exists
        $privateExportsDir = storage_path('app/private/exports');
        if (!is_dir($privateExportsDir)) {
            mkdir($privateExportsDir, 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            // Add JSON data
            $zip->addFromString('data.json', $jsonContent);
            $zip->close();
        } else {
            return response()->json(['message' => 'Failed to create export file.'], 500);
        }

        // Log completion
        PrivacyRequest::where('user_id', $user->id)
            ->where('type', 'export')
            ->where('status', 'processing')
            ->latest()
            ->first()
            ?->update(['status' => 'completed', 'completed_at' => now()]);

        // V-AUDIT-MODULE17-HIGH: Stream download from private storage
        // deleteFileAfterSend(true) ensures file is removed even if download completes successfully
        // If script crashes during ZIP creation (before this line), file is in private storage - not publicly accessible
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    /**
     * V-AUDIT-MODULE17-MEDIUM: Fixed Account Deletion Race Condition
     *
     * PROBLEM: Race condition existed between checking for pending withdrawals/investments
     * and actually deleting the account. A user could fire a "Withdraw" request milliseconds
     * after checks passed but before soft delete committed - resulting in orphaned withdrawal
     * requests or data integrity issues.
     *
     * SOLUTION: Acquire pessimistic lock (lockForUpdate) on user row and wallet BEFORE
     * performing any checks. This prevents concurrent modifications during the deletion process.
     *
     * FSD-LEGAL-007: User Data Deletion (Right to be Forgotten)
     */
    public function deleteAccount(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|current_password',
        ]);

        $userId = $request->user()->id;

        // V-AUDIT-MODULE17-MEDIUM: Start transaction and acquire locks FIRST
        // This prevents race conditions where user could initiate withdrawal/investment
        // between our checks and the actual deletion
        DB::beginTransaction();
        try {
            // V-AUDIT-MODULE17-MEDIUM: Lock the user row for update (pessimistic lock)
            // Any concurrent requests trying to modify this user will wait until our transaction completes
            $user = \App\Models\User::where('id', $userId)->lockForUpdate()->first();

            if (!$user) {
                DB::rollBack();
                return response()->json(['message' => 'User not found.'], 404);
            }

            // V-AUDIT-MODULE17-MEDIUM: Lock wallet if exists (prevent concurrent balance changes)
            if ($user->wallet) {
                $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->first();

                // 1. Business Rule Checks (Now safe from race conditions due to locks)
                if ($wallet && $wallet->balance > 10) {
                    DB::rollBack();
                    return response()->json(['message' => 'Cannot delete account. You have a remaining wallet balance. Please withdraw funds first.'], 403);
                }
            }

            if ($user->subscription && $user->subscription->status === 'active') {
                DB::rollBack();
                return response()->json(['message' => 'Cannot delete account. You have an active subscription. Please cancel it first.'], 403);
            }

            // FIX: Module 19 - Harden Account Deletion (Medium)
            // Prevent deletion if financial actions are pending

            // V-AUDIT-MODULE17-MEDIUM: Check for Pending Withdrawals (within locked transaction)
            $hasPendingWithdrawals = Withdrawal::where('user_id', $user->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingWithdrawals) {
                DB::rollBack();
                return response()->json(['message' => 'Cannot delete account. You have pending withdrawal requests.'], 403);
            }

            // V-AUDIT-MODULE17-MEDIUM: Check for Pending Investments (within locked transaction)
            $hasPendingInvestments = UserInvestment::where('user_id', $user->id)
                ->where('status', 'pending') // Assuming 'pending' status exists for unallocated
                ->exists();

            if ($hasPendingInvestments) {
                DB::rollBack();
                return response()->json(['message' => 'Cannot delete account. You have pending investments awaiting allocation.'], 403);
            }

            // Log the deletion request
            PrivacyRequest::create([
                'user_id' => $user->id,
                'type' => 'deletion',
                'status' => 'processing', // Will be set to completed after transaction
                'requested_at' => now(),
                'ip_address' => $request->ip(),
            ]);

            // 2. Anonymize PII (Soft Delete approach)
            $randomStr = Str::random(10);

            $user->update([
                'username' => 'deleted_user_' . $user->id,
                'email' => 'deleted_' . $user->id . '_' . $randomStr . '@preiposip.com',
                'mobile' => '0000000000',
                'password' => Hash::make(Str::random(32)),
                'status' => 'deleted',
            ]);

            if ($user->profile) {
                $user->profile->update([
                    'first_name' => 'Deleted',
                    'last_name' => 'User',
                    'address' => null,
                    'city' => null,
                    'state' => null,
                    'pincode' => null,
                    'avatar_url' => null,
                ]);
            }

            if ($user->kyc) {
                $user->kyc->update([
                    'pan_number' => null,
                    'aadhaar_number' => null,
                    'bank_account' => null,
                    'demat_account' => null,
                    'status' => 'deleted',
                ]);
            }

            // 3. Soft Delete
            $user->delete();

            // Mark request as completed
            PrivacyRequest::where('user_id', $user->id)
                ->where('type', 'deletion')
                ->where('status', 'processing')
                ->latest()
                ->first()
                ?->update(['status' => 'completed', 'completed_at' => now()]);

            // V-AUDIT-MODULE17-MEDIUM: Commit transaction - locks are released only after commit
            DB::commit();

            return response()->json(['message' => 'Account deleted successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting account: ' . $e->getMessage()], 500);
        }
    }
}