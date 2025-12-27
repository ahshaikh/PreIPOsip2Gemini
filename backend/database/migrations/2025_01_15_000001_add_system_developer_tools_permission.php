<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds the missing 'system.developer_tools' permission
     * that is required by the /admin/developer routes in api.php:891
     */
    public function up(): void
    {
        // Create the missing permission
        $permission = Permission::firstOrCreate([
            'name' => 'system.developer_tools',
            'guard_name' => 'web'
        ]);

        // Automatically assign to super-admin role (they should have all permissions)
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the permission (this will also remove all role assignments)
        $permission = Permission::where('name', 'system.developer_tools')
                                ->where('guard_name', 'web')
                                ->first();

        if ($permission) {
            $permission->delete();
        }
    }
};
