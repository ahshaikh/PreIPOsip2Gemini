<?php
// V-POLISH-1730-179 (Created) | V-FINAL-1730-437 (Password History Added) | V-FINAL-1730-444 (SEC-9 Hardened)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\PasswordHistory; // <-- IMPORT
use Illuminate\Support\Facades\DB;

class SecurityController extends Controller
{
    public function updatePassword(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        
        $newPassword = $validated['password'];

        // --- FSD-SEC-008: Password History Check ---
        $historyLimit = (int) setting('password_history_limit', 5);

        // 1. Fetch recent password hashes
        $recentPasswords = PasswordHistory::where('user_id', $user->id)
            ->latest()
            ->limit($historyLimit)
            ->get();
            
        // 2. Check new password against each hash
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
        // --- END CHECK ---

        DB::transaction(function () use ($user, $newPassword) {
            // 1. Update the user's password
            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            // 2. Log the new password to history
            PasswordHistory::create([
                'user_id' => $user->id,
                'password_hash' => $user->password // This is the new, already-hashed password
            ]);
        });

        return response()->json(['message' => 'Password updated successfully.']);
    }
}