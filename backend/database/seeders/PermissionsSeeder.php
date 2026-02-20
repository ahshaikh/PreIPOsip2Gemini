<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder ONLY handles permissions and roles - nothing else.
     * This ensures permissions are created BEFORE anything tries to use them.
     */
    public function run(): void
    {
        $this->command->info('🔐 Starting Permission Seeder...');

        // CRITICAL: Clear all cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // CRITICAL: Disable foreign key checks during seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables to start fresh
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('📝 Creating permissions...');

        // Define ALL permissions with dot notation (matching route middleware)
        $permissions = [
            // User Management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.export',
            'users.import',
            'users.suspend',
            'users.restore',
            'users.adjust_wallet',
            'users.manage_roles',

            // KYC Management
            'kyc.view_queue',
            'kyc.approve',
            'kyc.reject',
            'kyc.export',

            // Plan Management
            'plans.view',
            'plans.create',
            'plans.edit',
            'plans.delete',

            // Product Management
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            // Bulk Purchase Management
            'bulk_purchases.view',
            'bulk_purchases.create',
            'bulk_purchases.edit',
            'bulk_purchases.delete',

            // Payment Management
            'payments.view',
            'payments.create',
            'payments.refund',
            'payments.export',
            'payments.offline_entry',
            'payments.approve',

            // Withdrawal Management
            'withdrawals.view',
            'withdrawals.view_queue',
            'withdrawals.approve',
            'withdrawals.reject',
            'withdrawals.process',

            // Bonus Management
            'bonuses.view',
            'bonuses.create',
            'bonuses.adjust',
            'bonuses.delete',
            'bonuses.manage_config',

            // Referral Management
            'referrals.view',
            'referrals.manage_campaigns',

            // Lucky Draw Management
            'lucky_draws.view',
            'lucky_draws.create',
            'lucky_draws.execute',
            'lucky_draws.delete',

            // Profit Sharing Management
            'profit_sharing.view',
            'profit_sharing.create',
            'profit_sharing.calculate',
            'profit_sharing.distribute',
            'profit_sharing.reverse',

            // Support Management
            'support.view_tickets',
            'support.assign_tickets',
            'support.resolve_tickets',
            'support.close_tickets',

            // CMS Management
            'settings.manage_cms',

            // Notification Management
            'settings.manage_notifications',

            // Settings Management
            'settings.view_system',
            'settings.edit_system',
            'settings.manage_theme',

            // Compliance Management
            'compliance.view',
            'compliance.create',
            'compliance.edit',
            'compliance.delete',
            'compliance.publish',
            'compliance.archive',

            // Report Access
            'reports.view',
            'reports.export',
            'reports.view_financial',
            'reports.view_user',

            // System Management
            'system.view_logs',
            'system.view_health',
            'system.manage_backups',
            'system.manage_ip_whitelist',
            'system.manage_feature_flags',
            'system.developer_tools',
        ];

        // Create each permission
        $createdCount = 0;
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
            $createdCount++;

            if ($createdCount % 10 == 0) {
                $this->command->info("   Created {$createdCount} permissions...");
            }
        }

        $this->command->info("   ✓ Created {$createdCount} permissions");

        $this->command->info('👥 Creating roles...');

        // Create roles (lowercase with hyphens)
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $kycOfficer = Role::firstOrCreate(['name' => 'kyc-officer', 'guard_name' => 'web']);
        $supportAgent = Role::firstOrCreate(['name' => 'support', 'guard_name' => 'web']);
        $contentManager = Role::firstOrCreate(['name' => 'content-manager', 'guard_name' => 'web']);
        $financeManager = Role::firstOrCreate(['name' => 'finance-manager', 'guard_name' => 'web']);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->command->info('   ✓ Created 7 roles');

        $this->command->info('🔗 Assigning permissions to roles...');

        // Super Admin gets ALL permissions
        $superAdmin->syncPermissions(Permission::all());
        $this->command->info('   ✓ Super Admin: ALL permissions');

        // Admin gets most permissions
        $admin->syncPermissions([
            'users.view', 'users.edit', 'users.suspend', 'users.adjust_wallet',
            'kyc.view_queue', 'kyc.approve', 'kyc.reject',
            'plans.view', 'plans.create', 'plans.edit',
            'products.view', 'products.create', 'products.edit',
            'payments.view', 'payments.refund',
            'withdrawals.view', 'withdrawals.approve', 'withdrawals.reject',
            'bonuses.view', 'bonuses.create',
            'referrals.view',
            'support.view_tickets', 'support.assign_tickets', 'support.resolve_tickets',
            'reports.view', 'reports.export',
            'settings.manage_cms',
        ]);
        $this->command->info('   ✓ Admin: 25 permissions');

        // KYC Officer
        $kycOfficer->syncPermissions([
            'users.view',
            'kyc.view_queue',
            'kyc.approve',
            'kyc.reject',
            'kyc.export',
        ]);
        $this->command->info('   ✓ KYC Officer: 5 permissions');

        // Support Agent
        $supportAgent->syncPermissions([
            'users.view',
            'support.view_tickets',
            'support.assign_tickets',
            'support.resolve_tickets',
            'support.close_tickets',
        ]);
        $this->command->info('   ✓ Support Agent: 5 permissions');

        // Content Manager
        $contentManager->syncPermissions([
            'settings.manage_cms',
        ]);
        $this->command->info('   ✓ Content Manager: 1 permission');

        // Finance Manager
        $financeManager->syncPermissions([
            'payments.view',
            'payments.export',
            'payments.refund',
            'withdrawals.view',
            'withdrawals.approve',
            'withdrawals.reject',
            'withdrawals.process',
            'bonuses.view',
            'bonuses.create',
            'bonuses.adjust',
            'profit_sharing.view',
            'profit_sharing.create',
            'profit_sharing.calculate',
            'profit_sharing.distribute',
            'reports.view_financial',
            'reports.view',
            'reports.export',
        ]);
        $this->command->info('   ✓ Finance Manager: 17 permissions');

        // User role has NO admin permissions
        $this->command->info('   ✓ User: 0 permissions (regular user)');

        // CRITICAL: Clear cache again after creating everything
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('✅ Permission seeder completed successfully!');
        $this->command->info('');
        $this->command->info('Summary:');
        $this->command->info('  - ' . Permission::count() . ' permissions created');
        $this->command->info('  - ' . Role::count() . ' roles created');
        $this->command->info('  - All permissions assigned to roles');
    }
}
