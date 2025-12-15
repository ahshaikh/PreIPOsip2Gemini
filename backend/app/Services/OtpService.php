<?php
// V-FINAL-1730-326

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * Generate a new OTP with security checks.
     */
    public function generate(User $user, string $type): ?Otp
    {
        // 1. Check Cooldown (e.g., 1 minute)
        $existingOtp = Otp::where('user_id', $user->id)
            ->where('type', $type)
            ->latest()
            ->first();

        if ($existingOtp && $existingOtp->last_sent_at) {
            $diff = $existingOtp->last_sent_at->diffInSeconds(now());
            if ($diff < 60) {
                // Cooldown active
                return null;
            }
        }

        // 2. Generate
        $code = (string) random_int(100000, 999999);
        
        // Invalidate old OTPs
        Otp::where('user_id', $user->id)->where('type', $type)->delete();

        return Otp::create([
            'user_id' => $user->id,
            'type' => $type,
            'otp_code' => $code,
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(),
            'attempts' => 0,
            'blocked' => false,
        ]);
    }

    /**
     * Verify the OTP.
     * Returns: true (success), false (failure), or throws Exception (blocked/expired).
     */
    public function verify(User $user, string $type, string $code)
    {
        $otp = Otp::where('user_id', $user->id)
            ->where('type', $type)
            ->first();

        // 1. Check Existence
        if (!$otp) {
            return false;
        }

        // 2. Check Blocked
        if ($otp->blocked || $otp->attempts >= 3) {
            throw new \Exception("Too many failed attempts. Please request a new OTP.");
        }

        // 3. Check Expiry
        if (now()->gt($otp->expires_at)) {
            throw new \Exception("OTP has expired.");
        }

        // 4. Check Code - V-SECURITY-FIX: Use hash_equals to prevent timing attacks
        if (hash_equals($otp->otp_code, $code)) {
            $otp->delete(); // Success! Verify complete.
            return true;
        }

        // 5. Handle Failure
        $otp->increment('attempts');
        if ($otp->attempts >= 3) {
            $otp->update(['blocked' => true]);
        }
        
        return false;
    }

    /**
     * Cleanup old expired OTPs.
     */
    public function cleanup()
    {
        Otp::where('expires_at', '<', now()->subHour())->delete();
    }
}