<?php
// Quick diagnostic script to check admin authentication setup

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ADMIN AUTHENTICATION DIAGNOSTIC ===\n\n";

// Check IP Whitelist
echo "1. IP Whitelist Check:\n";
try {
    $whitelist = \App\Models\IpWhitelist::where('is_active', true)->get();
    if ($whitelist->isEmpty()) {
        echo "   ✓ IP Whitelist is EMPTY (feature disabled)\n";
    } else {
        echo "   ⚠ IP Whitelist has entries:\n";
        foreach ($whitelist as $entry) {
            echo "     - {$entry->ip_address} ({$entry->description})\n";
        }
    }
} catch (\Exception $e) {
    echo "   ℹ IP Whitelist table may not exist: " . $e->getMessage() . "\n";
}

echo "\n2. Auth Guards Configuration:\n";
$guards = config('auth.guards');
foreach ($guards as $name => $guard) {
    echo "   - {$name}: driver={$guard['driver']}, provider={$guard['provider']}\n";
}

echo "\n3. Auth Providers Configuration:\n";
$providers = config('auth.providers');
foreach ($providers as $name => $provider) {
    echo "   - {$name}: driver={$provider['driver']}, model={$provider['model']}\n";
}

echo "\n4. Sanctum Configuration:\n";
echo "   - Stateful domains: " . json_encode(config('sanctum.stateful')) . "\n";
echo "   - Prefix: " . config('sanctum.prefix', 'sanctum') . "\n";

echo "\n5. Sample Admin User Check:\n";
try {
    $admin = \App\Models\User::whereHas('roles', function($q) {
        $q->whereIn('name', ['admin', 'super-admin']);
    })->first();
    
    if ($admin) {
        echo "   ✓ Found admin user: {$admin->email} (ID: {$admin->id})\n";
        echo "   - Roles: " . $admin->roles->pluck('name')->implode(', ') . "\n";
        
        // Check if user has valid token
        $tokenCount = $admin->tokens()->count();
        echo "   - Active tokens: {$tokenCount}\n";
        
        if ($tokenCount > 0) {
            $latestToken = $admin->tokens()->latest()->first();
            echo "   - Latest token: {$latestToken->name} (created: {$latestToken->created_at})\n";
        }
    } else {
        echo "   ⚠ No admin user found in database\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error checking admin user: " . $e->getMessage() . "\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
