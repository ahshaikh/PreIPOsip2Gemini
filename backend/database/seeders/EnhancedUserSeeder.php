<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EnhancedUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedAdditionalTestUsers();
            $this->seedCompanyRepresentatives();
        });

        $this->command->info('✓ Enhanced test users seeded successfully');
    }

    /* ============================================================
     | ADDITIONAL TEST USERS (IDEMPOTENT)
     |============================================================*/
    private function seedAdditionalTestUsers(): void
    {
        $testUsers = [
            [
                'username' => 'testuser12',
                'email' => 'user2@preiposip.com',
                'mobile' => '9191919191',
                'first_name' => 'Test',
                'last_name' => 'Two',
                'referral_code' => 'USER002',
                'kyc_verified' => true,
            ],
            [
                'username' => 'testuser13',
                'email' => 'user3@preiposip.com',
                'mobile' => '9292929292',
                'first_name' => 'Test',
                'last_name' => 'Three',
                'referral_code' => 'USER003',
                'kyc_verified' => true,
            ],
            [
                'username' => 'testuser14',
                'email' => 'user4@preiposip.com',
                'mobile' => '9393939393',
                'first_name' => 'Test',
                'last_name' => 'Four',
                'referral_code' => 'USER004',
                'kyc_verified' => true,
            ],
            [
                'username' => 'testuser15',
                'email' => 'user5@preiposip.com',
                'mobile' => '9494949494',
                'first_name' => 'Test',
                'last_name' => 'Five',
                'referral_code' => 'USER005',
                'kyc_verified' => false,
            ],
        ];

        foreach ($testUsers as $data) {

            $kycVerified = $data['kyc_verified'];
            unset($data['kyc_verified']);

            /* ---------------------------
             | USER (updateOrCreate)
             ----------------------------*/
            $user = User::updateOrCreate(
                ['email' => $data['email']], // natural key
                [
                    'username' => $data['username'],
                    'mobile' => $data['mobile'],
                    'referral_code' => $data['referral_code'],
                    'password' => Hash::make('password'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'mobile_verified_at' => now(),
                ]
            );

            /* ---------------------------
             | ROLE (safe assign)
             ----------------------------*/
            if (! $user->hasRole('user')) {
                $user->assignRole('user');
            }

            /* ---------------------------
             | PROFILE (updateOrCreate)
             ----------------------------*/
            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                ]
            );

            /* ---------------------------
             | KYC (user_kyc is single source of truth)
             ----------------------------*/
            UserKyc::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => $kycVerified ? 'verified' : 'pending',
                    'verified_at' => $kycVerified ? now() : null,
                ]
            );

            /* ---------------------------
             | WALLET (updateOrCreate)
             ----------------------------*/
            Wallet::updateOrCreate(
                ['user_id' => $user->id],
                []
            );
        }

        $this->command->info('  ✓ Additional test users ensured');
    }

    /* ============================================================
     | COMPANY REPRESENTATIVES (IDEMPOTENT)
     |============================================================*/
    private function seedCompanyRepresentatives(): void
    {
        $companyUsers = [
            [
                'username' => 'companyrep11',
                'email' => 'company1@preiposip.com',
                'mobile' => '9595959595',
                'first_name' => 'Company',
                'last_name' => 'Rep One',
                'referral_code' => 'COMP001',
            ],
            [
                'username' => 'companyrep12',
                'email' => 'company2@preiposip.com',
                'mobile' => '9696969696',
                'first_name' => 'Company',
                'last_name' => 'Rep Two',
                'referral_code' => 'COMP002',
            ],
        ];

        foreach ($companyUsers as $data) {

            /* ---------------------------
             | USER
             ----------------------------*/
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'username' => $data['username'],
                    'mobile' => $data['mobile'],
                    'referral_code' => $data['referral_code'],
                    'password' => Hash::make('password'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'mobile_verified_at' => now(),
                ]
            );

            /* ---------------------------
             | ROLE
             ----------------------------*/
            if (Role::where('name', 'company')->exists()) {
                if (! $user->hasRole('company')) {
                    $user->assignRole('company');
                }
            } else {
                if (! $user->hasRole('user')) {
                    $user->assignRole('user');
                }
            }

            /* ---------------------------
             | PROFILE
             ----------------------------*/
            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                ]
            );

            /* ---------------------------
             | KYC (user_kyc is single source of truth)
             ----------------------------*/
            UserKyc::updateOrCreate(
                ['user_id' => $user->id],
                ['status' => 'pending']
            );

            /* ---------------------------
             | WALLET
             ----------------------------*/
            Wallet::updateOrCreate(
                ['user_id' => $user->id],
                []
            );
        }

        $this->command->info('  ✓ Company representatives ensured');
    }
}
