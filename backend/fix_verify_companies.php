<?php
/**
 * Fix script to verify companies that have active deals
 * Run: php fix_verify_companies.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Deal;
use Illuminate\Support\Facades\DB;

echo "=== VERIFYING COMPANIES WITH ACTIVE DEALS ===\n\n";

// Get companies that have active deals but are not verified
$unverifiedCompaniesWithDeals = Company::where('status', 'active')
    ->where('is_verified', false)
    ->whereHas('deals', function($q) {
        $q->where('status', 'active')
          ->whereIn('deal_type', ['live', 'upcoming']);
    })
    ->get();

echo "Found {$unverifiedCompaniesWithDeals->count()} unverified companies with active deals:\n\n";

foreach ($unverifiedCompaniesWithDeals as $company) {
    echo "Company #{$company->id}: {$company->name}\n";
    echo "  Status: {$company->status}\n";
    echo "  Verified: " . ($company->is_verified ? 'YES' : 'NO') . "\n";
    echo "  Active deals: {$company->deals()->where('status', 'active')->count()}\n";
    echo "\n";
}

// Ask for confirmation
echo "Do you want to verify these companies? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if (strtolower($line) !== 'yes') {
    echo "Aborted. No changes made.\n";
    exit(0);
}

// Verify the companies
$updated = 0;
foreach ($unverifiedCompaniesWithDeals as $company) {
    $company->is_verified = true;
    $company->save();
    echo "✓ Verified: {$company->name}\n";
    $updated++;
}

echo "\n=== COMPLETE ===\n";
echo "Verified {$updated} companies.\n";
echo "\nNow restart your Laravel server and frontend, then test:\n";
echo "  http://localhost:3000/products\n";
echo "  http://localhost:3000/products?filter=live\n";
echo "  http://localhost:3000/deals\n";
