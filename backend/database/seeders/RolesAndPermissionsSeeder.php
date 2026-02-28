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

        foreach ($roles as $roleName) {
            $roleInstances[$roleName] = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
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
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // ======================================================
        // 4. Assign permissions per role
        // V-WAVE3-FIX: Use syncPermissions to ensure fresh assignment in tests
        // ======================================================
        $roleInstances['admin']->syncPermissions([
            'access admin panel',
            'manage users',
            'manage kyc',
            'manage plans',
            'compliance.view_legal', // V-WAVE1-FIX: Added for dispute routes
            'payments.refund', // V-WAVE3-FIX: Added for refund routes
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
        ]);

        $roleInstances['kyc-officer']->givePermissionTo([
            'access admin panel',
            'manage kyc',
        ]);

        $roleInstances['finance-manager']->givePermissionTo([
            'access admin panel',
            'manage plans',
        ]);

        $roleInstances['finance']->givePermissionTo([
            'access admin panel',
            'manage plans',
        ]);

        $roleInstances['support']->givePermissionTo([
            'access admin panel',
            'manage users',
            'manage kyc',
            'users.edit',
        ]);

        $roleInstances['company']->givePermissionTo([
            'access admin panel',
            'manage plans',
        ]);

        // ======================================================
        // 5. Super-admin gets everything
        // ======================================================
        $roleInstances['super-admin']->syncPermissions(Permission::all());
    }
}
