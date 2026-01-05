<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
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
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

	// Create the missing permission
        $permission = Permission::firstOrCreate([
            'name' => 'system.developer_tools',
            'guard_name' => 'web',
        ]);

	// Automatically assign to super-admin role (they should have all permissions)
        $superAdmin = Role::where('name', 'super-admin')->first();

        if ($superAdmin && !$superAdmin->hasPermissionTo($permission)) {
            $superAdmin->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }
	// Remove the permission (this will also remove all role assignments)
        Permission::where('name', 'system.developer_tools')
            ->where('guard_name', 'web')
            ->delete();
    }
};