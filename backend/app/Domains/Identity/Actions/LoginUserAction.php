<?php

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\Enums\UserStatus;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    public function execute(User $user, string $ip): array
    {
        // 1. Check Global Login Setting
        $loginEnabled = Cache::remember('setting_login_enabled', 600, function () {
            return Setting::where('key', 'login_enabled')->value('value') ?? 'true';
        });

        if ($loginEnabled === 'false') {
            throw new \Exception('Login is currently disabled. Please contact support.', 503);
        }

        // 2. Validate User Status (Suspended/Banned check)
        $this->validateStatus($user);

        // 3. Check for 2FA Requirement
        if ($user->two_factor_confirmed_at) {
            return [
                'two_factor_required' => true,
                'user_id' => $user->id,
                'message' => 'Two-factor authentication required.',
            ];
        }

        // 4. Finalize Login & Issue Token
        return $this->issueToken($user, $ip);
    }

    private function validateStatus(User $user): void
    {
        if ($user->status === UserStatus::SUSPENDED->value) {
            throw ValidationException::withMessages(['email' => 'Your account has been suspended. Please contact support.']);
        }

        if ($user->status === UserStatus::BANNED->value) {
            throw ValidationException::withMessages(['email' => 'Your account has been permanently banned.']);
        }

        if ($user->status === UserStatus::PENDING->value) {
            // Returns a specific code 403 allowing frontend to redirect to verify page
            throw new \Exception('Please verify your email and mobile to activate your account.', 403);
        }
    }

    public function issueToken(User $user, string $ip): array
    {
        // Update Login Metadata
        $user->last_login_at = now();
        $user->last_login_ip = $ip;
        $user->save();

        // Issue Sanctum Token
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user->load('profile', 'kyc', 'roles:name'),
        ];
    }
}