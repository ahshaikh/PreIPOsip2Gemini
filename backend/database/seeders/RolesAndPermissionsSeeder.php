<?php
// V-PHASE1-1730-023
// NOTE:
// All system roles MUST be declared here.
// User seeders may NOT invent roles.
// Missing roles must be added to this seeder first.

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // ======================================================
        // 1. Declare canonical system roles
        // ======================================================
        $roles = [
            'super-admin',       // Full system control
            'admin',             // Operational admin
            'kyc-officer',       // KYC & compliance review
            'finance-manager',   // Finance approvals & reconciliation
            'finance',           // Finance execution
            'support',           // Customer support
            'company',           // Issuer-side role
            'user',              // End user / investor
        ];

        $roleInstances = [];
        $guards = ['web', 'sanctum'];

        foreach ($roles as $roleName) {
            foreach ($guards as $guard) {
                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => $guard,
                ]);
                
                if ($guard === 'web') {
                    $roleInstances[$roleName] = $role;
                }
            }
        }

        // ======================================================
        // 2. Sanity check (post-creation)
        // ======================================================
        foreach ($roles as $role) {
            if (!Role::where('name', $role)->where('guard_name', 'web')->exists()) {
                throw new \RuntimeException("Required role missing after creation: {$role}");
            }
        }

        // ======================================================
        // 3. Declare permissions
        // ======================================================
        $permissions = [
            'access admin panel',
            'manage users',
            'manage kyc',
            'manage plans',
            // V-WAVE1-FIX: Add compliance permissions used by dispute routes
            'compliance.view_legal',
            // V-WAVE3-FIX: Add payments permissions used by refund routes
            'payments.refund',
            // [FIX]: Settings permissions for tests
            'settings.view_system',
            'settings.edit_system',
            // [FIX]: Bonus and User management permissions
            'bonuses.manage_config',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.suspend',
            'users.block',
            'users.wallet',
            'users.adjust_wallet',
            // [FIX]: KYC permissions
            'kyc.view_queue',
            'kyc.approve',
            'kyc.reject',
            // [FIX]: Plan permissions
            'plans.edit',
            'reports.view_financial',
            'reports.view_user',
            'reports.view_compliance',
            'reports.export',
            'reports.manage_scheduled',
            'system.view_health',
            'system.view_logs',
            'settings.manage_cms',
            // [FIX]: Withdrawal permissions
            'withdrawals.view_queue',
            'withdrawals.approve',
            'withdrawals.complete',
            'withdrawals.reject',
            // [FIX]: Product permissions
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
        ];

        foreach ($permissions as $permissionName) {
            foreach ($guards as $guard) {
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => $guard,
                ]);
            }
        }

        // ======================================================
        // 4. Assign permissions per role
        // V-WAVE3-FIX: Use syncPermissions to ensure fresh assignment in tests
        // ======================================================
        foreach ($guards as $guard) {
            $adminRole = Role::where('name', 'admin')->where('guard_name', $guard)->first();
            if ($adminRole) {
                $adminRole->syncPermissions([
                    'access admin panel',
                    'manage users',
                    'manage kyc',
                    'manage plans',
                    'compliance.view_legal',
                    'payments.refund',
                    'settings.view_system',
                    'settings.edit_system',
                    'bonuses.manage_config',
                    'users.view',
                    'users.create',
                    'users.edit',
                    'users.delete',
                    'users.suspend',
                    'users.block',
                    'users.wallet',
                    'users.adjust_wallet',
                    'kyc.view_queue',
                    'kyc.approve',
                    'kyc.reject',
                    'plans.edit',
                    'reports.view_financial',
                    'reports.view_user',
                    'reports.view_compliance',
                    'reports.export',
                    'reports.manage_scheduled',
                    'system.view_health',
                    'system.view_logs',
                    'settings.manage_cms',
                    'withdrawals.view_queue',
                    'withdrawals.approve',
                    'withdrawals.complete',
                    'withdrawals.reject',
                    'products.view',
                    'products.create',
                    'products.edit',
                    'products.delete',
                ]);
            }

            $superAdminRole = Role::where('name', 'super-admin')->where('guard_name', $guard)->first();
            if ($superAdminRole) {
                $superAdminRole->syncPermissions(Permission::where('guard_name', $guard)->get());
            }

            $kycRole = Role::where('name', 'kyc-officer')->where('guard_name', $guard)->first();
            if ($kycRole) {
                $kycRole->givePermissionTo([
                    'access admin panel',
                    'manage kyc',
                ]);
            }

            $financeManagerRole = Role::where('name', 'finance-manager')->where('guard_name', $guard)->first();
            if ($financeManagerRole) {
                $financeManagerRole->givePermissionTo([
                    'access admin panel',
                    'manage plans',
                ]);
            }

            $financeRole = Role::where('name', 'finance')->where('guard_name', $guard)->first();
            if ($financeRole) {
                $financeRole->givePermissionTo([
                    'access admin panel',
                    'manage plans',
                ]);
            }

            $supportRole = Role::where('name', 'support')->where('guard_name', $guard)->first();
            if ($supportRole) {
                $supportRole->givePermissionTo([
                    'access admin panel',
                    'manage users',
                    'manage kyc',
                    'users.edit',
                ]);
            }

            $companyRole = Role::where('name', 'company')->where('guard_name', $guard)->first();
            if ($companyRole) {
                $companyRole->givePermissionTo([
                    'access admin panel',
                    'manage plans',
                ]);
            }
        }
    }
}
