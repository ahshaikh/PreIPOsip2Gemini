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
        
        // 3. Create ZIP
        $zipFileName = 'export_' . $user->id . '_' . time() . '.zip';
        $zipPath = storage_path('app/public/' . $zipFileName);
        
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

        // 4. Return Download
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    /**
     * FSD-LEGAL-007: User Data Deletion (Right to be Forgotten)
     */
    public function deleteAccount(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = $request->user();

        // 1. Business Rule Checks
        if ($user->wallet && $user->wallet->balance > 10) {
            return response()->json(['message' => 'Cannot delete account. You have a remaining wallet balance. Please withdraw funds first.'], 403);
        }

        if ($user->subscription && $user->subscription->status === 'active') {
             return response()->json(['message' => 'Cannot delete account. You have an active subscription. Please cancel it first.'], 403);
        }

        // FIX: Module 19 - Harden Account Deletion (Medium)
        // Prevent deletion if financial actions are pending
        
        // Check for Pending Withdrawals
        $hasPendingWithdrawals = Withdrawal::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
            
        if ($hasPendingWithdrawals) {
            return response()->json(['message' => 'Cannot delete account. You have pending withdrawal requests.'], 403);
        }

        // Check for Pending Investments (Shares not yet allocated)
        $hasPendingInvestments = UserInvestment::where('user_id', $user->id)
            ->where('status', 'pending') // Assuming 'pending' status exists for unallocated
            ->exists();

        if ($hasPendingInvestments) {
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

        DB::beginTransaction();
        try {
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

            DB::commit();

            return response()->json(['message' => 'Account deleted successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting account: ' . $e->getMessage()], 500);
        }
    }
}