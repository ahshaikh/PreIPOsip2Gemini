// V-PHASE1-1730-023
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles
        $superAdmin = Role::create(['name' => 'super-admin']);
        $admin = Role::create(['name' => 'admin']);
        $support = Role::create(['name' => 'support']);
        $finance = Role::create(['name' => 'finance']);
        $user = Role::create(['name' => 'user']);

        // Create Permissions (example for Phase 1)
        Permission::create(['name' => 'access admin panel']);
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'manage kyc']);
        Permission::create(['name' => 'manage plans']);
        
        // Assign permissions to roles
        $admin->givePermissionTo([
            'access admin panel',
            'manage users',
            'manage kyc',
            'manage plans',
        ]);
        
        $support->givePermissionTo([
            'access admin panel',
            'manage users',
            'manage kyc',
        ]);

        // Super-admin gets all permissions implicitly
        $superAdmin->givePermissionTo(Permission::all());
    }
}