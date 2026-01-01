<?php
// V-PHASE1-1730-017 (Created) | V-REMEDIATE-1730-177 | V-FINAL-1730-588 (Avatar & KYC)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Services\FileUploadService; // <-- IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    protected $fileUploader;
    
    public function __construct(FileUploadService $fileUploader)
    {
        $this->fileUploader = $fileUploader;
    }

    /**
     * Display the authenticated user's complete profile.
     * Test: testGetProfileReturnsUserData
     * Test: testProfileIncludesKycStatus
     *
     * V-FIX-AVATAR-DISPLAY: Ensure profile exists before returning
     * V-FIX-PROFILE-VISIBILITY: Include bank details from KYC for frontend display
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // V-FIX-AVATAR-DISPLAY: Create profile if it doesn't exist
        if (!$user->profile) {
            $user->profile()->create([
                'first_name' => $user->username ?? 'User',
                'last_name' => '',
            ]);
            Log::info("Created missing profile for user {$user->id}");
        }

        // Eager load the profile, kyc, subscription status, and roles for proper admin detection
        $user->load('profile', 'kyc', 'subscription', 'roles');

        // V-FIX-PROFILE-VISIBILITY: Add bank details from KYC for frontend consumption
        // Frontend expects user.bank_details object with account info
        $userData = $user->toArray();
        $userData['bank_details'] = null;

        if ($user->kyc) {
            $userData['bank_details'] = [
                'account_number' => $user->kyc->bank_account,
                'ifsc_code' => $user->kyc->bank_ifsc,
                'bank_name' => $user->kyc->bank_name,
                'branch_name' => null, // KYC doesn't store branch name
                'account_holder_name' => $user->profile
                    ? trim(($user->profile->first_name ?? '') . ' ' . ($user->profile->last_name ?? ''))
                    : $user->username,
            ];
        }

        return response()->json($userData);
    }

    /**
     * Update the authenticated user's profile.
     * Test: testUpdateProfileWithValidData
     * Test: testProfileUpdateValidation
     *
     * V-FIX-AVATAR-DISPLAY: Ensure profile exists before updating
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        try {
            // V-FIX-AVATAR-DISPLAY: Create profile if it doesn't exist
            if (!$user->profile) {
                $user->profile()->create($request->validated());
                Log::info("Created profile during update for user {$user->id}");
            } else {
                $user->profile->update($request->validated());
            }

            return response()->json($user->fresh()->profile);
        } catch (\Exception $e) {
            Log::error("Profile update failed for user {$user->id}: " . $e->getMessage());
            return response()->json(['message' => 'Profile update failed.'], 500);
        }
    }

    /**
     * NEW: Update the authenticated user's avatar.
     * Test: testUploadAvatarSucceeds
     *
     * V-FIX-AVATAR-DISPLAY: Complete rewrite to fix avatar display issues
     * - Ensure profile exists
     * - Use relative URLs instead of absolute
     * - Delete old avatar files
     * - Add comprehensive logging
     * - Return fresh user data
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|file|image|mimes:jpg,jpeg,png|max:2048', // 2MB Max
        ]);

        $user = $request->user();

        try {
            // V-FIX-AVATAR-DISPLAY: Create profile if it doesn't exist
            if (!$user->profile) {
                $user->profile()->create([
                    'first_name' => $user->username ?? 'User',
                    'last_name' => '',
                ]);
                Log::info("Created missing profile during avatar upload for user {$user->id}");
            }

            // Delete old avatar if exists
            if ($user->profile->avatar_url) {
                // Extract path from URL (e.g., /storage/avatars/1/file.jpg -> avatars/1/file.jpg)
                $oldPath = str_replace('/storage/', '', $user->profile->avatar_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                    Log::info("Deleted old avatar for user {$user->id}: {$oldPath}");
                }
            }

            // Use the secure FileUploadService
            $path = $this->fileUploader->upload($request->file('avatar'), [
                'disk' => 'public',
                'path' => "avatars/{$user->id}",
                'encrypt' => false,
                'virus_scan' => true
            ]);

            // V-FIX-AVATAR-DISPLAY: Use RELATIVE URL, not absolute
            // FileUploadService returns path like: avatars/1/filename.jpg
            // We want URL like: /storage/avatars/1/filename.jpg
            $avatarUrl = '/storage/' . $path;

            // Verify file actually exists
            if (!Storage::disk('public')->exists($path)) {
                Log::error("Avatar file not found after upload for user {$user->id}: {$path}");
                throw new \Exception("File upload verification failed");
            }

            // Save the new URL
            $user->profile->update(['avatar_url' => $avatarUrl]);

            Log::info("Avatar updated successfully for user {$user->id}: {$avatarUrl}");

            // V-FIX-AVATAR-DISPLAY: Return fresh user data so frontend React Query updates
            $user->refresh();
            $user->load('profile', 'kyc', 'subscription', 'roles');

            return response()->json([
                'message' => 'Avatar updated successfully',
                'avatar_url' => $avatarUrl,
                'user' => $user, // Return complete user object for React Query cache update
            ]);

        } catch (\Exception $e) {
            Log::error("Avatar upload failed for user {$user->id}: " . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get user's bank details from KYC
     *
     * V-FIX-AVATAR-DISPLAY: Check profile exists before accessing
     */
    public function getBankDetails(Request $request)
    {
        $user = $request->user();
        $kyc = $user->kyc;

        if (!$kyc) {
            return response()->json([
                'bank_account' => null,
                'bank_ifsc' => null,
                'account_holder_name' => null,
            ]);
        }

        // V-FIX-AVATAR-DISPLAY: Safe profile access
        $accountHolderName = $user->username ?? 'User';
        if ($user->profile) {
            $accountHolderName = trim(($user->profile->first_name ?? '') . ' ' . ($user->profile->last_name ?? '')) ?: $user->username;
        }

        return response()->json([
            'bank_account' => $kyc->bank_account,
            'bank_ifsc' => $kyc->bank_ifsc,
            'account_holder_name' => $accountHolderName,
        ]);
    }

    /**
     * Update user's bank details in KYC
     *
     * V-FIX-BANK-DETAILS: Frontend sends account_number/ifsc_code/bank_name
     * Backend KYC table uses bank_account/bank_ifsc/bank_name
     * Need to map field names correctly
     */
    public function updateBankDetails(Request $request)
    {
        // V-FIX-BANK-DETAILS: Accept frontend field names
        $validated = $request->validate([
            'account_number' => 'required|string|min:9|max:18',
            'ifsc_code' => 'required|string|size:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'bank_name' => 'nullable|string|max:100',
            'account_holder_name' => 'nullable|string|max:100',
        ]);

        $user = $request->user();

        // V-FIX-BANK-DETAILS: Map frontend field names to backend column names
        $kycData = [
            'bank_account' => $validated['account_number'],
            'bank_ifsc' => $validated['ifsc_code'],
            'bank_name' => $validated['bank_name'] ?? null,
        ];

        // Get or create KYC record
        $kyc = $user->kyc;
        if (!$kyc) {
            $kycData['user_id'] = $user->id;
            $kycData['status'] = \App\Enums\KycStatus::PENDING->value;
            $kyc = $user->kyc()->create($kycData);
        } else {
            $kyc->update($kycData);
        }

        // Reload for fresh data
        $kyc = $kyc->fresh();

        return response()->json([
            'message' => 'Bank details updated successfully',
            'bank_details' => [
                'account_number' => $kyc->bank_account,
                'ifsc_code' => $kyc->bank_ifsc,
                'bank_name' => $kyc->bank_name,
                'account_holder_name' => $user->profile
                    ? trim(($user->profile->first_name ?? '') . ' ' . ($user->profile->last_name ?? ''))
                    : $user->username,
            ],
        ]);
    }
}