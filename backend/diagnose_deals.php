<?php
/**
 * Diagnostic script to trace why deals aren't showing on frontend
 * Run: php diagnose_deals.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Deal;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "=== DEAL DIAGNOSIS ===\n\n";

// 1. Check total deals
$totalDeals = Deal::count();
echo "1. Total Deals in database: {$totalDeals}\n\n";

// 2. Check deals by status
echo "2. Deals by status:\n";
$dealsByStatus = Deal::select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();
foreach ($dealsByStatus as $row) {
    echo "   - {$row->status}: {$row->count}\n";
}
echo "\n";

// 3. Check deals by deal_type
echo "3. Deals by deal_type:\n";
$dealsByType = Deal::select('deal_type', DB::raw('count(*) as count'))
    ->groupBy('deal_type')
    ->get();
foreach ($dealsByType as $row) {
    echo "   - {$row->deal_type}: {$row->count}\n";
}
echo "\n";

// 4. Check active + live deals
$activeDeals = Deal::where('status', 'active')->where('deal_type', 'live')->get();
echo "4. Active + Live deals: {$activeDeals->count()}\n";
foreach ($activeDeals as $deal) {
    echo "   - Deal #{$deal->id}: {$deal->title}\n";
    echo "     Company ID: {$deal->company_id}\n";
    echo "     Opens at: " . ($deal->deal_opens_at ? $deal->deal_opens_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "     Closes at: " . ($deal->deal_closes_at ? $deal->deal_closes_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
    echo "     NOW: " . now()->format('Y-m-d H:i:s') . "\n";

    // Check if would pass live() scope
    $passesLive = $deal->deal_type === 'live'
        && $deal->status === 'active'
        && $deal->deal_opens_at <= now()
        && (!$deal->deal_closes_at || $deal->deal_closes_at > now());
    echo "     Passes live() scope: " . ($passesLive ? 'YES' : 'NO') . "\n";

    // Check company
    $company = $deal->company;
    if ($company) {
        echo "     Company: {$company->name}\n";
        echo "     Company Status: {$company->status}\n";
        echo "     Company Verified: " . ($company->is_verified ? 'YES' : 'NO') . "\n";
        echo "     Company Sector: {$company->sector}\n";
        echo "     Company Sector ID: {$company->sector_id}\n";
    } else {
        echo "     Company: NOT FOUND (company_id={$deal->company_id})\n";
    }
    echo "\n";
}

// 5. Check deals using live() scope
echo "5. Deals using Deal::live() scope: ";
$liveDeals = Deal::live()->get();
echo "{$liveDeals->count()}\n";
foreach ($liveDeals as $deal) {
    echo "   - Deal #{$deal->id}: {$deal->title}\n";
}
echo "\n";

// 6. Check companies
echo "6. Companies in database:\n";
$totalCompanies = Company::count();
$activeCompanies = Company::where('status', 'active')->where('is_verified', true)->count();
echo "   Total: {$totalCompanies}\n";
echo "   Active + Verified: {$activeCompanies}\n\n";

// 7. Check companies with deals
echo "7. Active + Verified companies WITH deals:\n";
$companiesWithDeals = Company::where('status', 'active')
    ->where('is_verified', true)
    ->whereHas('deals', function($q) {
        $q->where('status', 'active')
          ->whereIn('deal_type', ['live', 'upcoming']);
    })
    ->with('deals')
    ->get();

echo "   Count: {$companiesWithDeals->count()}\n";
foreach ($companiesWithDeals as $company) {
    echo "   - Company #{$company->id}: {$company->name}\n";
    echo "     Deals: {$company->deals->count()}\n";
    foreach ($company->deals as $deal) {
        echo "       * {$deal->title} (type={$deal->deal_type}, status={$deal->status})\n";
    }
}
echo "\n";

// 8. Check companies with LIVE deals specifically
echo "8. Active + Verified companies WITH LIVE deals (using live() scope):\n";
$companiesWithLiveDeals = Company::where('status', 'active')
    ->where('is_verified', true)
    ->whereHas('deals', function($q) {
        $q->live();
    })
    ->get();

echo "   Count: {$companiesWithLiveDeals->count()}\n";
foreach ($companiesWithLiveDeals as $company) {
    echo "   - Company #{$company->id}: {$company->name}\n";
}
echo "\n";

echo "=== DIAGNOSIS COMPLETE ===\n";
echo "\nIf you see 0 companies in step 8, check:\n";
echo "1. deal_opens_at must be <= NOW (not in future)\n";
echo "2. deal_closes_at must be NULL or > NOW (not in past)\n";
echo "3. company_id in deals must match actual company IDs\n";
echo "4. Companies must have status='active' AND is_verified=1\n";
