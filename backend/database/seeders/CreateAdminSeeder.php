<?php
// V-FINAL-1730-640 (Fix Duplicate)

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;

class CreateAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'superadmin@preipo.com';
        $username = 'SuperAdmin';
        $mobile = '9999999999';
        $referralCode = 'ADMIN001';

        // 1. Find existing user by Email OR Username to prevent duplicates
        $admin = User::where('email', $email)
                    ->orWhere('username', $username)
                    ->first();

        if ($admin) {
            // Update the existing user to match our desired credentials
            $admin->update([
                'email' => $email,
                'username' => $username,
                'mobile' => $mobile,
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'referral_code' => $referralCode,
            ]);
        } else {
            // Clean up any potential collisions on unique fields (mobile/referral)
            User::where('mobile', $mobile)->delete();
            User::where('referral_code', $referralCode)->delete();

            // Create new user
            $admin = User::create([
                'email' => $email,
                'username' => $username,
                'mobile' => $mobile,
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'referral_code' => $referralCode,
            ]);
        }

        // 2. Assign Role
        $admin->syncRoles(['super-admin']); // Use syncRoles to ensure no duplicates

        // 3. Ensure Relations exist
        UserProfile::updateOrCreate(['user_id' => $admin->id], [
            'first_name' => 'Super',
            'last_name' => 'Admin'
        ]);
        
        UserKyc::updateOrCreate(['user_id' => $admin->id], [
            'status' => 'verified'
        ]);
        
        // Only create wallet if missing (preserve balance if exists)
        Wallet::firstOrCreate(['user_id' => $admin->id], [
            'balance' => 1000000
        ]);

        $this->command->info('Admin User Configured Successfully!');
        $this->command->info("Email: $email");
        $this->command->info("Password: password");
    }
}