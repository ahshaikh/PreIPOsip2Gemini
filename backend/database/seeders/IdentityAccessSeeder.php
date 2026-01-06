<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\KycDocument;
use App\Models\Wallet;
use App\Models\AdminLedgerEntry;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Identity & Access Seeder - Phase 2
 *
 * Seeds user-related data:
 * - Users (Admin, Test Users, Company Reps)
 * - User Profiles
 * - User KYC records
 * - KYC Documents
 * - Wallets (with genesis balances)
 * - Admin Ledger (genesis entries for initial liability)
 * - User Settings
 *
 * CRITICAL:
 * - Requires FoundationSeeder (roles, permissions)
 * - Creates admin ledger genesis entries
 * - Initializes user wallets with test balances
 */
class IdentityAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedAdminUsers();
            $this->seedTestUsers();
            $this->seedUserProfiles();
            $this->seedUserKyc();
            $this->seedWallets();
            $this->seedAdminLedgerGenesis();
            $this->seedUserSettings();
        });

        $this->command->info('✅ Identity & Access data seeded successfully');
    }

    /**
     * Seed admin users
     */
    private function seedAdminUsers(): void
    {
        $admins = [
            [
                'email' => 'admin@preiposip.com',
                'username' => 'superadmin',
                'mobile' => '+919876543210',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'status' => 'active',
                'referral_code' => 'ADMIN001',
                'role' => 'Super Admin',
            ],
            [
                'email' => 'support@preiposip.com',
                'username' => 'supportmanager',
                'mobile' => '+919876543211',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'status' => 'active',
                'referral_code' => 'SUPPORT01',
                'role' => 'Support Agent',
            ],
            [
                'email' => 'kyc@preiposip.com',
                'username' => 'kycreviewer',
                'mobile' => '+919876543212',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'status' => 'active',
                'referral_code' => 'KYCREV01',
                'role' => 'KYC Reviewer',
            ],
        ];

        foreach ($admins as $adminData) {
            $role = $adminData['role'];
            unset($adminData['role']);

            $admin = User::updateOrCreate(
                ['username' => $adminData['username']],
                $adminData
            );

            $admin->assignRole($role);
        }

        $this->command->info('  ✓ Admin users seeded: ' . count($admins) . ' records');
    }

    /**
     * Seed test users
     */
    private function seedTestUsers(): void
    {
        $testUsers = [
            [
                'email' => 'user1@test.com',
                'username' => 'testuser1',
                'mobile' => '+919876540001',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'status' => 'active',
                'referral_code' => 'USER0001',
                '_kyc_status' => 'verified', // Temporary marker for KYC seeding
            ],
            [
                'email' => 'user2@test.com',
                'username' => 'testuser2',
                'mobile' => '+919876540002',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'status' => 'active',
                'referral_code' => 'USER0002',
                '_kyc_status' => 'verified',
            ],
            [
                'email' => 'user3@test.com',
                'username' => 'testuser3',
                'mobile' => '+919876540003',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'status' => 'active',
                'referral_code' => 'USER0003',
                '_kyc_status' => 'verified',
            ],
            [
                'email' => 'user4@test.com',
                'username' => 'testuser4',
                'mobile' => '+919876540004',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'status' => 'active',
                'referral_code' => 'USER0004',
                'referred_by' => 'USER0001', // Will be linked after creation
                '_kyc_status' => 'verified',
            ],
            [
                'email' => 'user5@test.com',
                'username' => 'testuser5',
                'mobile' => '+919876540005',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'status' => 'active',
                'referral_code' => 'USER0005',
                'referred_by' => 'USER0002', // Will be linked after creation
                '_kyc_status' => 'verified',
            ],
            [
                'email' => 'company1@example.com',
                'username' => 'companyrep1',
                'mobile' => '+919876550001',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'status' => 'active',
                'referral_code' => 'COMP0001',
                '_kyc_status' => 'pending',
            ],
            [
                'email' => 'company2@example.com',
                'username' => 'companyrep2',
                'mobile' => '+919876550002',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'status' => 'active',
                'referral_code' => 'COMP0002',
                '_kyc_status' => 'pending',
            ],
        ];

        foreach ($testUsers as $userData) {
            $referredByCode = $userData['referred_by'] ?? null;
            $kycStatus = $userData['_kyc_status'] ?? 'pending';
            unset($userData['referred_by'], $userData['_kyc_status']);

            $user = User::updateOrCreate(
                ['username' => $userData['username']],
                $userData
            );

            $user->assignRole('User');

            // Store KYC status temporarily for use in seedUserKyc
            $user->_temp_kyc_status = $kycStatus;

            // Link referral after all users are created
            if ($referredByCode) {
                $referrer = User::where('referral_code', $referredByCode)->first();
                if ($referrer) {
                    $user->update(['referred_by' => $referrer->id]);
                }
            }
        }

        $this->command->info('  ✓ Test users seeded: ' . count($testUsers) . ' records');
    }

    /**
     * Seed user profiles
     */
    private function seedUserProfiles(): void
    {
        $users = User::all();
        $count = 0;

        foreach ($users as $user) {
            if (UserProfile::where('user_id', $user->id)->exists()) {
                continue;
            }

            // Generate realistic profile data
            $dob = now()->subYears(rand(25, 55))->subDays(rand(1, 365));

            // Extract name from username for profiles
            $firstName = ucfirst(str_replace(['admin', 'manager', 'reviewer', 'testuser', 'companyrep'], '', $user->username));
            if (empty($firstName) || is_numeric($firstName)) {
                $firstName = 'User';
            }
            $lastName = 'Seeded';

            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'dob' => $dob,
                'gender' => rand(0, 1) ? 'male' : 'female',
                'address_line_1' => rand(1, 999) . ' Test Street',
                'address_line_2' => 'Near Test Landmark',
                'city' => collect(['Mumbai', 'Delhi', 'Bangalore', 'Pune', 'Hyderabad'])->random(),
                'state' => collect(['Maharashtra', 'Delhi', 'Karnataka', 'Telangana'])->random(),
                'pincode' => '400' . rand(100, 999),
                'country' => 'India',
            ]);

            $count++;
        }

        $this->command->info('  ✓ User profiles seeded: ' . $count . ' records');
    }

    /**
     * Seed user KYC records
     */
    private function seedUserKyc(): void
    {
        // Only seed KYC for regular users (not admins)
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', 'User');
        })->get();

        foreach ($users as $user) {
            if (UserKyc::where('user_id', $user->id)->exists()) {
                continue;
            }

            $profile = $user->profile;
            $status = $user->_temp_kyc_status ?? 'pending';

            // Get first and last name from profile
            $fullName = ($profile->first_name ?? 'User') . ' ' . ($profile->last_name ?? 'Seeded');

            UserKyc::create([
                'user_id' => $user->id,
                'pan_number' => 'ABCDE' . rand(1000, 9999) . 'F',
                'aadhaar_number' => str_repeat(rand(1, 9), 12),
                'bank_account' => rand(10000000, 99999999),
                'bank_ifsc' => strtoupper(Str::random(4)) . '0' . rand(100000, 999999),
                'status' => $status,
                'submitted_at' => $status !== 'pending' ? now()->subDays(rand(5, 30)) : null,
                'verified_at' => $status === 'verified' ? now()->subDays(rand(1, 5)) : null,
            ]);
        }

        $this->command->info('  ✓ User KYC seeded: ' . $users->count() . ' records');
    }

    /**
     * Seed wallets with test balances
     */
    private function seedWallets(): void
    {
        // Only seed wallets for regular users (verified KYC)
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', 'User');
        })->whereHas('kyc', function ($query) {
            $query->where('status', 'verified');
        })->get();

        // Test balances for users (in paise)
        $testBalances = [
            'user1@test.com' => 5000000,  // ₹50,000
            'user2@test.com' => 10000000, // ₹1,00,000
            'user3@test.com' => 2500000,  // ₹25,000
            'user4@test.com' => 0,        // ₹0
            'user5@test.com' => 0,        // ₹0
        ];

        foreach ($users as $user) {
            if (Wallet::where('user_id', $user->id)->exists()) {
                continue;
            }

            $balancePaise = $testBalances[$user->email] ?? 0;

            Wallet::create([
                'user_id' => $user->id,
                'balance_paise' => $balancePaise,
                'locked_balance' => 0,
            ]);
        }

        $this->command->info('  ✓ Wallets seeded: ' . $users->count() . ' records');
    }

    /**
     * Seed admin ledger genesis entries
     *
     * This creates the opening balance for the admin's liability account.
     * Total test wallet balances = ₹1,75,000 (₹50,000 + ₹1,00,000 + ₹25,000)
     *
     * Double-entry accounting:
     * Entry 1 (Debit): Initial Liability - ₹10,00,000
     * Entry 2 (Credit): Offsetting entry - ₹10,00,000
     */
    private function seedAdminLedgerGenesis(): void
    {
        // Check if genesis entries already exist
        if (AdminLedgerEntry::where('description', 'LIKE', '%GENESIS%')->exists()) {
            $this->command->info('  ⚠ Admin ledger genesis already exists, skipping');
            return;
        }

        // Calculate total test wallet balances
        $totalTestBalancesPaise = Wallet::sum('balance_paise');
        $totalTestBalances = $totalTestBalancesPaise / 100;

        // Round up to nearest lakh for clean accounting
        $genesisAmount = 1000000; // ₹10,00,000 (covers ₹1,75,000 test balances + buffer)

        // Create double-entry for genesis
        $debitEntry = AdminLedgerEntry::create([
            'entry_date' => now(),
            'entry_type' => 'debit',
            'category' => 'wallet_liability',
            'subcategory' => 'genesis',
            'amount_paise' => $genesisAmount * 100,
            'balance_after_paise' => $genesisAmount * 100,
            'description' => 'GENESIS: Initial Wallet Liability for Test Users',
            'reference_type' => null,
            'reference_id' => null,
            'metadata' => json_encode([
                'type' => 'genesis',
                'total_test_wallets_paise' => $totalTestBalancesPaise,
                'buffer_paise' => ($genesisAmount * 100) - $totalTestBalancesPaise,
            ]),
        ]);

        $creditEntry = AdminLedgerEntry::create([
            'entry_date' => now(),
            'entry_type' => 'credit',
            'category' => 'wallet_liability',
            'subcategory' => 'genesis',
            'amount_paise' => $genesisAmount * 100,
            'balance_after_paise' => 0, // Back to zero after offsetting
            'description' => 'GENESIS: Offsetting Entry for Initial Liability',
            'reference_type' => null,
            'reference_id' => null,
            'metadata' => json_encode([
                'type' => 'genesis_offset',
            ]),
            'entry_pair_id' => $debitEntry->id,
        ]);

        // Link the entries
        $debitEntry->update(['entry_pair_id' => $creditEntry->id]);

        $this->command->info('  ✓ Admin ledger genesis seeded: ₹' . number_format($genesisAmount, 2));
    }

    /**
     * Seed user settings
     */
    private function seedUserSettings(): void
    {
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', 'User');
        })->get();

        foreach ($users as $user) {
            if (UserSetting::where('user_id', $user->id)->exists()) {
                continue;
            }

            UserSetting::create([
                'user_id' => $user->id,
                'theme' => 'light',
                'language' => 'en',
                'timezone' => 'Asia/Kolkata',
                'email_notifications' => true,
                'sms_notifications' => true,
                'push_notifications' => true,
                'marketing_emails' => true,
                'two_factor_enabled' => false,
            ]);
        }

        $this->command->info('  ✓ User settings seeded: ' . $users->count() . ' records');
    }
}
