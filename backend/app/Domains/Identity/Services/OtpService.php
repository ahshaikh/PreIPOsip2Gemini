<?php

namespace App\Domains\Identity\Services;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * Generate and store an OTP for a user.
     */
    public function generate(User $user, string $type): string
    {
        // Invalidate previous OTPs for this type
        $user->otps()->where('type', $type)->delete();

        // Generate a secure random 6-digit code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        Otp::create([
            'user_id' => $user->id,
            'type' => $type,
            'otp_code' => $code, // In strict production, this should be hashed
            'expires_at' => now()->addMinutes(10),
        ]);

        return $code;
    }

    /**
     * Verify an OTP with strict Rate Limiting.
     * Prevents brute-force attacks on specific User IDs.
     */
    public function verify(User $user, string $type, string $code): bool
    {
        $throttleKey = "otp_verify_attempts:{$user->id}:{$type}";
        
        // 1. Check Rate Limit (e.g., 5 attempts per 10 minutes per user)
        if (Cache::get($throttleKey, 0) >= 5) {
            throw new \Exception('Too many verification attempts. Please wait 10 minutes.');
        }

        // 2. Fetch Valid OTP from Database
        $otpRecord = $user->otps()
            ->where('type', $type)
            ->where('expires_at', '>', now())
            ->first();

        // 3. Validate Code
        if (!$otpRecord || $otpRecord->otp_code !== $code) {
            // Increment failure count
            Cache::increment($throttleKey);
            // Set expiry for the lock if it's new
            if (Cache::get($throttleKey) === 1) {
                Cache::put($throttleKey, 1, now()->addMinutes(10));
            }
            return false;
        }

        // 4. Cleanup on success
        $otpRecord->delete();
        Cache::forget($throttleKey);

        return true;
    }
}