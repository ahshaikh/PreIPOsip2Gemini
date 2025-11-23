<?php
// V-FINAL-1730-469 (Created)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str; // V-FIX: Missing import
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Collection;

class TwoFactorAuthController extends Controller
{
    /**
     * Get the user's current 2FA status and (if setup) recovery codes.
     */
    public function status(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'is_enabled' => !is_null($user->two_factor_confirmed_at),
            'recovery_codes' => $user->two_factor_recovery_codes ?? [],
        ]);
    }

    /**
     * Start the 2FA setup process.
     * Generates a new secret and a QR code URL.
     */
    public function enable(Request $request)
    {
        $user = $request->user();
        $google2fa = app(Google2FA::class);
        
        // Generate a new secret
        $secret = $google2fa->generateSecretKey();
        
        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => null, // Not confirmed yet
        ]);
        
        return response()->json([
            'qr_code_url' => $user->getTwoFactorQrCodeUrl(),
            'secret_key' => $secret // For manual entry
        ]);
    }

    /**
     * Confirm and activate 2FA with a valid code.
     */
    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string|digits:6']);
        
        $user = $request->user();
        
        if (!$user->two_factor_secret) {
            return response()->json(['message' => '2FA setup not initiated.'], 400);
        }

        // Verify the code
        if (!$user->verifyTwoFactorCode($request->code)) {
            return response()->json(['message' => 'Invalid 2FA Code.'], 422);
        }

        // --- SUCCESS ---
        // 1. Mark as confirmed
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ]);
        
        // 2. Generate Recovery Codes
        $recoveryCodes = Collection::times(8, fn () => Str::random(10) . '-' . Str::random(10))->all();
        $user->forceFill([
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();

        return response()->json([
            'message' => '2FA Enabled Successfully!',
            'recovery_codes' => $recoveryCodes
        ]);
    }

    /**
     * Disable 2FA (requires current password).
     */
    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        
        $user = $request->user();
        
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json(['message' => '2FA Disabled Successfully.']);
    }
}