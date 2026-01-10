# Phase 2 - Security Considerations & Edge Cases

**Document Version:** 1.0
**Date:** 2026-01-10
**Phase:** Governance Protocol - Disclosure Review & Lifecycle Management

---

## Table of Contents

1. [Security Threat Model](#security-threat-model)
2. [Edge Cases & Mitigation](#edge-cases--mitigation)
3. [Potential Misuse Scenarios](#potential-misuse-scenarios)
4. [Race Conditions](#race-conditions)
5. [Data Integrity](#data-integrity)
6. [Audit & Compliance](#audit--compliance)
7. [Operational Guidelines](#operational-guidelines)
8. [Testing Requirements](#testing-requirements)

---

## 1. Security Threat Model

### 1.1 Threat: Unauthorized Investment After Suspension

**Scenario:**
Company is suspended but investor has already initiated payment. Payment webhook arrives after suspension, crediting investment.

**Attack Vector:**
- Investor initiates payment
- Admin suspends company
- Payment gateway webhook arrives 30 seconds later
- System processes payment despite suspension

**Mitigation:**
```php
// In PaymentWebhookController
public function handlePaymentSuccess(Request $request) {
    DB::transaction(function() use ($request) {
        $company = Company::lockForUpdate()->find($companyId);

        // GUARD: Re-check company state at payment processing time
        if (!app(CompanyLifecycleService::class)->canAcceptInvestments($company)) {
            $this->refundPayment($request->payment_id);
            Log::critical('Payment processed after suspension - refunded', [
                'company_id' => $company->id,
                'payment_id' => $request->payment_id,
            ]);
            return;
        }

        // Proceed with investment...
    });
}
```

**Implemented Defenses:**
- Three-layer guard: Policy → Middleware → Service
- Database row locking during payment processing
- Automatic refund if state changed during payment
- Critical-level logging for investigation

---

### 1.2 Threat: Disclosure Data Tampering

**Scenario:**
Attacker modifies approved disclosure data in database to mislead investors.

**Attack Vector:**
- Direct SQL injection (if exists)
- Compromised admin account
- Database access via backup file

**Mitigation:**
```php
// DisclosureVersionObserver enforces immutability
public function updating(DisclosureVersion $version): bool {
    Log::critical('IMMUTABILITY VIOLATION ATTEMPT', [
        'version_id' => $version->id,
        'attempted_changes' => $version->getDirty(),
        'ip' => request()->ip(),
        'user' => auth()->id(),
    ]);

    return false; // Block update
}
```

**Implemented Defenses:**
- Observer pattern blocks all DisclosureVersion updates
- SHA-256 hash verification on version data
- Critical log level alerts security team
- Immutable audit trail (company_lifecycle_logs)

---

### 1.3 Threat: Admin Privilege Escalation

**Scenario:**
Malicious admin approves their own disclosure or transitions their own company state.

**Attack Vector:**
- Admin user owns company account
- Admin approves own disclosure
- Admin transitions own company to live_investable
- Self-dealing opportunity

**Mitigation:**
```php
// In DisclosureReviewService
public function approveDisclosure(CompanyDisclosure $disclosure, int $adminId, ?string $notes = null) {
    // GUARD: Prevent self-approval
    if ($disclosure->company->owned_by_admin_id === $adminId) {
        Log::critical('Self-approval attempt blocked', [
            'admin_id' => $adminId,
            'company_id' => $disclosure->company_id,
        ]);
        throw new \RuntimeException('Cannot approve disclosures for companies you own');
    }

    // Proceed...
}
```

**Recommended Defenses (NOT YET IMPLEMENTED):**
- Add `owned_by_admin_id` column to companies table
- Block self-approval in DisclosureReviewService
- Require dual-approval for critical state changes
- Monitor audit logs for same-admin patterns

---

### 1.4 Threat: Bypass Via API Direct Access

**Scenario:**
Attacker bypasses frontend validation and calls API directly with crafted requests.

**Attack Vector:**
- Frontend shows "disabled" button for non-investable company
- Attacker inspects API calls
- Crafts POST /api/invest with company_id
- Bypasses UI logic

**Mitigation:**
```php
// Three-layer defense architecture
1. InvestmentPolicy->invest() - Authorization layer
2. EnsureCompanyInvestable Middleware - HTTP layer
3. CompanyLifecycleService->canAcceptInvestments() - Service layer

// Example: Investment route
Route::post('/invest/{company}', [InvestmentController::class, 'invest'])
    ->middleware(['auth', 'ensure.company.investable']) // HTTP block
    ->can('invest', 'company'); // Policy block

// Inside controller
public function invest(Company $company) {
    // Third layer - service guard
    if (!app(CompanyLifecycleService::class)->canAcceptInvestments($company)) {
        abort(403, 'Investment not allowed');
    }
}
```

**Implemented Defenses:**
- Defense-in-depth (3 layers)
- Server-side validation only (never trust client)
- All denial attempts logged

---

## 2. Edge Cases & Mitigation

### 2.1 Edge Case: Disclosure Approved While Company Suspended

**Scenario:**
Admin A suspends company. Admin B (unaware) approves disclosure for same company. Tier completion triggers lifecycle transition attempt.

**Problem:**
```
Company: suspended
Admin B approves Tier 2 disclosure
→ CompanyLifecycleService->checkAndTransition() called
→ Attempts: suspended → live_investable
→ Invalid transition!
```

**Current Behavior:**
```php
// In CompanyLifecycleService->checkAndTransition()
if ($currentState === 'live_limited' && $this->isTierComplete($company, 2)) {
    $newState = 'live_investable'; // Problem: Doesn't check if suspended
}
```

**Mitigation Required:**
```php
// Add to CompanyLifecycleService->checkAndTransition()
public function checkAndTransition(Company $company): bool {
    // GUARD: Do not auto-transition suspended companies
    if ($company->lifecycle_state === 'suspended') {
        Log::info('Skipping auto-transition for suspended company', [
            'company_id' => $company->id,
        ]);
        return false;
    }

    // Existing logic...
}
```

**Status:** ⚠️ **NOT YET IMPLEMENTED** - Add this guard before production.

---

### 2.2 Edge Case: Clarification Answered After Disclosure Rejected

**Scenario:**
Admin rejects disclosure. Company (unaware) submits clarification answer 1 minute later.

**Problem:**
```
Disclosure status: rejected
Clarification status: open
Company answers clarification
→ Clarification status: answered
→ Disclosure status still: rejected
→ Orphaned answered clarification
```

**Mitigation Required:**
```php
// In DisclosureClarification model
public function submitAnswer(int $userId, string $answerBody, ?array $documents = null): void {
    // GUARD: Check if disclosure still in valid state
    if (!in_array($this->disclosure->status, ['under_review', 'clarification_required'])) {
        throw new \RuntimeException(
            'Cannot answer clarification - disclosure is no longer under review (status: ' .
            $this->disclosure->status . ')'
        );
    }

    // Existing logic...
}
```

**Status:** ⚠️ **NOT YET IMPLEMENTED** - Add this guard.

---

### 2.3 Edge Case: Concurrent Edits During Review

**Scenario:**
Company user and admin both edit disclosure data simultaneously.

**Problem:**
```
T1: Company user reads disclosure_data (version A)
T2: Admin reads disclosure_data (version A)
T3: Company user updates field X → saves (version B)
T4: Admin updates field Y → saves (version C)
→ Company user's change to field X is lost!
```

**Mitigation:**
```php
// Use optimistic locking with version column
Schema::table('company_disclosures', function (Blueprint $table) {
    $table->unsignedInteger('lock_version')->default(0);
});

// In CompanyDisclosure model
public function updateDisclosureData(array $newData, int $userId): void {
    $currentLockVersion = $this->lock_version;

    DB::transaction(function() use ($newData, $userId, $currentLockVersion) {
        // Re-fetch with lock
        $disclosure = CompanyDisclosure::lockForUpdate()->find($this->id);

        // Check if version changed
        if ($disclosure->lock_version !== $currentLockVersion) {
            throw new \RuntimeException('Disclosure was modified by another user. Please refresh and try again.');
        }

        // Update data
        $disclosure->disclosure_data = $newData;
        $disclosure->lock_version = $currentLockVersion + 1;
        $disclosure->save();
    });
}
```

**Status:** ⚠️ **NOT YET IMPLEMENTED** - Consider for high-concurrency scenarios.

---

### 2.4 Edge Case: Version Hash Collision

**Scenario:**
Two different disclosure_data arrays produce same SHA-256 hash (astronomically unlikely but theoretically possible).

**Problem:**
```
Version 1: disclosure_data = {...}
Version 2: disclosure_data = {...different...}
→ Both generate same version_hash
→ Investors cannot distinguish which version they saw
```

**Mitigation:**
```php
// Add secondary uniqueness check
Schema::table('disclosure_versions', function (Blueprint $table) {
    $table->unique(['company_disclosure_id', 'version_hash', 'created_at']);
});

// In DisclosureVersion->createFromDisclosure()
public static function createFromDisclosure(...) {
    $hash = hash('sha256', json_encode($disclosure->disclosure_data));

    // Check for collision (paranoid check)
    $existing = static::where('company_disclosure_id', $disclosure->id)
        ->where('version_hash', $hash)
        ->where('created_at', '<', now())
        ->first();

    if ($existing && $existing->disclosure_data !== $disclosure->disclosure_data) {
        Log::critical('SHA-256 HASH COLLISION DETECTED', [
            'existing_version_id' => $existing->id,
            'new_data' => $disclosure->disclosure_data,
        ]);
        throw new \RuntimeException('Hash collision detected - contact engineering immediately');
    }
}
```

**Status:** ⚠️ **RECOMMENDED** - Add paranoid check for regulatory compliance.

---

## 3. Potential Misuse Scenarios

### 3.1 Misuse: Pump-and-Dump via State Manipulation

**Scenario:**
Malicious actor creates company, rushes through disclosure approvals, transitions to live_investable, attracts investors, then admin suspends after funds raised.

**Attack Timeline:**
```
Day 1: Register company
Day 2: Submit bare-minimum Tier 1 disclosures
Day 3: Admin approves (low scrutiny) → live_limited
Day 4: Submit bare-minimum Tier 2 financials (inflated numbers)
Day 5: Admin approves (low scrutiny) → live_investable
Day 6: Marketing blitz, raise ₹10 crores
Day 7: Admin realizes fraud, suspends company
→ Investors trapped with shares in fraudulent company
```

**Mitigation Strategies:**

1. **Dual Approval for High-Value State Changes:**
```php
// Require two admins to approve Tier 2 disclosures
public function approveDisclosure(...) {
    if ($disclosure->module->tier === 2) {
        // Check if primary approval already exists
        if ($disclosure->primary_approved_by && $disclosure->primary_approved_by !== $adminId) {
            // Second approval - proceed
        } else {
            // First approval - mark as pending secondary
            $disclosure->primary_approved_by = $adminId;
            $disclosure->status = 'pending_secondary_approval';
            $disclosure->save();
            return;
        }
    }
}
```

2. **Mandatory Review Period:**
```php
// In CompanyLifecycleService
public function transitionTo(...) {
    if ($newState === 'live_investable') {
        $company->investable_review_period_ends_at = now()->addDays(7);
        $company->buying_enabled = false; // Disabled during review period

        // Schedule job to enable buying after review period
        EnableBuyingAfterReviewPeriod::dispatch($company)
            ->delay(now()->addDays(7));
    }
}
```

3. **Investment Velocity Caps:**
```php
// Prevent >₹1 crore raised in first 48 hours
public function createSubscription(...) {
    $company = Subscription::where('company_id', $companyId)
        ->where('created_at', '>', $company->tier_2_approved_at)
        ->where('created_at', '<', now())
        ->sum('amount');

    if (now()->diffInHours($company->tier_2_approved_at) < 48 && $totalRaised > 10000000) {
        throw new \RuntimeException('Investment cap reached for new companies');
    }
}
```

**Status:** ⚠️ **NOT YET IMPLEMENTED** - Critical for investor protection.

---

### 3.2 Misuse: Serial Rejection for Competitive Advantage

**Scenario:**
Competitor's employee becomes admin. Repeatedly rejects competitor company's disclosures with nitpicky reasons to delay market entry.

**Attack Timeline:**
```
Week 1: Competitor submits Tier 2 disclosure
Week 2: Admin (insider) requests 10 clarifications (trivial)
Week 3: Competitor answers all clarifications
Week 4: Admin disputes all answers (bad faith)
Week 5-12: Repeat clarification cycles
→ Competitor delayed from reaching live_investable for 3 months
```

**Mitigation Strategies:**

1. **Clarification Dispute Limits:**
```php
// In DisclosureReviewService
public function disputeClarificationAnswer(...) {
    $disputeCount = DisclosureClarification::where('company_disclosure_id', $clarification->company_disclosure_id)
        ->where('status', 'disputed')
        ->count();

    if ($disputeCount >= 3) {
        Log::warning('Excessive disputes - escalating to senior admin', [
            'disclosure_id' => $clarification->company_disclosure_id,
            'admin_id' => $adminId,
        ]);

        // Auto-escalate to senior admin review
        $this->escalateToSeniorReview($clarification->disclosure);
    }
}
```

2. **Approval SLA Tracking:**
```php
// Monitor review duration
if ($disclosure->submitted_at->diffInDays(now()) > 30) {
    Log::warning('SLA breach: Disclosure pending >30 days', [
        'disclosure_id' => $disclosure->id,
        'admin_id' => $disclosure->review_started_by,
    ]);

    // Auto-assign to different admin
    $this->reassignReview($disclosure);
}
```

3. **Admin Performance Monitoring:**
```sql
-- Query to detect bad actors
SELECT
    reviewed_by,
    COUNT(*) as total_reviews,
    SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejections,
    AVG(DATEDIFF(approved_at, submitted_at)) as avg_review_days
FROM company_disclosures
WHERE review_started_at > DATE_SUB(NOW(), INTERVAL 90 DAY)
GROUP BY reviewed_by
HAVING (rejections / total_reviews) > 0.5
OR avg_review_days > 21;
```

**Status:** ⚠️ **RECOMMENDED** - Add before public launch.

---

### 3.3 Misuse: Disclosure Data Inflation After Approval

**Scenario:**
Company gets Tier 2 approved with realistic financials. After reaching live_investable, company wants to update numbers to inflated values to attract more investors.

**Attack Attempt:**
```
Company updates disclosure_data directly in DB (if possible)
→ Investors see inflated numbers
→ Original approved version still exists but not shown prominently
```

**Mitigation (Already Implemented):**
```php
// CompanyDisclosure model prevents editing after approval
public function updateDisclosureData(array $newData, int $userId): void {
    if ($this->is_locked) {
        throw new \RuntimeException('Cannot update locked disclosure. Submit new version for review.');
    }
    // ...
}
```

**Additional Safeguard:**
```php
// Frontend must always show approved version, not draft
public function getInvestorVisibleData() {
    return $this->currentVersion
        ? $this->currentVersion->disclosure_data
        : null; // Never show draft to investors
}
```

**Status:** ✅ **ALREADY IMPLEMENTED** - Locked disclosure cannot be edited.

---

## 4. Race Conditions

### 4.1 Race: Simultaneous State Transitions

**Scenario:**
Two disclosure approvals complete at same millisecond, both trigger checkAndTransition().

```
Thread A: Approves last Tier 1 disclosure at 14:30:15.123
Thread B: Approves another Tier 1 disclosure at 14:30:15.123
→ Both check: isTierComplete(company, 1) → TRUE
→ Both execute: transitionTo(company, 'live_limited')
→ Duplicate lifecycle log entries? Incorrect state?
```

**Mitigation:**
```php
// Use DB::transaction() with row locking
public function checkAndTransition(Company $company): bool {
    DB::beginTransaction();

    try {
        // Lock company row for duration of transaction
        $company = Company::lockForUpdate()->find($company->id);

        $currentState = $company->lifecycle_state;

        // ... check and transition logic ...

        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

**Status:** ✅ **ALREADY IMPLEMENTED** - Uses DB::transaction() in checkAndTransition().

---

### 4.2 Race: Approve + Suspend

**Scenario:**
Admin A approves disclosure (triggers auto-transition). Admin B suspends company simultaneously.

```
T1: Admin A clicks "Approve Disclosure"
T2: Admin B clicks "Suspend Company"
T3: Approval transaction starts
T4: Suspension transaction starts
T5: Approval commits → company state = live_investable
T6: Suspension commits → company state = suspended
Result: Disclosure approved but company suspended. Confusing state.
```

**Mitigation:**
```php
// Both operations lock company row
public function approveDisclosure(...) {
    DB::transaction(function() {
        $company = Company::lockForUpdate()->find($disclosure->company_id);
        // ... approval logic ...
    });
}

public function suspend(...) {
    DB::transaction(function() use ($company) {
        $company = Company::lockForUpdate()->find($company->id);
        // ... suspension logic ...
    });
}
```

**Status:** ✅ **ALREADY IMPLEMENTED** - Both use DB::transaction().

---

## 5. Data Integrity

### 5.1 Integrity: Orphaned Clarifications

**Problem:**
Disclosure deleted but clarifications remain in database.

**Mitigation:**
```php
// In migration for disclosure_clarifications
$table->foreignId('company_disclosure_id')
    ->constrained('company_disclosures')
    ->cascadeOnDelete(); // Auto-delete clarifications if disclosure deleted
```

**Status:** ✅ **ALREADY IMPLEMENTED** - Foreign key cascade in Phase 1 migration.

---

### 5.2 Integrity: Version Hash Mismatch

**Problem:**
Version hash doesn't match actual disclosure_data (data corruption).

**Detection:**
```php
// Scheduled job to verify all version hashes
public function verifyVersionIntegrity() {
    DisclosureVersion::chunk(1000, function($versions) {
        foreach ($versions as $version) {
            $expectedHash = hash('sha256', json_encode($version->disclosure_data));

            if ($version->version_hash !== $expectedHash) {
                Log::critical('Version hash mismatch - data corruption suspected', [
                    'version_id' => $version->id,
                    'expected' => $expectedHash,
                    'actual' => $version->version_hash,
                ]);

                // Trigger security incident
                $this->raiseSecurityIncident($version);
            }
        }
    });
}
```

**Status:** ⚠️ **RECOMMENDED** - Add daily integrity check job.

---

## 6. Audit & Compliance

### 6.1 Audit Requirement: Who Changed What When

**Requirement:**
SEBI auditor asks: "Who approved this disclosure and why was it approved?"

**Solution:**
```php
// Query disclosure approval trail
$disclosure = CompanyDisclosure::with([
    'currentVersion',
    'approvals.reviewedByUser',
    'clarifications',
])->find($id);

$auditTrail = [
    'submitted_by' => $disclosure->submittedByUser->name,
    'submitted_at' => $disclosure->submitted_at,
    'approved_by' => $disclosure->approvedByUser->name,
    'approved_at' => $disclosure->approved_at,
    'version_hash' => $disclosure->currentVersion->version_hash,
    'clarifications' => $disclosure->clarifications->map(...),
    'lifecycle_transitions' => $disclosure->company->lifecycleLogs->map(...),
];
```

**Status:** ✅ **SUPPORTED** - Full audit trail available via relationships.

---

### 6.2 Audit Requirement: Proof of Data at Time of Investment

**Requirement:**
Investor sues company for fraud. Court asks: "What data did investor see when they invested?"

**Solution:**
```php
// In Subscription model
protected $casts = [
    'snapshot_at_investment' => 'array',
];

// When creating subscription
public function createSubscription(...) {
    $subscription = Subscription::create([
        'user_id' => $userId,
        'company_id' => $companyId,
        'amount' => $amount,
        'snapshot_at_investment' => [
            'disclosure_versions' => $company->disclosures->map(fn($d) => [
                'module_code' => $d->module->code,
                'version_number' => $d->currentVersion->version_number,
                'version_hash' => $d->currentVersion->version_hash,
                'approved_at' => $d->currentVersion->approved_at,
            ]),
            'company_lifecycle_state' => $company->lifecycle_state,
            'company_buying_enabled' => $company->buying_enabled,
        ],
    ]);
}
```

**Status:** ⚠️ **NOT YET IMPLEMENTED** - Add snapshot to subscriptions table.

---

## 7. Operational Guidelines

### 7.1 Guideline: Suspension Process

**When to Suspend:**
- Regulatory investigation initiated
- Fraud allegations received
- Financial misstatements discovered
- Legal compliance violation
- Court order received

**How to Suspend:**
```bash
POST /api/admin/companies/{id}/suspend
{
  "public_reason": "Company is under regulatory review",
  "internal_notes": "SEBI investigation ref #12345. Contact: officer@sebi.gov.in"
}
```

**Post-Suspension Actions:**
1. Verify buying_enabled = false (system does automatically)
2. Notify all active investors via email
3. Post warning banner on company page (system does automatically)
4. Document decision in company_lifecycle_logs (system does automatically)
5. Monitor for unauthorized investment attempts (check logs daily)

---

### 7.2 Guideline: Disclosure Review SLA

**Target Timelines:**
- Tier 1 (Basic): Review within 5 business days
- Tier 2 (Financials): Review within 10 business days (critical - enables buying!)
- Tier 3 (Advanced): Review within 7 business days

**Escalation Triggers:**
- If pending >2x target timeline → Escalate to senior admin
- If >3 clarification rounds → Escalate to compliance team
- If company complains → Escalate to admin manager

**Quality Checks:**
- Tier 2 MUST have two-admin approval (implement before launch)
- Financials MUST be verified against uploaded documents
- Legal compliance MUST be checked against SEBI guidelines

---

### 7.3 Guideline: Emergency State Transitions

**Use Force Transition ONLY for:**
- Emergency fundraising (government contracts, time-sensitive deals)
- Regulatory compliance (ordered by SEBI to immediately halt)
- System bugs (incorrect auto-transition)

**Never Use for:**
- Competitive advantage
- Personal favors
- Bypassing review process

**Documentation Required:**
```bash
POST /api/admin/companies/{id}/lifecycle/transition
{
  "new_state": "live_investable",
  "reason": "Emergency transition approved by CEO. Email thread: [link]. Business justification: [detailed reason]. Risk assessment: [detailed assessment]."
}
```

**Post-Transition Actions:**
1. Email senior management with details
2. Add entry to admin audit log
3. Schedule follow-up review within 7 days

---

## 8. Testing Requirements

### 8.1 Critical Test Cases

**Must Test Before Production:**

1. **Suspension Blocks Buying:**
   - Suspend company
   - Attempt investment via API
   - Verify 403 Forbidden response
   - Verify buying_enabled = false

2. **Disclosure Immutability:**
   - Approve disclosure
   - Attempt to update version.disclosure_data directly in DB
   - Verify observer blocks update
   - Verify CRITICAL log entry created

3. **Version Hash Integrity:**
   - Create version
   - Verify hash matches data
   - Modify data in DB (bypass observer)
   - Run integrity check job
   - Verify security incident raised

4. **Tier Completion Auto-Transition:**
   - Create company in 'draft' state
   - Approve all Tier 1 disclosures
   - Verify auto-transition to 'live_limited'
   - Verify buying_enabled still false
   - Approve all Tier 2 disclosures
   - Verify auto-transition to 'live_investable'
   - Verify buying_enabled now true

5. **Concurrent Approval Race Condition:**
   - Use 2 threads to approve 2 Tier 1 disclosures simultaneously
   - Verify only 1 lifecycle transition occurs
   - Verify no duplicate lifecycle log entries

6. **Clarification Orphaning:**
   - Request clarifications
   - Reject disclosure
   - Attempt to answer clarifications
   - Verify rejection with clear error message

---

### 8.2 Load Testing

**Scenarios to Test:**
- 100 concurrent disclosure approvals
- 1000 concurrent investment attempts on same company
- Suspension during high-traffic investment period

**Metrics to Monitor:**
- Transaction deadlocks
- DB::transaction() retry counts
- Lock wait timeouts
- Log volume (ensure not DoS-ing logging system)

---

## 9. Monitoring & Alerts

### 9.1 Critical Alerts

**Configure alerts for:**

1. **Immutability Violation Attempts:**
```sql
SELECT COUNT(*) FROM logs
WHERE level='CRITICAL'
AND message LIKE '%IMMUTABILITY VIOLATION%'
AND created_at > NOW() - INTERVAL 1 HOUR;
-- Alert if count > 0
```

2. **Investment After Suspension:**
```sql
SELECT COUNT(*) FROM logs
WHERE level='CRITICAL'
AND message LIKE '%Payment processed after suspension%'
AND created_at > NOW() - INTERVAL 1 HOUR;
-- Alert if count > 0
```

3. **Excessive Clarification Disputes:**
```sql
SELECT admin_id, COUNT(*) as dispute_count
FROM disclosure_clarifications
WHERE status='disputed'
AND resolved_at > NOW() - INTERVAL 7 DAY
GROUP BY admin_id
HAVING dispute_count > 10;
-- Alert if any admin has >10 disputes/week
```

4. **SLA Breaches:**
```sql
SELECT COUNT(*) FROM company_disclosures
WHERE status IN ('submitted', 'under_review')
AND submitted_at < NOW() - INTERVAL 15 DAY;
-- Alert if count > 5
```

---

## 10. Summary

### ✅ Implemented Defenses

- Three-layer investment guards (Policy, Middleware, Service)
- Immutable disclosure versions with observer enforcement
- DB transactions with row locking for race condition prevention
- Comprehensive audit logging (all admin actions logged)
- Version hash integrity (SHA-256)
- Foreign key cascades for referential integrity

### ⚠️ Recommended Before Production

- Dual-approval for Tier 2 disclosures
- Mandatory review period before buying enabled
- Investment velocity caps for new companies
- Clarification dispute limits
- SLA breach auto-escalation
- Admin performance monitoring
- Version hash integrity check job
- Subscription snapshot at investment time
- Optimistic locking for concurrent edits
- Self-approval prevention guard

### 🔴 Critical Gaps to Address

1. Add guard in `checkAndTransition()` to skip suspended companies
2. Add guard in `submitAnswer()` to check disclosure still under review
3. Add `snapshot_at_investment` to subscriptions table
4. Implement dual-approval workflow for Tier 2
5. Add `owned_by_admin_id` to prevent self-dealing

---

**End of Document**
