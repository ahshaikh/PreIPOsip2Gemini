# SYSTEM ARCHITECTURE: Unified Authority Model
## Meta-Fix I.28: From Module Correctness to System Correctness

**Date:** 2025-12-28
**Principle:** "A system is only as coherent as its authority structure"

---

## PROBLEM: Fragmentation

### Before (Module Correctness)
Each service solved its own problem correctly, but created system-level fragmentation:

```
SystemHealthMonitoringService
  └─ assessEconomicImpact($amount, $hours, $users) ❌

StuckStateDetectorService
  └─ assessAlertEconomicImpact($alert) ❌

AlertRootCauseAnalyzer
  └─ calculateSeverity($count, $amount, $users) ❌

❌ THREE different implementations of "assess severity/impact"
❌ Thresholds can drift independently
❌ No single source of truth
❌ Each module "correct" but system fragmented
```

### Symptoms of Fragmentation
- **Logic Duplication:** Same thresholds defined in 3 places
- **Drift Risk:** Changes to one service don't propagate
- **Inconsistency:** Same inputs → different outputs (eventually)
- **No Authority:** "Who decides what CRITICAL means?"
- **Testing Nightmare:** Must test same logic 3x

---

## SOLUTION: Unified Authority

### After (System Correctness)
Single source of truth with clear delegation hierarchy:

```
EconomicImpactService (AUTHORITY)
  └─ assessByValues($amount, $hours, $users) ✓ SINGLE TRUTH
  └─ assessByEntity($type, $id) ✓ CONVENIENCE METHOD
  └─ assessByAlert($alert) ✓ CONVENIENCE METHOD

SystemHealthMonitoringService (SPECIALIST)
  └─ checkAllMetrics()
  └─ DELEGATES to → EconomicImpactService

StuckStateDetectorService (SPECIALIST)
  └─ detectAllStuckStates()
  └─ DELEGATES to → EconomicImpactService

AlertRootCauseAnalyzer (SPECIALIST)
  └─ identifyRootCauses()
  └─ DELEGATES to → EconomicImpactService

SystemIntegrityService (COORDINATOR)
  └─ getSystemStatus()
  └─ COORDINATES all specialists
```

---

## ARCHITECTURAL PRINCIPLES

### 1. Single Authority Per Domain

**RULE:** Each domain has exactly ONE authoritative service

| Domain | Authority | Responsibility |
|--------|-----------|----------------|
| **Economic Impact Assessment** | `EconomicImpactService` | Assess severity, calculate SLA, determine auto-fix eligibility |
| **Health Monitoring** | `SystemHealthMonitoringService` | Collect metrics, create alerts, track trends |
| **Stuck State Detection** | `StuckStateDetectorService` | Detect timeouts, execute auto-resolution, escalate |
| **Root Cause Analysis** | `AlertRootCauseAnalyzer` | Group alerts, identify patterns, track systemic issues |
| **Financial Integrity** | `LedgerReconciliationService` | Verify balances, detect mismatches, ensure double-entry |
| **Tax Compliance** | `TdsEnforcementService` | Calculate TDS, enforce deductions, track exemptions |
| **System Coordination** | `SystemIntegrityService` | Orchestrate all services, provide unified entry points |

### 2. Delegation, Not Duplication

**RULE:** Specialists delegate to authorities, never duplicate logic

**GOOD (Delegation):**
```php
class SystemHealthMonitoringService
{
    private EconomicImpactService $economicImpact;

    public function checkStuckPayments()
    {
        $impactLevel = $this->economicImpact->assessByValues($amount, $hours, $users);
        // ✓ Delegates to authority
    }
}
```

**BAD (Duplication):**
```php
class SystemHealthMonitoringService
{
    public function checkStuckPayments()
    {
        // ❌ Duplicates authority's logic
        if ($amount > 500000 || $hours > 168 || $users > 100) {
            $impactLevel = 'CRITICAL';
        }
    }
}
```

### 3. Coordination Over Independence

**RULE:** Services don't operate in isolation, they coordinate through SystemIntegrityService

**GOOD (Coordinated):**
```php
// Single entry point, coordinated workflow
$integrity = app(SystemIntegrityService::class);
$status = $integrity->getSystemStatus(); // Coordinates all services
```

**BAD (Fragmented):**
```php
// Multiple independent calls, no coordination
$healthService->checkAllMetrics();
$detectorService->detectAllStuckStates();
$analyzerService->identifyRootCauses();
// ❌ Services operate independently, no coordination
```

---

## AUTHORITY HIERARCHY

```
┌─────────────────────────────────────────────────────────────┐
│ SystemIntegrityService (TOP-LEVEL COORDINATOR)              │
│ - getSystemStatus()                                         │
│ - detectAndAnalyzeIssues()                                  │
│ - executeGovernanceChecks()                                 │
│ - generateExecutiveSummary()                                │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ COORDINATES
                            ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                         UNIFIED AUTHORITIES                                  │
├──────────────────────────────────────────────────────────────────────────────┤
│ EconomicImpactService (Impact Assessment Authority)                         │
│ - assessByValues($amount, $hours, $users) → CRITICAL|HIGH|MEDIUM|LOW        │
│ - assessByEntity($type, $id)                                                │
│ - assessByAlert($alert)                                                     │
│ - getSLA($impactLevel) → minutes                                            │
│ - isAutoFixEligible($impactLevel) → bool                                    │
│ - getThresholds() → array                                                   │
└──────────────────────────────────────────────────────────────────────────────┘
                            │
                            │ DELEGATED TO BY
                            ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                          SPECIALIST SERVICES                                 │
├──────────────────────────────────────────────────────────────────────────────┤
│ SystemHealthMonitoringService                                               │
│ - checkFinancialHealth()         → delegates to EconomicImpactService       │
│ - checkOperationalHealth()       → delegates to EconomicImpactService       │
│ - checkSystemHealth()                                                       │
│                                                                              │
│ StuckStateDetectorService                                                   │
│ - detectStuckPayments()          → delegates to EconomicImpactService       │
│ - detectStuckInvestments()       → delegates to EconomicImpactService       │
│ - autoResolveStuckStates()       → delegates to EconomicImpactService       │
│                                                                              │
│ AlertRootCauseAnalyzer                                                      │
│ - identifyRootCauses()           → delegates to EconomicImpactService       │
│ - createOrUpdateRootCause()      → delegates to EconomicImpactService       │
│                                                                              │
│ LedgerReconciliationService                                                 │
│ - findAllWalletMismatches()                                                 │
│ - reconcileLedgers()                                                        │
│                                                                              │
│ TdsEnforcementService                                                       │
│ - calculateTds()                                                            │
│ - enforceTdsDeduction()                                                     │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## OWNERSHIP BOUNDARIES

### Clear Ownership Rules

| What | Owner | Others May |
|------|-------|------------|
| **Impact thresholds (₹10k, ₹100k, etc.)** | `EconomicImpactService` | READ ONLY via `getThresholds()` |
| **Impact assessment logic (OR vs AND)** | `EconomicImpactService` | NEVER duplicate, always delegate |
| **SLA definitions (15min, 1h, etc.)** | `EconomicImpactService` | READ ONLY via `getSLA()` |
| **Auto-fix eligibility (LOW only)** | `EconomicImpactService` | READ ONLY via `isAutoFixEligible()` |
| **Health metric collection** | `SystemHealthMonitoringService` | NEVER duplicate, use via coordinator |
| **Stuck state detection logic** | `StuckStateDetectorService` | NEVER duplicate, use via coordinator |
| **Root cause pattern detection** | `AlertRootCauseAnalyzer` | NEVER duplicate, use via coordinator |
| **System coordination** | `SystemIntegrityService` | NEVER bypass, always entry point |

---

## MIGRATION GUIDE

### How to Fix Fragmented Code

#### Pattern 1: Remove Duplicated Assessment Logic

**BEFORE:**
```php
class SomeService
{
    private function assessImpact($amount, $hours, $users)
    {
        if ($amount > 500000 || $hours > 168 || $users > 100) {
            return 'CRITICAL';
        }
        // ... more duplication
    }
}
```

**AFTER:**
```php
class SomeService
{
    private EconomicImpactService $economicImpact;

    public function __construct(EconomicImpactService $economicImpact)
    {
        $this->economicImpact = $economicImpact;
    }

    private function assessImpact($amount, $hours, $users)
    {
        // DELEGATED to authority
        return $this->economicImpact->assessByValues($amount, $hours, $users);
    }
}
```

#### Pattern 2: Use Coordinator Instead of Direct Service Calls

**BEFORE:**
```php
// Controller calling services directly (fragmented)
public function getDashboard()
{
    $health = app(SystemHealthMonitoringService::class)->checkAllMetrics();
    $stuck = app(StuckStateDetectorService::class)->detectAllStuckStates();
    $causes = app(AlertRootCauseAnalyzer::class)->identifyRootCauses();

    // Manual coordination ❌
    return view('dashboard', compact('health', 'stuck', 'causes'));
}
```

**AFTER:**
```php
// Controller calling coordinator (unified)
public function getDashboard()
{
    $integrity = app(SystemIntegrityService::class);

    // Coordinated workflow ✓
    $status = $integrity->getSystemStatus();

    return view('dashboard', ['status' => $status]);
}
```

#### Pattern 3: Remove Hardcoded Thresholds

**BEFORE:**
```php
// Hardcoded thresholds ❌
if ($amount > 10000) { // Where did 10000 come from?
    return 'medium';
}
```

**AFTER:**
```php
// Use authority's thresholds ✓
$thresholds = $this->economicImpact->getThresholds();
if ($amount > $thresholds['MEDIUM']['amount']) {
    return 'medium';
}

// Or better yet, just delegate:
return $this->economicImpact->assessByValues($amount, 0, 1);
```

---

## TESTING STRATEGY

### Test Authorities, Not Duplicates

**BEFORE (Fragmented):**
```
TestSystemHealthMonitoringService::testAssessEconomicImpact()
TestStuckStateDetectorService::testAssessAlertEconomicImpact()
TestAlertRootCauseAnalyzer::testCalculateSeverity()

❌ Same logic tested 3 times
❌ Thresholds can drift across tests
```

**AFTER (Unified):**
```
TestEconomicImpactService::testAssessByValues()
  - Test all threshold combinations once
  - Single source of truth for test cases

TestSystemHealthMonitoringService::testDelegation()
  - Mock EconomicImpactService
  - Verify delegation happens correctly

✓ Authority tested thoroughly once
✓ Specialists test delegation, not logic
```

---

## ANTI-PATTERNS TO AVOID

### 1. ❌ "I'll just copy this logic, it's small"

**NO.** Even small logic should have single authority.

```php
// ❌ DON'T DO THIS
if ($amount > 10000) { // Copied from somewhere else
    $severity = 'high';
}

// ✓ DO THIS
$severity = $this->economicImpact->assessByValues($amount, 0, 1);
```

### 2. ❌ "Each service should be independent"

**NO.** Independence → fragmentation. Services should coordinate.

```php
// ❌ DON'T DO THIS
class NewService
{
    public function doSomething()
    {
        // I'll just implement my own impact assessment
        if ($this->isHighImpact($data)) {
            // ...
        }
    }
}

// ✓ DO THIS
class NewService
{
    private EconomicImpactService $economicImpact;

    public function doSomething()
    {
        $impact = $this->economicImpact->assessByValues(...);
        if ($impact === 'HIGH' || $impact === 'CRITICAL') {
            // ...
        }
    }
}
```

### 3. ❌ "I don't want a dependency on EconomicImpactService"

**TOO BAD.** Dependency injection is how we enforce unified authority.

```php
// ❌ DON'T DO THIS (avoiding dependency)
class NewService
{
    public function assess($amount)
    {
        // I'll hardcode to avoid dependency
        return $amount > 100000 ? 'high' : 'low';
    }
}

// ✓ DO THIS (embrace dependency)
class NewService
{
    private EconomicImpactService $economicImpact;

    public function __construct(EconomicImpactService $economicImpact)
    {
        $this->economicImpact = $economicImpact;
    }

    public function assess($amount)
    {
        return $this->economicImpact->assessByValues($amount, 0, 1);
    }
}
```

---

## BENEFITS OF UNIFIED AUTHORITY

### 1. Single Source of Truth
- Change thresholds once → applies everywhere
- No drift between services
- Clear ownership

### 2. Easier Testing
- Test authority thoroughly once
- Specialists just test delegation
- Mock dependencies cleanly

### 3. Easier Reasoning
- "Where does economic impact get assessed?" → ONE place
- "Who decides what CRITICAL means?" → ONE service
- No hunting through codebase

### 4. Easier Evolution
- New threshold? → Change in ONE place
- New severity level? → Add to authority, delegators update automatically
- Refactor impact logic? → Touch ONE service

### 5. Prevents Inconsistency
- Same inputs → ALWAYS same output
- No "this service says HIGH, that service says MEDIUM"
- Deterministic behavior

---

## GOVERNANCE CHECKLIST

Before merging any PR, verify:

- [ ] No duplicated assessment logic (grep for threshold values)
- [ ] All services delegate to authorities (no local calculation)
- [ ] New services use SystemIntegrityService coordinator
- [ ] Tests cover delegation, not duplicated logic
- [ ] Documentation updated to reflect ownership boundaries

---

## CONCLUSION

**The Goal:** System correctness over module correctness

**The Principle:** "A system is only as coherent as its authority structure"

**The Practice:**
1. Define clear authorities (one per domain)
2. Enforce delegation (no duplication)
3. Coordinate through SystemIntegrityService (no fragmentation)

**The Result:** A system that works as a coherent whole, not a collection of isolated parts.

---

**Version:** 1.0
**Last Updated:** 2025-12-28
**Next Review:** When adding new governance services (document authority first)
