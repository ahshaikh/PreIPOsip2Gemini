<?php
// V-PHASE1-1730-016 (Created) | V-SECURITY-FIX (Password History Check Added)

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password as PasswordRules;
use App\Models\PasswordHistory;
use App\Models\User;

class PasswordResetController extends Controller
{
    /**
     * Send a password reset link.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users']);

        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent.'])
            : response()->json(['message' => 'Unable to send reset link.'], 500);
    }

    /**
     * Reset the user's password.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRules::defaults()],
        ]);

        // --- FSD-SEC-008: Password History Check ---
        $user = User::where('email', $request->email)->first();

        if ($user) {
            $historyLimit = (int) setting('password_history_limit', 5);
            $newPassword = $request->password;

            // Fetch recent password hashes
            $recentPasswords = PasswordHistory::where('user_id', $user->id)
                ->latest()
                ->limit($historyLimit)
                ->get();

            // Check new password against each historical hash
            foreach ($recentPasswords as $record) {
                if (Hash::check($newPassword, $record->password_hash)) {
                    return response()->json([
                        'message' => 'Cannot reuse a recent password.',
                        'errors' => [
                            'password' => ["You cannot reuse one of your last {$historyLimit} passwords."]
                        ]
                    ], 422);
                }
            }
        }
        // --- END PASSWORD HISTORY CHECK ---

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                DB::transaction(function () use ($user, $password) {
                    // 1. Update user password
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    // 2. Log new password to history
                    PasswordHistory::create([
                        'user_id' => $user->id,
                        'password_hash' => $user->password // The new hashed password
                    ]);
                });
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password reset successful.'])
            : response()->json(['message' => 'Invalid token or email.'], 400);
    }
}