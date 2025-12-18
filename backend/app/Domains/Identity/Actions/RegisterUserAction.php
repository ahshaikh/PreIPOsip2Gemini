<?php

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\Enums\UserStatus;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use App\Models\Setting;
use App\Jobs\SendOtpJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RegisterUserAction
{
    public function execute(array $data): User
    {
        // 1. Check Registration Setting (Cached for performance)
        $registrationEnabled = Cache::remember('setting_registration_enabled', 600, function () {
            return Setting::where('key', 'registration_enabled')->value('value') ?? 'true';
        });

        if ($registrationEnabled === 'false') {
            throw new \Exception('Registrations are currently closed.', 403);
        }

        return DB::transaction(function () use ($data) {
            // 2. Create User Core Record
            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'mobile' => $data['mobile'],
                'password' => Hash::make($data['password']),
                'status' => UserStatus::PENDING->value,
            ]);

            // 3. Initialize Domain Entities (Wallet, Profile, KYC)
            UserProfile::create(['user_id' => $user->id]);
            UserKyc::create(['user_id' => $user->id, 'status' => 'pending']); // Default KYC status
            Wallet::create(['user_id' => $user->id, 'balance' => 0, 'locked_balance' => 0]);
            
            // 4. Assign Default Role
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('user');
            }

            // 5. Handle Referrals
            if (!empty($data['referral_code'])) {
                $this->processReferral($user, $data['referral_code']);
            }

            // 6. Dispatch OTPs for Verification
            SendOtpJob::dispatch($user, 'email');
            SendOtpJob::dispatch($user, 'mobile');

            return $user;
        });
    }

    private function processReferral(User $user, string $code): void
    {
        $referralEnabled = Cache::remember('setting_referral_enabled', 600, function () {
            return Setting::where('key', 'referral_enabled')->value('value') ?? 'true';
        });

        if ($referralEnabled === 'true') {
            $referrer = User::where('referral_code', $code)->first();
            if ($referrer) {
                $user->update(['referred_by' => $referrer->id]);
            }
        }
    }
}