<?php

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Services\OtpService;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class VerifyOtpAction
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    public function execute(int $userId, string $type, string $code): array
    {
        $user = User::find($userId);

        if (!$user) {
            // Security: Don't reveal user existence, but fail generic
            throw ValidationException::withMessages(['otp' => 'Invalid request.']);
        }

        // Delegate to Service for secure verification (Rate Limiting handling)
        try {
            $isValid = $this->otpService->verify($user, $type, $code);
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['otp' => $e->getMessage()]);
        }

        if (!$isValid) {
            throw ValidationException::withMessages(['otp' => 'Invalid OTP code.']);
        }

        // Update User State
        $updateField = $type === 'email' ? 'email_verified_at' : 'mobile_verified_at';
        $user->update([$updateField => now()]);

        // Check for Activation
        $activated = false;
        $user->refresh();
        
        if ($user->email_verified_at && $user->mobile_verified_at && $user->status === UserStatus::PENDING->value) {
            $user->update(['status' => UserStatus::ACTIVE->value]);
            $activated = true;
        }

        return [
            'message' => ucfirst($type) . ' verified successfully.',
            'account_activated' => $activated
        ];
    }
}