<?php
// V-DEPLOY-1730-004
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates a Super Admin, a normal Admin, and a regular test user.
     */
    public function run(): void
    {
        // 1. Super Admin User
        $superAdmin = User::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'email' => 'superadmin@preiposip.com',
                'mobile' => '9999999999', // Assign this unique mobile to superadmin
                'password' => Hash::make('password'),
                'referral_code' => 'ADMIN001',
                'status' => 'active',
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('super-admin');
        UserProfile::updateOrCreate(['user_id' => $superAdmin->id], ['first_name' => 'Super', 'last_name' => 'Admin']);
        UserKyc::updateOrCreate(['user_id' => $superAdmin->id], ['status' => 'verified', 'verified_at' => now()]);
        Wallet::updateOrCreate(['user_id' => $superAdmin->id]);

        // 2. Regular Admin User
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@preiposip.com',
                'mobile' => '9898989898', // Ensure this is a different unique mobile
                'password' => Hash::make('password'),
                'referral_code' => 'ADMIN002',
                'status' => 'active',
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');
        UserProfile::updateOrCreate(['user_id' => $admin->id], ['first_name' => 'Admin', 'last_name' => 'User']);
        UserKyc::updateOrCreate(['user_id' => $admin->id], ['status' => 'verified', 'verified_at' => now()]);
        Wallet::updateOrCreate(['user_id' => $admin->id]);
        
        // 3. Regular Test User
        $testUser = User::updateOrCreate(
            ['username' => 'testuser'],
            [
                'email' => 'user@preiposip.com',
                'mobile' => '9090909090',
                'password' => Hash::make('password'),
                'referral_code' => 'USER001',
                'status' => 'active',
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
            ]
        );
        $testUser->assignRole('user');
        UserProfile::updateOrCreate(['user_id' => $testUser->id], ['first_name' => 'Test', 'last_name' => 'User']);
        // This user has NOT submitted KYC
        UserKyc::updateOrCreate(['user_id' => $testUser->id], ['status' => 'pending']);
        Wallet::updateOrCreate(['user_id' => $testUser->id]);
    }
}