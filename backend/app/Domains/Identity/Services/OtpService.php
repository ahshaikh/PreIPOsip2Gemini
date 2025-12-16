<?php

namespace App\Domains\Identity\Services;

use App\Models\User;
use App\Models\Otp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * Generate and store an OTP for a user.
     */
    public function generate(User $user, string $type): string
    {
        // Invalidate previous OTPs
        $user->otps()->where('type', $type)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        Otp::create([
            'user_id' => $user->id,
            'type' => $type,
            'otp_code' => $code, // In prod, hash this: Hash::make($code)
            'expires_at' => now()->addMinutes(10),
        ]);

        return $code;
    }

    /**
     * Verify an OTP.
     * Includes Rate Limiting to prevent brute-forcing a specific User ID.
     */
    public function verify(User $user, string $type, string $code): bool
    {
        $throttleKey = "otp_verify_attempts:{$user->id}:{$type}";
        
        // 1. Check Rate Limit (5 attempts per 10 minutes per user)
        if (Cache::get($throttleKey, 0) >= 5) {
            throw new \Exception('Too many verification attempts. Please try again later.');
        }

        // 2. Fetch OTP
        $otpRecord = $user->otps()
            ->where('type', $type)
            ->where('expires_at', '>', now())
            ->first();

        // 3. Validate
        if (!$otpRecord || $otpRecord->otp_code !== $code) {
            Cache::increment($throttleKey);
            Cache::put($throttleKey, Cache::get($throttleKey), now()->addMinutes(10));
            return false;
        }

        // 4. Cleanup on success
        $otpRecord->delete();
        Cache::forget($throttleKey);

        return true;
    }
}