<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use App\Services\Kyc\KycStatusService;
use App\Enums\KycStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestDataSetSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedUsers();
        });

        $this->command->info('✓ Test dataset seeded successfully');
    }

    private function seedUsers(): void
    {
        $users = [
            [
                'username' => 'testuser11',
                'email' => 'user11@preiposip.com',
                'mobile' => '9090909091',
                'first_name' => 'Test',
                'last_name' => 'One',
                'referral_code' => 'USER011',
                'role' => 'user',
                'kyc_status' => KycStatus::VERIFIED,
            ],
            [
                'username' => 'testuser12',
                'email' => 'user12@preiposip.com',
                'mobile' => '9191919192',
                'first_name' => 'Test',
                'last_name' => 'Two',
                'referral_code' => 'USER0012',
                'role' => 'user',
                'kyc_status' => KycStatus::VERIFIED,
            ],
            [
                'username' => 'companyrep11',
                'email' => 'company11@preiposip.com',
                'mobile' => '9292929293',
                'first_name' => 'Company',
                'last_name' => 'Rep',
                'referral_code' => 'COMP0011',
                'role' => 'company',
                'kyc_status' => KycStatus::PENDING,
            ],
        ];

        foreach ($users as $data) {

            $user = $this->upsertUser($data);

            $this->assignRoleSafely($user, $data['role']);

            $this->upsertProfile($user, $data);

            $this->ensureWalletExists($user);

            $this->ensureKycStatus($user->id, $data['kyc_status']);
        }
    }

    private function upsertUser(array $data): User
    {
        $existing = User::where('email', $data['email'])
            ->orWhere('username', $data['username'])
            ->orWhere('mobile', $data['mobile'])
            ->first();

        if ($existing) {
            $existing->update([
                'email' => $data['email'],
                'username' => $data['username'],
                'mobile' => $data['mobile'],
                'referral_code' => $data['referral_code'],
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
            ]);

            return $existing;
        }

        return User::create([
            'email' => $data['email'],
            'username' => $data['username'],
            'mobile' => $data['mobile'],
            'referral_code' => $data['referral_code'],
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
    }

    private function assignRoleSafely(User $user, string $roleName): void
    {
        if (!Role::where('name', $roleName)->exists()) {
            return;
        }

        if (!$user->hasRole($roleName)) {
            $user->assignRole($roleName);
        }
    }

    private function upsertProfile(User $user, array $data): void
    {
        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
            ]
        );
    }

    private function ensureWalletExists(User $user): void
    {
        Wallet::updateOrCreate(
            ['user_id' => $user->id],
            []
        );
    }

    /**
     * Walk legal KYC transition path.
     * NO metadata fields passed.
     */
    private function ensureKycStatus(int $userId, KycStatus $targetStatus): void
    {
        $service = app(KycStatusService::class);

        $kyc = UserKyc::firstOrCreate(
            ['user_id' => $userId],
            ['status' => KycStatus::PENDING]
        );

        if ($kyc->status === $targetStatus) {
            return;
        }

        $transitionPath = [
            KycStatus::PENDING,
            KycStatus::SUBMITTED,
            KycStatus::PROCESSING,
            KycStatus::VERIFIED,
        ];

        $currentIndex = array_search($kyc->status, $transitionPath, true);
        $targetIndex  = array_search($targetStatus, $transitionPath, true);

        if ($currentIndex === false || $targetIndex === false) {
            return;
        }

        for ($i = $currentIndex + 1; $i <= $targetIndex; $i++) {
            $service->transitionTo($kyc, $transitionPath[$i]);
        }
    }
}
