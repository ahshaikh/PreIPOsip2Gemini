<?php
// V-PHASE1-1730-023

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

        // Define roles
        $roles = ['super-admin', 'admin', 'support', 'finance', 'user'];
        $roleInstances = [];

        foreach ($roles as $roleName) {
            $roleInstances[$roleName] = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        // Define permissions
        $permissions = [
            'access admin panel',
            'manage users',
            'manage kyc',
            'manage plans',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // Assign permissions to roles
        $roleInstances['admin']->givePermissionTo([
            'access admin panel',
            'manage users',
            'manage kyc',
            'manage plans',
        ]);

        $roleInstances['support']->givePermissionTo([
            'access admin panel',
            'manage users',
            'manage kyc',
        ]);

        // Super-admin gets all permissions
        $roleInstances['super-admin']->syncPermissions(Permission::all());
    }
}