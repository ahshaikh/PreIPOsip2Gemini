<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$userId = 261;
$companyId = 258;

// Check if company exists
$company = DB::table('companies')->where('id', $companyId)->first();
if (!$company) {
    echo "Company {$companyId} not found\n";
    exit(1);
}

// Check if user exists
$user = DB::table('users')->where('id', $userId)->first();
if (!$user) {
    echo "User {$userId} not found\n";
    exit(1);
}

// Check if role already exists
$existingRole = DB::table('company_user_roles')
    ->where('user_id', $userId)
    ->where('company_id', $companyId)
    ->first();

if ($existingRole) {
    echo "Role already exists for user. Updating to founder and activating...\n";
    DB::table('company_user_roles')
        ->where('id', $existingRole->id)
        ->update([
            'role' => 'founder',
            'is_active' => true,
            'revoked_at' => null,
            'updated_at' => now(),
        ]);
    echo "Updated existing role to founder\n";
} else {
    echo "Creating new founder role for user {$userId} in company {$companyId}\n";
    DB::table('company_user_roles')->insert([
        'user_id' => $userId,
        'company_id' => $companyId,
        'role' => 'founder',
        'is_active' => true,
        'assigned_by' => $userId, // Self-assigned for now
        'assigned_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "Created founder role successfully\n";
}

// Verify
$role = DB::table('company_user_roles')
    ->where('user_id', $userId)
    ->where('company_id', $companyId)
    ->where('is_active', true)
    ->whereNull('revoked_at')
    ->first();

if ($role) {
    echo "✓ Verification successful - User now has active '{$role->role}' role\n";
} else {
    echo "✗ Verification failed - Role not found after insert\n";
    exit(1);
}
