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
                'branch_name' => $user->kyc->bank_branch, // V-FIX-BANK-BRANCH: Return bank_branch from KYC
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
            $validated = $request->validated();

            // V-FIX-PROFILE-ENHANCEMENT: Handle mobile separately (stored in users table)
            if ($request->has('mobile')) {
                // Validate mobile
                $request->validate([
                    'mobile' => 'sometimes|string|regex:/^[0-9]{10}$/',
                ]);

                // Update mobile on user model
                $user->mobile = $request->mobile;
                $user->save();

                // Remove mobile from validated data (not in user_profiles table)
                unset($validated['mobile']);
            }

            // V-FIX-AVATAR-DISPLAY: Create profile if it doesn't exist
            if (!$user->profile) {
                $user->profile()->create($validated);
                Log::info("Created profile during update for user {$user->id}");
            } else {
                $user->profile->update($validated);
            }

            // Return complete user data with profile
            $user->refresh();
            $user->load('profile', 'kyc', 'subscription', 'roles');

            return response()->json($user);
        } catch (\Exception $e) {
            Log::error("Profile update failed for user {$user->id}: " . $e->getMessage());
            return response()->json(['message' => 'Profile update failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * NEW: Update the authenticated user's avatar.
     * Test: testUploadAvatarSucceeds
     *
     * V-FIX-AVATAR-STORAGE: Bypass FileUploadService, use direct Laravel storage
     * - Save directly to public disk to avoid conflicts with KYC uploads
     * - Use simple file storage without encryption
     * - Ensure correct path and URL generation
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|file|image|mimes:jpg,jpeg,png|max:2048', // 2MB Max
        ]);

        $user = $request->user();

        try {
            // V-FIX-AVATAR-STORAGE: Create profile if it doesn't exist
            if (!$user->profile) {
                $user->profile()->create([
                    'first_name' => $user->username ?? 'User',
                    'last_name' => '',
                ]);
                Log::info("Created missing profile during avatar upload for user {$user->id}");
            }

            // Delete old avatar if exists
            if ($user->profile->avatar_url) {
                $oldPath = str_replace('/storage/', '', $user->profile->avatar_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                    Log::info("Deleted old avatar for user {$user->id}: {$oldPath}");
                }
            }

            // V-FIX-AVATAR-STORAGE: Use direct Laravel storage instead of FileUploadService
            // Store directly to public disk to avoid KYC upload confusion
            $file = $request->file('avatar');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = "avatars/{$user->id}/{$filename}";

            // Store file to public disk
            Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

            // Generate relative URL
            $avatarUrl = '/storage/' . $path;

            // Verify file was saved
            if (!Storage::disk('public')->exists($path)) {
                Log::error("Avatar file verification failed for user {$user->id}: {$path}");
                throw new \Exception("File upload verification failed");
            }

            Log::info("Avatar stored successfully", [
                'user_id' => $user->id,
                'path' => $path,
                'full_path' => Storage::disk('public')->path($path),
                'url' => $avatarUrl
            ]);

            // V-FIX-AVATAR-URL-NULL: Save the new URL and verify it saved
            $profile = $user->profile;
            $profile->avatar_url = $avatarUrl;
            $profile->save();

            // Verify the save worked
            $profile->refresh();
            if ($profile->avatar_url !== $avatarUrl) {
                Log::error("Avatar URL failed to save to database", [
                    'user_id' => $user->id,
                    'expected' => $avatarUrl,
                    'actual' => $profile->avatar_url
                ]);
                throw new \Exception("Failed to save avatar URL to database");
            }

            Log::info("Avatar URL saved to database successfully", [
                'user_id' => $user->id,
                'avatar_url' => $profile->avatar_url
            ]);

            // V-FIX-AVATAR-DISPLAY: Return fresh user data so frontend React Query updates
            $user->refresh();
            $user->load('profile', 'kyc', 'subscription', 'roles');

            return response()->json([
                'message' => 'Avatar updated successfully',
                'avatar_url' => $profile->avatar_url,
                'user' => $user,
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
        // V-FIX-BANK-BRANCH: Add branch_name validation
        $validated = $request->validate([
            'account_number' => 'required|string|min:9|max:18',
            'ifsc_code' => 'required|string|size:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'bank_name' => 'nullable|string|max:100',
            'branch_name' => 'nullable|string|max:100', // V-FIX-BANK-BRANCH
            'account_holder_name' => 'nullable|string|max:100',
        ]);

        $user = $request->user();

        // V-FIX-BANK-DETAILS: Map frontend field names to backend column names
        // V-FIX-BANK-BRANCH: Include bank_branch in mapping
        $kycData = [
            'bank_account' => $validated['account_number'],
            'bank_ifsc' => $validated['ifsc_code'],
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_branch' => $validated['branch_name'] ?? null, // V-FIX-BANK-BRANCH
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
                'branch_name' => $kyc->bank_branch, // V-FIX-BANK-BRANCH: Return branch_name in response
                'account_holder_name' => $user->profile
                    ? trim(($user->profile->first_name ?? '') . ' ' . ($user->profile->last_name ?? ''))
                    : $user->username,
            ],
        ]);
    }
}