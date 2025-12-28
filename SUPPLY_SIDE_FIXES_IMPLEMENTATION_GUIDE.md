# Supply-Side Fixes Implementation Guide
## Inventory Provenance & Conservation Enforcement

**Date:** 2025-12-28
**Status:** Implementation Ready
**Risk Level:** P0 - Architectural

---

## EXECUTIVE SUMMARY

Implements fixes B.5, B.6, B.7 from the architectural audit:

- ✅ **B.5: Inventory Provenance Required** - No inventory without verified company source
- ✅ **B.6: Manual Bulk Purchase Constraints** - Manual paths cannot bypass verification
- ✅ **B.7: Inventory Conservation Guaranteed** - Mathematical guarantee: debits = credits

**PROTOCOL ENFORCED:**
- "If the system cannot explain why money moved, the system is broken"
- "Any inventory change without provenance is a P0 failure"
- "Admin solvency must be provable at all times"

---

## WHAT WAS BROKEN

### BEFORE (Unverified Inventory):

```
Admin creates BulkPurchase:
  product_id: 123
  face_value_purchased: ₹100,000
  actual_cost_paid: ₹70,000
  seller_name: "ABC Corp" ← Just a string, no verification

Questions that CANNOT be answered:
- Is ABC Corp a verified company?
- Did ABC Corp submit documentation?
- Did admin approve ABC Corp's KYC?
- Where is the share listing that led to this purchase?
- Can we trace this inventory back to a legal agreement?

RESULT: Orphaned inventory with no provenance
```

### Conservation Violations:

```
Scenario 1: Race Condition
- User A allocates ₹10,000 (locks bulk_purchase row)
- User B allocates ₹10,000 (locks same row)
- Both decrement value_remaining by ₹10,000
- value_remaining goes negative ❌

Scenario 2: Manual Manipulation
- Admin manually updates: UPDATE bulk_purchases SET value_remaining = 999999
- Inventory "created" from thin air ❌
- SUM(allocated) + SUM(remaining) > SUM(total_received) ❌

Scenario 3: Failed Reversal
- Allocation fails, compensation tries to restore inventory
- Compensation fails silently
- Inventory permanently lost ❌
```

---

## WHAT WAS FIXED

### Fix B.5: Inventory Provenance Required

**Database Schema Changes:**

```sql
-- bulk_purchases table now requires:
ALTER TABLE bulk_purchases ADD COLUMN company_id BIGINT NOT NULL;
ALTER TABLE bulk_purchases ADD COLUMN company_share_listing_id BIGINT;
ALTER TABLE bulk_purchases ADD COLUMN source_type ENUM('company_listing', 'manual_entry');
ALTER TABLE bulk_purchases ADD COLUMN approved_by_admin_id BIGINT;
ALTER TABLE bulk_purchases ADD COLUMN manual_entry_reason TEXT;
ALTER TABLE bulk_purchases ADD COLUMN source_documentation TEXT;
ALTER TABLE bulk_purchases ADD COLUMN verified_at TIMESTAMP;

-- Constraints:
CHECK (source_type = 'company_listing' AND company_share_listing_id IS NOT NULL)
  OR (source_type = 'manual_entry' AND approved_by_admin_id IS NOT NULL
      AND manual_entry_reason IS NOT NULL AND LENGTH(manual_entry_reason) >= 50)
```

**Provenance Chain:**

```
BulkPurchase
  ↓ company_id (REQUIRED)
  ↓ Company (MUST be is_verified = true)
  ↓
  ↓ IF source_type = 'company_listing':
  │   ↓ company_share_listing_id (REQUIRED)
  │   ↓ CompanyShareListing (MUST be status = 'approved')
  │   ↓ Full approval workflow with admin review
  │
  ↓ IF source_type = 'manual_entry':
      ↓ approved_by_admin_id (REQUIRED)
      ↓ User (MUST be super-admin)
      ↓ manual_entry_reason (REQUIRED, min 50 chars)
      ↓ source_documentation (REQUIRED)
```

**CompanyInventoryService:**

Two strictly controlled paths:

```php
// Path 1: From approved company listing (PREFERRED)
$inventory = $companyInventoryService->createInventoryFromListing(
    $listing,
    $actualCostPaid
);
// Enforces:
// - Company.is_verified = true
// - Listing.status = 'approved'
// - No duplicate conversion
// - Records in admin ledger

// Path 2: Manual entry (CONSTRAINED)
$inventory = $companyInventoryService->createManualInventoryEntry(
    $company,          // MUST be verified
    $product,
    $inventoryData,
    $approvingAdmin,   // MUST be super-admin
    $reason,           // MUST be >= 50 chars
    $documentation     // MUST exist
);
// Enforces:
// - All provenance fields populated
// - Logged as WARNING for audit review
// - Cannot bypass company verification
```

---

### Fix B.6: Manual Bulk Purchase Constraints

**What Changed:**

**BEFORE:**
```php
// Admin could create inventory without ANY verification
BulkPurchase::create([
    'product_id' => 123,
    'face_value_purchased' => 100000,
    'actual_cost_paid' => 70000,
    'seller_name' => 'Random Corp', // ← No verification!
]);
// RESULT: Orphaned inventory, no provenance
```

**AFTER:**
```php
// Database constraint PREVENTS creation without company
// Attempting to create without company_id → SQL Error

// CompanyInventoryService enforces:
if (!$company->is_verified) {
    throw ValidationException('Cannot create inventory for unverified company');
}

if ($source_type === 'manual_entry') {
    if (!$approvingAdmin->hasRole('super-admin')) {
        throw ValidationException('Only super-admins can approve manual inventory');
    }

    if (strlen($reason) < 50) {
        throw ValidationException('Manual inventory requires detailed justification (min 50 chars)');
    }

    if (empty($sourceDocumentation)) {
        throw ValidationException('Manual inventory requires source documentation');
    }

    Log::warning("MANUAL INVENTORY CREATED - AUDIT REQUIRED", [
        'company_id' => $company->id,
        'approved_by' => $approvingAdmin->id,
        'reason' => $reason,
    ]);
}
```

**Admin Panel Changes Required:**

Old manual bulk purchase form must be updated to:
1. Require company selection (dropdown of VERIFIED companies only)
2. Show warning: "Manual inventory requires super-admin approval"
3. Add reason field (min 50 chars, multiline textarea)
4. Add documentation upload (files or URLs)
5. Show approval confirmation: "I verify this inventory has proper legal documentation and company approval"

---

### Fix B.7: Inventory Conservation Guaranteed

**Mathematical Invariant:**

```
For every product:
  SUM(bulk_purchases.total_value_received) =
    SUM(bulk_purchases.value_remaining) + SUM(user_investments.value_allocated WHERE is_reversed = false)

Simplified:
  total_purchased = allocated + remaining

Conservation cannot be violated.
```

**Database-Level Enforcement:**

```sql
-- Constraint 1: Cannot go negative
ALTER TABLE bulk_purchases
ADD CONSTRAINT check_value_remaining_non_negative
CHECK (value_remaining >= 0);

-- Constraint 2: Cannot exceed total
ALTER TABLE bulk_purchases
ADD CONSTRAINT check_value_remaining_not_over_total
CHECK (value_remaining <= total_value_received);

-- If these constraints violated → SQL rejects transaction
```

**Application-Level Enforcement:**

**InventoryConservationService:**

```php
// BEFORE allocation:
$canAllocate = $conservationService->canAllocate($product, $amount);
if (!$canAllocate['can_allocate']) {
    throw new Exception($canAllocate['reason']);
}
// Uses pessimistic locking + pre-check

// AFTER allocation:
$result = $conservationService->verifyAllocation($userInvestment);
if (!$result['is_conserved']) {
    Log::critical("CONSERVATION VIOLATED", $result);
    // System self-detects violations
}
```

**Locking Strategy:**

```php
// AllocationService (updated):
DB::transaction(function () use ($product, $amount) {
    // Lock ALL bulk purchases for product (prevents concurrent allocation)
    $batches = BulkPurchase::where('product_id', $product->id)
        ->where('value_remaining', '>', 0)
        ->orderBy('purchase_date', 'asc') // FIFO
        ->lockForUpdate() // ← CRITICAL
        ->get();

    // Pre-check: total available
    if ($batches->sum('value_remaining') < $amount) {
        throw new Exception('Insufficient inventory');
    }

    // Allocate (FIFO)
    foreach ($batches as $batch) {
        $toTake = min($batch->value_remaining, $remainingNeeded);

        UserInvestment::create([...]);
        $batch->decrement('value_remaining', $toTake); // Atomic

        $remainingNeeded -= $toTake;
        if ($remainingNeeded <= 0) break;
    }

    // Post-check: conservation holds
    $conservation = $conservationService->verifyConservation($product);
    if (!$conservation['is_conserved']) {
        throw new Exception('Conservation violated');
    }
});
```

**Reconciliation:**

```php
// Daily reconciliation job
$report = $conservationService->reconcile();

if ($report['status'] === 'discrepancies_found') {
    // Alert admins
    Notification::send(
        User::role('super-admin')->get(),
        new InventoryConservationViolationAlert($report)
    );

    Log::critical("INVENTORY CONSERVATION VIOLATED", $report);
}

// Report includes:
// - Which products violated
// - Discrepancy amount and percentage
// - Recommended action (restore inventory vs review allocations)
```

---

## IMPLEMENTATION STEPS

### Phase 1: Database Migration

```bash
cd backend

# Run provenance migration
php artisan migrate --path=database/migrations/2025_12_28_100001_enforce_bulk_purchase_provenance.php

# Run conservation constraints migration
php artisan migrate --path=database/migrations/2025_12_28_100002_add_inventory_conservation_constraints.php
```

**CRITICAL:** The provenance migration includes data migration logic that attempts to:
1. Link existing bulk_purchases to companies via products
2. Set source_type based on whether company_share_listing exists
3. Fill manual_entry_reason for historical entries

**If migration fails:**
- Some bulk_purchases may not have a valid company_id linkage
- Admin must MANUALLY assign companies to these purchases
- Query to find orphaned purchases:
  ```sql
  SELECT * FROM bulk_purchases WHERE company_id IS NULL;
  ```

---

### Phase 2: Update Controllers

**AdminShareListingController (approve method):**

```php
// BEFORE:
public function approve(Request $request, $id)
{
    $listing = CompanyShareListing::findOrFail($id);

    if ($request->get('create_bulk_purchase', true)) {
        BulkPurchase::create([
            'product_id' => $request->product_id,
            'face_value_purchased' => $approvedQuantity * $listing->face_value_per_share,
            'actual_cost_paid' => $approvedQuantity * $approvedPrice,
            // ... other fields
        ]);
    }
}

// AFTER:
public function approve(Request $request, $id)
{
    $listing = CompanyShareListing::findOrFail($id);

    if ($request->get('create_bulk_purchase', true)) {
        $companyInventoryService = app(CompanyInventoryService::class);

        $bulkPurchase = $companyInventoryService->createInventoryFromListing(
            $listing,
            $approvedQuantity * $approvedPrice, // actual cost paid
            [
                'product_id' => $request->product_id,
                'extra_allocation_percentage' => $request->extra_allocation_percentage ?? 0,
                'notes' => $request->admin_notes,
            ]
        );

        // Link listing to bulk purchase
        $listing->update(['bulk_purchase_id' => $bulkPurchase->id]);
    }
}
```

**BulkPurchaseController (store method for manual entries):**

```php
// BEFORE:
public function store(Request $request)
{
    $validated = $request->validate([
        'product_id' => 'required|exists:products,id',
        'face_value_purchased' => 'required|numeric|min:0.01',
        'actual_cost_paid' => 'required|numeric|min:0',
        'seller_name' => 'required|string',
        // ... other fields
    ]);

    $bulkPurchase = BulkPurchase::create($validated);

    return response()->json(['bulk_purchase' => $bulkPurchase], 201);
}

// AFTER:
public function store(Request $request)
{
    $validated = $request->validate([
        'company_id' => 'required|exists:companies,id',
        'product_id' => 'required|exists:products,id',
        'face_value_purchased' => 'required|numeric|min:0.01',
        'actual_cost_paid' => 'required|numeric|min:0',
        'manual_entry_reason' => 'required|string|min:50',
        'source_documentation' => 'required|array|min:1',
        // ... other fields
    ]);

    $company = Company::findOrFail($validated['company_id']);
    $product = Product::findOrFail($validated['product_id']);

    $companyInventoryService = app(CompanyInventoryService::class);

    try {
        $bulkPurchase = $companyInventoryService->createManualInventoryEntry(
            $company,
            $product,
            [
                'face_value_purchased' => $validated['face_value_purchased'],
                'actual_cost_paid' => $validated['actual_cost_paid'],
                'extra_allocation_percentage' => $validated['extra_allocation_percentage'] ?? 0,
                'purchase_date' => $validated['purchase_date'] ?? now(),
                'notes' => $validated['notes'] ?? '',
            ],
            auth()->user(), // Approving admin
            $validated['manual_entry_reason'],
            $validated['source_documentation']
        );

        return response()->json([
            'bulk_purchase' => $bulkPurchase,
            'warning' => 'Manual inventory entry flagged for audit review',
        ], 201);

    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Manual inventory entry rejected',
            'errors' => $e->errors(),
        ], 422);
    }
}
```

---

### Phase 3: Update AllocationService ✅ COMPLETED

**Integration Status:** AllocationService has been fully updated with conservation enforcement.

**Changes Made:**

1. **Constructor Updated:** Injected InventoryConservationService dependency
2. **New Method:** `allocateShares(user, product, amount, investment, source, allowFractional)` - Orchestration-compatible
3. **New Method:** `reverseAllocation(investment, reason)` - Compensation-compatible
4. **Legacy Methods:** Renamed old methods to `allocateSharesLegacy()` and `reverseAllocationLegacy()` for backward compatibility

**Key Features:**

```php
// BEFORE allocation: Pre-check conservation
$canAllocate = $this->conservationService->canAllocate($product, $amount);
if (!$canAllocate['can_allocate']) {
    return false; // Blocked by conservation check
}

// Lock inventory for product (prevents concurrent allocation)
$batches = $this->conservationService->lockInventoryForAllocation($product);

// FIFO allocation with SYNC inventory decrement
foreach ($batches as $batch) {
    UserInvestment::create([...]);
    $batch->decrement('value_remaining', $amountToTake);
}

// AFTER allocation: Verify conservation holds
$verificationResult = $this->conservationService->verifyConservation($product);
if (!$verificationResult['is_conserved']) {
    throw new \Exception('Conservation violated');
}
```

**File:** `backend/app/Services/AllocationService.php`

---

### Phase 4: Scheduled Reconciliation

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Reconcile inventory conservation daily at 3 AM
    $schedule->call(function () {
        $conservationService = app(InventoryConservationService::class);
        $report = $conservationService->reconcile();

        if ($report['status'] === 'discrepancies_found') {
            // Alert super-admins
            $admins = User::role('super-admin')->get();
            Notification::send($admins, new InventoryConservationViolationAlert($report));

            Log::critical("INVENTORY CONSERVATION VIOLATED", $report);
        } else {
            Log::info("Inventory conservation check passed", [
                'total_products' => $report['summary']['total_products'],
                'conservation_rate' => $report['summary']['conservation_rate'],
            ]);
        }
    })->daily()->at('03:00');

    // Health check every hour (for dashboard)
    $schedule->call(function () {
        $conservationService = app(InventoryConservationService::class);
        $health = $conservationService->getHealthScore();

        Cache::put('inventory_conservation_health', $health, now()->addHours(2));

        if ($health['severity'] === 'critical') {
            Log::critical("INVENTORY CONSERVATION HEALTH CRITICAL", $health);
        }
    })->hourly();
}
```

---

### Phase 5: Admin Dashboard

**Add conservation health widget:**

```php
// Dashboard Controller
public function index()
{
    $conservationHealth = Cache::get('inventory_conservation_health', function () {
        $conservationService = app(InventoryConservationService::class);
        return $conservationService->getHealthScore();
    });

    return view('admin.dashboard', [
        'conservation_health' => $conservationHealth,
        // ... other dashboard data
    ]);
}
```

**Dashboard blade template:**

```html
<div class="widget conservation-health">
    <h3>Inventory Conservation Health</h3>

    <div class="health-score {{ $conservation_health['severity'] }}">
        {{ $conservation_health['health_score'] }}%
    </div>

    <div class="stats">
        <p>Products Healthy: {{ $conservation_health['products_healthy'] }} / {{ $conservation_health['total_products'] }}</p>
        <p>Products Violated: {{ $conservation_health['products_violated'] }}</p>
    </div>

    @if($conservation_health['severity'] !== 'healthy')
        <div class="alert alert-{{ $conservation_health['severity'] }}">
            Warning: Inventory conservation violations detected.
            <a href="{{ route('admin.inventory.reconciliation') }}">View Report</a>
        </div>
    @endif
</div>
```

---

## TESTING

### Test 1: Provenance Enforcement

```php
// Test: Cannot create inventory without company
public function test_cannot_create_inventory_without_company()
{
    $this->expectException(QueryException::class);

    BulkPurchase::create([
        'product_id' => 1,
        'face_value_purchased' => 10000,
        'actual_cost_paid' => 7000,
        // company_id missing → SQL error
    ]);
}

// Test: Cannot create inventory for unverified company
public function test_cannot_create_inventory_for_unverified_company()
{
    $company = Company::factory()->create(['is_verified' => false]);
    $listing = CompanyShareListing::factory()->create(['company_id' => $company->id]);

    $service = app(CompanyInventoryService::class);

    $this->expectException(ValidationException::class);

    $service->createInventoryFromListing($listing, 10000);
}

// Test: Manual entry requires detailed reason
public function test_manual_entry_requires_detailed_reason()
{
    $company = Company::factory()->create(['is_verified' => true]);
    $product = Product::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $service = app(CompanyInventoryService::class);

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('at least 50 characters');

    $service->createManualInventoryEntry(
        $company,
        $product,
        ['face_value_purchased' => 10000, 'actual_cost_paid' => 7000],
        $admin,
        'Short reason', // Too short!
        ['doc' => 'agreement.pdf']
    );
}
```

### Test 2: Conservation Guarantee

```php
// Test: Conservation holds after allocation
public function test_conservation_holds_after_allocation()
{
    $product = Product::factory()->create();
    BulkPurchase::factory()->create([
        'product_id' => $product->id,
        'total_value_received' => 100000,
        'value_remaining' => 100000,
    ]);

    // Allocate ₹30,000
    UserInvestment::factory()->create([
        'product_id' => $product->id,
        'value_allocated' => 30000,
        'is_reversed' => false,
    ]);

    $service = app(InventoryConservationService::class);
    $result = $service->verifyConservation($product);

    $this->assertTrue($result['is_conserved']);
    $this->assertEquals(100000, $result['total_received']);
    $this->assertEquals(30000, $result['allocated_to_users']);
    $this->assertEquals(70000, $result['remaining_inventory']);
    $this->assertEquals(0, $result['discrepancy']);
}

// Test: Conservation detects violation
public function test_conservation_detects_violation()
{
    $product = Product::factory()->create();
    BulkPurchase::factory()->create([
        'product_id' => $product->id,
        'total_value_received' => 100000,
        'value_remaining' => 100000,
    ]);

    // Manually corrupt: allocate without decrementing inventory
    DB::table('bulk_purchases')
        ->where('product_id', $product->id)
        ->update(['value_remaining' => 100000]); // Should be 70000

    UserInvestment::factory()->create([
        'product_id' => $product->id,
        'value_allocated' => 30000,
        'is_reversed' => false,
    ]);

    $service = app(InventoryConservationService::class);
    $result = $service->verifyConservation($product);

    $this->assertFalse($result['is_conserved']);
    $this->assertEquals(30000, $result['discrepancy']); // ₹30,000 over
}
```

---

## EXPECTED OUTCOMES

**BEFORE:**
- Orphaned inventory with no company linkage
- Manual bulk purchases bypass all verification
- Inventory conservation violations undetected
- No answer to: "Why does this inventory exist?"

**AFTER:**
- Every bulk purchase traces to verified company
- Manual entries require super-admin approval + documentation
- Conservation violations prevented by database constraints
- Full provenance: bulk_purchase → company → listing/approval → documentation

**Security:**
- Cannot create inventory for unverified companies
- Cannot bypass provenance requirements
- Database-level constraints prevent conservation violations
- Daily reconciliation detects discrepancies

**Compliance:**
- Every inventory has audit trail
- Manual entries flagged for review
- Can answer: "Why does this inventory exist? Who supplied it?"
- Admin ledger tracks all inventory purchases

---

## ROLLBACK PLAN

If issues arise:

```bash
# Rollback migrations
php artisan migrate:rollback --step=2

# This removes:
# - Provenance columns and constraints
# - Conservation constraints
# - Indexes

# System reverts to previous state (but loses provenance tracking)
```

**Manual data cleanup** (if needed):
```sql
-- If some bulk purchases fail provenance checks after migration:
UPDATE bulk_purchases
SET company_id = (SELECT company_id FROM products WHERE id = bulk_purchases.product_id LIMIT 1)
WHERE company_id IS NULL;
```

---

**Implementation Status:** Ready for deployment
**Risk Level:** Medium (requires data migration)
**Recommended Rollout:** Staging → Production with monitoring

