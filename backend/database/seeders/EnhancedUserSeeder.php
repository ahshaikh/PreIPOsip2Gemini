<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Enhanced User Seeder
 *
 * Creates additional test users for comprehensive testing:
 * - 4 more verified test users (total 7 test users with existing UserSeeder)
 * - 2 company representative users
 * - All with KYC, profiles, and wallets
 *
 * Note: Run this AFTER UserSeeder.php to add more test users
 */
class EnhancedUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedAdditionalTestUsers();
            $this->seedCompanyRepresentatives();
        });

        $this->command->info('✓ Enhanced test users seeded successfully');
    }

    /**
     * Seed 4 additional verified test users
     */
    private function seedAdditionalTestUsers(): void
    {
        $testUsers = [
            [
                'username' => 'testuser2',
                'email' => 'user2@preiposip.com',
                'mobile' => '9191919191',
                'first_name' => 'Test User',
                'last_name' => 'Two',
                'referral_code' => 'USER002',
                'kyc_verified' => true,
            ],
            [
                'username' => 'testuser3',
                'email' => 'user3@preiposip.com',
                'mobile' => '9292929292',
                'first_name' => 'Test User',
                'last_name' => 'Three',
                'referral_code' => 'USER003',
                'kyc_verified' => true,
            ],
            [
                'username' => 'testuser4',
                'email' => 'user4@preiposip.com',
                'mobile' => '9393939393',
                'first_name' => 'Test User',
                'last_name' => 'Four',
                'referral_code' => 'USER004',
                'kyc_verified' => true,
            ],
            [
                'username' => 'testuser5',
                'email' => 'user5@preiposip.com',
                'mobile' => '9494949494',
                'first_name' => 'Test User',
                'last_name' => 'Five',
                'referral_code' => 'USER005',
                'kyc_verified' => false, // This one is pending KYC
            ],
        ];

        foreach ($testUsers as $userData) {
            $kycVerified = $userData['kyc_verified'];
            unset($userData['kyc_verified'], $userData['first_name'], $userData['last_name']);

            // Check if user already exists
            if (User::where('email', $userData['email'])->exists()) {
                continue;
            }

            $user = User::create(array_merge($userData, [
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
            ]));

            $user->assignRole('user');

            // Create profile
            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => explode('@', $user->email)[0],
                'last_name' => 'User',
            ]);

            // Create KYC
            UserKyc::create([
                'user_id' => $user->id,
                'status' => $kycVerified ? 'verified' : 'pending',
                'verified_at' => $kycVerified ? now() : null,
            ]);

            // Create wallet
            Wallet::create(['user_id' => $user->id]);
        }

        $this->command->info('  ✓ Additional test users seeded: 4 users');
    }

    /**
     * Seed company representative users
     */
    private function seedCompanyRepresentatives(): void
    {
        $companyUsers = [
            [
                'username' => 'companyrep1',
                'email' => 'company1@preiposip.com',
                'mobile' => '9595959595',
                'first_name' => 'Company',
                'last_name' => 'Rep One',
                'referral_code' => 'COMP001',
            ],
            [
                'username' => 'companyrep2',
                'email' => 'company2@preiposip.com',
                'mobile' => '9696969696',
                'first_name' => 'Company',
                'last_name' => 'Rep Two',
                'referral_code' => 'COMP002',
            ],
        ];

        foreach ($companyUsers as $userData) {
            unset($userData['first_name'], $userData['last_name']);

            // Check if user already exists
            if (User::where('email', $userData['email'])->exists()) {
                continue;
            }

            $user = User::create(array_merge($userData, [
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
            ]));

            // Assign company role if it exists, otherwise assign user role
            if (\Spatie\Permission\Models\Role::where('name', 'company')->exists()) {
                $user->assignRole('company');
            } else {
                $user->assignRole('user');
            }

            // Create profile
            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => explode('@', $user->email)[0],
                'last_name' => 'Representative',
            ]);

            // Create pending KYC
            UserKyc::create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            // Create wallet
            Wallet::create(['user_id' => $user->id]);
        }

        $this->command->info('  ✓ Company representatives seeded: 2 users');
    }
}
