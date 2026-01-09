<?php
// Test if is_admin accessor fix works

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing is_admin Accessor Fix ===\n\n";

$user = App\Models\User::find(1);

if (!$user) {
    echo "❌ User ID 1 not found\n";
    exit(1);
}

echo "User: {$user->email}\n";
echo "User ID: {$user->id}\n\n";

// Get roles
$roles = $user->roles->pluck('name')->toArray();
echo "Roles assigned: " . implode(', ', $roles) . "\n\n";

// Test the accessor
echo "Testing is_admin accessor:\n";
echo "  \$user->is_admin = " . ($user->is_admin ? 'TRUE' : 'FALSE') . "\n\n";

// Test hasAnyRole method directly
echo "Testing hasAnyRole(['admin', 'super-admin']):\n";
echo "  Result: " . ($user->hasAnyRole(['admin', 'super-admin']) ? 'TRUE' : 'FALSE') . "\n\n";

// Check if 'super-admin' role exists
if (in_array('super-admin', $roles)) {
    echo "✅ User has 'super-admin' role (with hyphen)\n";
} elseif (in_array('superadmin', $roles)) {
    echo "✅ User has 'superadmin' role (no hyphen)\n";
} else {
    echo "⚠️  User does NOT have admin role\n";
}

echo "\n=== Result ===\n";
if ($user->is_admin) {
    echo "✅ is_admin returns TRUE - Fix is working!\n";
    echo "   Admin routes should be accessible now.\n";
} else {
    echo "❌ is_admin returns FALSE - Fix not working\n";
    echo "   Check User.php getIsAdminAttribute() method\n";
}
