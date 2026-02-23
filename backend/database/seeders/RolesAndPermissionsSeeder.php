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
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

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
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // ======================================================
        // 4. Assign permissions per role
        // ======================================================
        $roleInstances['admin']->givePermissionTo([
            'access admin panel',
            'manage users',
            'manage kyc',
            'manage plans',
            'compliance.view_legal', // V-WAVE1-FIX: Added for dispute routes
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
