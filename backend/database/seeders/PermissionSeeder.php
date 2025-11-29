<?php
// V-FINAL-1730-417 (Fixed & Robust)

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
            // Dashboard
            'dashboard.view',
            
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
            
            // Settings - EXPANDED
            'settings.view_system', 'settings.edit_system', 'settings.manage_theme',
            'settings.manage_cms', 'settings.manage_notifications',
            'settings.view', 'settings.edit', 
            
            // System Health
            'system.view_health', 'system.view_logs', 'system.manage_backups',
            
            // Notifications
            'notifications.view', 'notifications.send'
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // --- ASSIGN TO ROLES ---
        
        // Super Admin (Gets everything)
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin (Gets almost everything)
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            'dashboard.view',
            'users.view', 'users.create', 'users.edit', 'users.suspend', 'users.adjust_wallet', 'users.manage_roles',
            
            'kyc.view_queue', 'kyc.approve', 'kyc.reject',
            'plans.view', 'plans.create', 'plans.edit',
            'products.view', 'products.create', 'products.edit',
            'payments.view', 'payments.refund', 'payments.offline_entry', 'payments.approve',
            'withdrawals.view_queue', 'withdrawals.approve', 'withdrawals.reject', 'withdrawals.complete',
            'bonuses.manage_config', 'bonuses.award_manual', 'bonuses.manage_campaigns',
            'reports.view_financial', 'reports.view_user', 'reports.export',
            
            // Settings
            'settings.manage_cms', 
            'settings.manage_notifications', 
            'settings.view', 
            'settings.edit',
            'settings.view_system',
            'settings.edit_system',
            
            // SYSTEM ACCESS (Fixes 403 on IP Whitelist, Logs, Health)
            'system.view_health', 
            'system.view_logs', 
            'system.manage_backups', // Required for IP Whitelist route
            
            'notifications.view', 'notifications.send'
        ]);
        
        // Support (Limited access)
        $support = Role::firstOrCreate(['name' => 'support', 'guard_name' => 'web']);
        $support->givePermissionTo([
            'dashboard.view',
            'users.view',
            'kyc.view_queue', 'kyc.approve', 'kyc.reject',
            'payments.view',
        ]);
        
        // Finance (Limited access)
        $finance = Role::firstOrCreate(['name' => 'finance', 'guard_name' => 'web']);
        $finance->givePermissionTo([
            'dashboard.view',
            'payments.view', 'payments.refund', 'payments.offline_entry',
            'withdrawals.view_queue', 'withdrawals.approve', 'withdrawals.reject', 'withdrawals.complete',
            'reports.view_financial', 'reports.export',
        ]);
        
        $this->command->info('Permissions seeded and assigned to roles successfully!');
    }
}