<?php
// V-FINAL-1730-417 (Created)

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // --- DEFINE PERMISSIONS (FSD-SYS-129) ---
        $permissions = [
            // Users
            'users.view', 'users.create', 'users.edit', 'users.delete', 
            'users.suspend', 'users.adjust_wallet', 'users.manage_roles',
            // KYC
            'kyc.view_queue', 'kyc.approve', 'kyc.reject',
            // Plans
            'plans.view', 'plans.create', 'plans.edit', 'plans.delete',
            // Products
            'products.view', 'products.create', 'products.edit', 'products.delete',
            // Payments
            'payments.view', 'payments.refund', 'payments.offline_entry', 'payments.approve',
            // Withdrawals
            'withdrawals.view_queue', 'withdrawals.approve', 'withdrawals.reject', 'withdrawals.complete',
            // Bonuses
            'bonuses.manage_config', 'bonuses.award_manual', 'bonuses.manage_campaigns',
            // Reports
            'reports.view_financial', 'reports.view_user', 'reports.export',
            // Settings
            'settings.view_system', 'settings.edit_system', 'settings.manage_theme',
            'settings.manage_cms', 'settings.manage_notifications',
            // System Health
            'system.view_health', 'system.view_logs', 'system.manage_backups'
        ];
        
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // --- ASSIGN TO ROLES ---
        
        // Super Admin (Gets everything)
        $superAdmin = Role::findByName('super-admin');
        $superAdmin->givePermissionTo(Permission::all());

        // Admin (Gets almost everything, except core system)
        $admin = Role::findByName('admin');
        $admin->givePermissionTo([
            'users.view', 'users.create', 'users.edit', 'users.suspend', 'users.adjust_wallet',
            'kyc.view_queue', 'kyc.approve', 'kyc.reject',
            'plans.view', 'plans.create', 'plans.edit',
            'products.view', 'products.create', 'products.edit',
            'payments.view', 'payments.refund', 'payments.offline_entry', 'payments.approve',
            'withdrawals.view_queue', 'withdrawals.approve', 'withdrawals.reject', 'withdrawals.complete',
            'bonuses.manage_config', 'bonuses.award_manual', 'bonuses.manage_campaigns',
            'reports.view_financial', 'reports.view_user', 'reports.export',
            'settings.manage_cms', 'settings.manage_notifications',
        ]);
        
        // Support (Limited access)
        $support = Role::findByName('support');
        $support->givePermissionTo([
            'users.view',
            'kyc.view_queue', 'kyc.approve', 'kyc.reject',
            'payments.view',
        ]);
        
        // Finance (Limited access)
        $finance = Role::findByName('finance');
        $finance->givePermissionTo([
            'payments.view', 'payments.refund', 'payments.offline_entry',
            'withdrawals.view_queue', 'withdrawals.approve', 'withdrawals.reject', 'withdrawals.complete',
            'reports.view_financial', 'reports.export',
        ]);
    }
}