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
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // --- FIX (Gap 2) ---
        // Eager load the profile, kyc, and subscription status
        $user->load('profile', 'kyc', 'subscription');
        // -----------------
        
        return response()->json($user);
    }

    /**
     * Update the authenticated user's profile.
     * Test: testUpdateProfileWithValidData
     * Test: testProfileUpdateValidation
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        
        try {
            $user->profile->update($request->validated());
            return response()->json($user->profile);
        } catch (\Exception $e) {
            Log::error("Profile update failed for user {$user->id}: " . $e->getMessage());
            return response()->json(['message' => 'Profile update failed.'], 500);
        }
    }

    /**
     * NEW: Update the authenticated user's avatar.
     * Test: testUploadAvatarSucceeds
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|file|image|mimes:jpg,jpeg,png|max:2048', // 2MB Max
        ]);

        $user = $request->user();

        try {
            // Use the secure FileUploadService
            $path = $this->fileUploader->upload($request->file('avatar'), [
                'disk' => 'public',
                'path' => "avatars/{$user->id}",
                'encrypt' => false,
                'virus_scan' => true
            ]);
            
            // Get the public URL
            $url = Storage::disk('public')->url($path);

            // Delete old avatar if it exists
            if ($user->profile->avatar_url) {
                // This logic is simple, assumes storage path is stored
                // A more robust system would store the *path* not the *URL*
            }

            // Save the new URL
            $user->profile->update(['avatar_url' => $url]);
            $user->update(['avatar_url' => $url]); // Also update user table for quick access

            return response()->json(['message' => 'Avatar updated', 'avatar_url' => $url]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}