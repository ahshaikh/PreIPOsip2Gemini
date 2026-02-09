<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Assign founder roles to all CompanyUsers without roles
 *
 * CONTEXT:
 * After fixing the company_user_roles FK to reference company_users instead of users,
 * existing CompanyUsers need roles assigned to access disclosure features.
 *
 * ASSUMPTION:
 * All existing CompanyUsers are company founders/creators until proven otherwise.
 * This is a safe default for MVP stage.
 */
class AssignMissingCompanyUserRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Finding CompanyUsers without roles...');

        // Find all CompanyUsers without active roles
        $usersWithoutRoles = DB::table('company_users as cu')
            ->leftJoin('company_user_roles as cur', function($join) {
                $join->on('cu.id', '=', 'cur.user_id')
                     ->where('cur.is_active', true)
                     ->whereNull('cur.revoked_at');
            })
            ->whereNull('cur.id')
            ->select('cu.id', 'cu.email', 'cu.company_id')
            ->get();

        if ($usersWithoutRoles->isEmpty()) {
            $this->command->info('✓ All CompanyUsers already have roles assigned');
            return;
        }

        $this->command->warn("Found {$usersWithoutRoles->count()} CompanyUsers without roles");
        $this->command->info('Assigning founder role to all...');

        $now = now();
        $rolesCreated = 0;

        foreach ($usersWithoutRoles as $user) {
            DB::table('company_user_roles')->insert([
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'role' => 'founder',
                'is_active' => true,
                'assigned_by' => null, // System-assigned
                'assigned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $rolesCreated++;
            $this->command->info("  ✓ Assigned founder role to {$user->email} (ID: {$user->id})");
        }

        $this->command->info("\n✓ Successfully assigned {$rolesCreated} founder roles");
        $this->command->warn("\n⚠️  IMPORTANT: Review these role assignments");
        $this->command->warn("    If any users should have different roles (finance, legal, viewer),");
        $this->command->warn("    update them manually in the company_user_roles table.");
    }
}
