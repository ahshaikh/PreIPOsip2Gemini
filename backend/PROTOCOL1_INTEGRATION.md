# PROTOCOL-1 INTEGRATION GUIDE

## Overview

Protocol-1 is the comprehensive governance enforcement framework for the PreIPOsip platform. It ensures all Phase 1-5 rules are correctly enforced across all actors, actions, and data mutations.

**Version:** 1.0.0
**Date:** 2026-01-16
**Status:** Ready for deployment

---

## Architecture Components

Protocol-1 consists of 6 core components:

### 1. Protocol1Specification
**File:** `app/Services/Protocol1/Protocol1Specification.php`
**Purpose:** Formal specification of all 19 governance rules across 6 rule sets

**Rule Sets:**
- **Platform Supremacy (Rules 1.x):** Suspension, freeze, buying controls, investigation
- **Immutability (Rules 2.x):** Snapshot and locked record protection
- **Actor Separation (Rules 3.x):** Issuer/investor/admin boundary enforcement
- **Attribution (Rules 4.x):** Explicit actor_type and audit trail requirements
- **Buy Eligibility (Rules 5.x):** 6-layer investment guard system
- **Cross-Phase (Rules 6.x):** Cross-phase enforcement coordination

### 2. Protocol1Validator
**File:** `app/Services/Protocol1/Protocol1Validator.php`
**Purpose:** Comprehensive validation engine that enforces all Protocol-1 rules

**Enforcement Modes:**
- **Strict:** Block CRITICAL and HIGH violations (Production)
- **Lenient:** Block only CRITICAL violations (Staging)
- **Monitor:** Log only, never block (Development)

### 3. Protocol1Monitor
**File:** `app/Services/Protocol1/Protocol1Monitor.php`
**Purpose:** Real-time monitoring, violation logging, and alerting

**Features:**
- Violation logging to database
- Real-time metrics tracking
- Anomaly detection
- Alert generation for critical violations
- Compliance scoring (0-100, A+ to F)

### 4. Protocol1Middleware
**File:** `app/Http/Middleware/Protocol1Middleware.php`
**Purpose:** HTTP request-level enforcement

**Middleware Alias:** `protocol1`

### 5. Database Tables
**Migration:** `database/migrations/2026_01_16_184535_create_protocol1_tables.php`

**Tables:**
- `protocol1_violation_log`: Comprehensive violation audit trail
- `protocol1_alerts`: Critical alerts requiring admin attention

### 6. Configuration
**File:** `config/protocol1.php`
**Environment Variables:** See `.env` configuration section below

---

## Installation & Setup

### Step 1: Run Database Migration

```bash
cd backend
php artisan migrate
```

This creates the Protocol-1 tables:
- `protocol1_violation_log`
- `protocol1_alerts`

### Step 2: Configure Environment Variables

Add to `.env`:

```env
# Protocol-1 Core Settings
PROTOCOL1_ENABLED=true
PROTOCOL1_ENFORCEMENT_MODE=strict  # strict | lenient | monitor

# Rule Set Toggles (granular control)
PROTOCOL1_RULE_PLATFORM_SUPREMACY=true
PROTOCOL1_RULE_IMMUTABILITY=true
PROTOCOL1_RULE_ACTOR_SEPARATION=true
PROTOCOL1_RULE_ATTRIBUTION=true
PROTOCOL1_RULE_BUY_ELIGIBILITY=true
PROTOCOL1_RULE_CROSS_PHASE=true

# Monitoring
PROTOCOL1_MONITORING_ENABLED=true
PROTOCOL1_METRICS_RETENTION=90          # days
PROTOCOL1_VIOLATION_RETENTION=730       # 2 years for compliance
PROTOCOL1_TRACK_ACTIONS=true            # For compliance scoring

# Alerting
PROTOCOL1_ALERTING_ENABLED=true
PROTOCOL1_ALERT_CRITICAL=true           # Alert on CRITICAL violations
PROTOCOL1_ALERT_ANOMALY=true            # Alert on high violation rates
PROTOCOL1_ANOMALY_THRESHOLD=10          # violations per time window
PROTOCOL1_ANOMALY_WINDOW=5              # minutes

# Alert Channels
PROTOCOL1_ALERT_LOG=true
PROTOCOL1_ALERT_DATABASE=true
PROTOCOL1_ALERT_EMAIL_ENABLED=false
PROTOCOL1_ALERT_SLACK_ENABLED=false
PROTOCOL1_ALERT_SMS_ENABLED=false

# Alert Recipients
PROTOCOL1_ALERT_EMAIL_RECIPIENTS=security@preiposip.com,admin@preiposip.com
PROTOCOL1_ALERT_SLACK_WEBHOOK=
PROTOCOL1_ALERT_SMS_RECIPIENTS=

# Compliance
PROTOCOL1_MIN_COMPLIANCE_SCORE=95       # Minimum acceptable score
PROTOCOL1_SCORE_MODE=daily              # daily | rolling

# Exception Handling
PROTOCOL1_FAIL_SAFE=environment         # block | allow | environment

# Development
PROTOCOL1_VERBOSE_LOGGING=false
PROTOCOL1_LOG_TIMING=true
PROTOCOL1_DRY_RUN=false                 # Logs what would be blocked without blocking
```

### Step 3: Clear Configuration Cache

```bash
php artisan config:clear
php artisan config:cache
```

---

## Usage Patterns

### Pattern 1: HTTP Middleware (Automatic Enforcement)

**Apply to route groups:**

```php
// In routes/api.php

// Apply to all issuer routes
Route::middleware(['auth:sanctum', 'protocol1'])->prefix('issuer')->group(function () {
    Route::put('/disclosures/{id}', [IssuerController::class, 'updateDisclosure']);
    Route::post('/disclosures/{id}/submit', [IssuerController::class, 'submitDisclosure']);
});

// Apply to all admin routes
Route::middleware(['auth:sanctum', 'protocol1'])->prefix('admin')->group(function () {
    Route::put('/companies/{id}/visibility', [AdminController::class, 'updateVisibility']);
    Route::put('/companies/{id}/platform-context', [AdminController::class, 'updatePlatformContext']);
});

// Apply to all investor routes
Route::middleware(['auth:sanctum', 'protocol1'])->prefix('investor')->group(function () {
    Route::post('/investments', [InvestorController::class, 'createInvestment']);
});
```

**With explicit actor/action override:**

```php
// Override auto-detection for specific scenarios
Route::post('/admin/companies/{id}/suspend')
    ->middleware(['auth:sanctum', 'protocol1:admin_override,suspend_company'])
    ->uses([AdminController::class, 'suspendCompany']);
```

### Pattern 2: Service-Level Validation (Explicit Calls)

**In service methods:**

```php
use App\Services\Protocol1\Protocol1Validator;
use App\Services\Protocol1\Protocol1ViolationException;

class CompanyDisclosureService
{
    protected Protocol1Validator $protocol1Validator;

    public function __construct()
    {
        $this->protocol1Validator = new Protocol1Validator();
    }

    public function updateDisclosure(int $disclosureId, array $data, User $user): array
    {
        $disclosure = CompanyDisclosure::findOrFail($disclosureId);
        $company = $disclosure->company;

        // PROTOCOL-1 VALIDATION
        try {
            $this->protocol1Validator->validate([
                'actor_type' => 'issuer',
                'action' => 'edit_disclosure',
                'company' => $company,
                'user' => $user,
                'data' => $data,
                'target_model' => 'disclosure',
                'target_id' => $disclosureId,
            ]);
        } catch (Protocol1ViolationException $e) {
            $result = $e->getValidationResult();
            throw new \RuntimeException($result['block_reason']);
        }

        // Proceed with update
        $disclosure->update(['data' => $data]);

        return ['success' => true];
    }
}
```

### Pattern 3: Action Counter (Compliance Scoring)

**Track all platform actions for compliance scoring:**

```php
use App\Services\Protocol1\Protocol1Monitor;

class CompanyDisclosureService
{
    protected Protocol1Monitor $protocol1Monitor;

    public function __construct()
    {
        $this->protocol1Monitor = new Protocol1Monitor();
    }

    public function submitDisclosure(int $disclosureId, User $user): array
    {
        // Increment action counter for compliance scoring
        $this->protocol1Monitor->incrementActionCounter();

        // ... rest of logic
    }
}
```

### Pattern 4: Manual Monitoring (Metrics & Alerts)

**Get compliance metrics:**

```php
use App\Services\Protocol1\Protocol1Monitor;

$monitor = new Protocol1Monitor();

// Get today's metrics
$metrics = $monitor->getMetrics();
// Returns:
// [
//     'date' => '2026-01-16',
//     'total_violations' => 12,
//     'by_severity' => ['CRITICAL' => 2, 'HIGH' => 5, 'MEDIUM' => 3, 'LOW' => 2],
//     'top_violated_rules' => [...],
//     'by_actor_type' => ['issuer' => 8, 'investor' => 4],
// ]

// Get compliance score
$score = $monitor->getComplianceScore();
// Returns:
// [
//     'score' => 97.5,
//     'grade' => 'A+',
//     'total_actions' => 1000,
//     'total_violations' => 25,
//     'date' => '2026-01-16',
// ]
```

---

## Deployment Strategy

### Phase 1: Development Environment (MONITOR Mode)

```env
PROTOCOL1_ENFORCEMENT_MODE=monitor
PROTOCOL1_DRY_RUN=true
PROTOCOL1_VERBOSE_LOGGING=true
```

**Purpose:** Collect baseline metrics, identify false positives

**Duration:** 1-2 weeks

**Actions:**
- Deploy Protocol-1 with monitor mode
- Review violation logs daily
- Adjust rule exceptions if needed
- Identify integration issues

### Phase 2: Staging Environment (LENIENT Mode)

```env
PROTOCOL1_ENFORCEMENT_MODE=lenient
PROTOCOL1_DRY_RUN=false
PROTOCOL1_VERBOSE_LOGGING=false
```

**Purpose:** Test blocking behavior, validate exception handling

**Duration:** 1 week

**Actions:**
- Block only CRITICAL violations
- Test all critical user flows
- Verify error messages are user-friendly
- Confirm fail-safe behavior

### Phase 3: Production Environment (STRICT Mode)

```env
PROTOCOL1_ENFORCEMENT_MODE=strict
PROTOCOL1_DRY_RUN=false
PROTOCOL1_ALERTING_ENABLED=true
PROTOCOL1_ALERT_EMAIL_ENABLED=true
```

**Purpose:** Full enforcement of all governance rules

**Actions:**
- Block CRITICAL and HIGH violations
- Monitor compliance score daily
- Investigate alerts within 24 hours
- Review metrics weekly

---

## Monitoring & Alerting

### Real-Time Monitoring

Protocol-1 automatically logs all violations to `protocol1_violation_log` table.

**Query recent violations:**

```sql
SELECT
    rule_name,
    severity,
    message,
    actor_type,
    company_id,
    was_blocked,
    created_at
FROM protocol1_violation_log
WHERE created_at >= NOW() - INTERVAL 24 HOUR
ORDER BY created_at DESC
LIMIT 50;
```

### Alert Management

Critical violations are stored in `protocol1_alerts` table.

**Query pending alerts:**

```sql
SELECT
    id,
    severity,
    title,
    message,
    is_acknowledged,
    created_at
FROM protocol1_alerts
WHERE is_acknowledged = FALSE
ORDER BY
    FIELD(severity, 'CRITICAL', 'HIGH', 'MEDIUM', 'LOW'),
    created_at DESC;
```

**Acknowledge alert:**

```sql
UPDATE protocol1_alerts
SET
    is_acknowledged = TRUE,
    acknowledged_by = :admin_user_id,
    acknowledged_at = NOW(),
    admin_notes = :notes,
    resolution_status = 'resolved'
WHERE id = :alert_id;
```

### Metrics Dashboard

Create admin dashboard endpoint:

```php
use App\Services\Protocol1\Protocol1Monitor;

Route::get('/admin/protocol1/dashboard', function () {
    $monitor = new Protocol1Monitor();

    return response()->json([
        'metrics' => $monitor->getMetrics(),
        'compliance_score' => $monitor->getComplianceScore(),
        'recent_violations' => DB::table('protocol1_violation_log')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get(),
        'pending_alerts' => DB::table('protocol1_alerts')
            ->where('is_acknowledged', false)
            ->orderByDesc('severity')
            ->orderBy('created_at')
            ->get(),
    ]);
});
```

---

## Troubleshooting

### Issue 1: False Positive Violations

**Symptom:** Legitimate actions are being blocked

**Solution:**
1. Review violation logs to identify problematic rule
2. Check if action context is correctly inferred
3. Add explicit actor_type to request:
   ```php
   $request->merge(['actor_type' => 'admin_override']);
   ```
4. Consider adding rule exception in Protocol1Specification

### Issue 2: Performance Impact

**Symptom:** Validation adds significant latency

**Solution:**
1. Enable validation caching:
   ```env
   PROTOCOL1_CACHE_VALIDATIONS=true
   PROTOCOL1_VALIDATION_CACHE_TTL=60
   ```
2. Queue violation logging:
   ```env
   PROTOCOL1_QUEUE_LOGGING=true
   ```
3. Disable verbose logging in production:
   ```env
   PROTOCOL1_VERBOSE_LOGGING=false
   ```

### Issue 3: Unexpected System Errors

**Symptom:** Protocol-1 validation throws exceptions

**Solution:**
1. Use fail-safe mode in production:
   ```env
   PROTOCOL1_FAIL_SAFE=environment  # Allows actions on validation errors
   ```
2. Review error logs: `storage/logs/laravel.log`
3. Check service availability (database, cache)

### Issue 4: Missing Violations

**Symptom:** Expected violations not being logged

**Solution:**
1. Verify Protocol-1 is enabled:
   ```env
   PROTOCOL1_ENABLED=true
   ```
2. Check if rule set is enabled:
   ```env
   PROTOCOL1_RULE_PLATFORM_SUPREMACY=true
   ```
3. Verify middleware is applied to route
4. Check enforcement mode (monitor mode never blocks)

---

## Testing

### Unit Tests

Create tests for Protocol-1 validator:

```php
use Tests\TestCase;
use App\Services\Protocol1\Protocol1Validator;
use App\Services\Protocol1\Protocol1ViolationException;
use App\Models\Company;
use App\Models\User;

class Protocol1ValidatorTest extends TestCase
{
    public function test_suspended_company_blocks_issuer_disclosure_edit()
    {
        $company = Company::factory()->create([
            'is_suspended' => true,
        ]);

        $user = User::factory()->create();

        $validator = new Protocol1Validator();

        $this->expectException(Protocol1ViolationException::class);

        $validator->validate([
            'actor_type' => 'issuer',
            'action' => 'edit_disclosure',
            'company' => $company,
            'user' => $user,
            'data' => [],
        ]);
    }

    public function test_approved_disclosure_prevents_issuer_edit()
    {
        // Create approved disclosure
        $disclosure = CompanyDisclosure::factory()->create([
            'status' => 'approved',
        ]);

        $validator = new Protocol1Validator();

        $this->expectException(Protocol1ViolationException::class);

        $validator->validate([
            'actor_type' => 'issuer',
            'action' => 'edit_disclosure',
            'company' => $disclosure->company,
            'user' => User::factory()->create(),
            'data' => [],
            'target_model' => 'disclosure',
            'target_id' => $disclosure->id,
        ]);
    }
}
```

### Integration Tests

Test middleware enforcement:

```php
use Tests\TestCase;
use App\Models\Company;
use App\Models\User;

class Protocol1MiddlewareTest extends TestCase
{
    public function test_middleware_blocks_investment_on_suspended_company()
    {
        $company = Company::factory()->create([
            'is_suspended' => true,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/investor/investments', [
                'company_id' => $company->id,
                'amount' => 10000,
            ]);

        $response->assertStatus(403);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Protocol-1 Governance Violation',
        ]);
    }
}
```

---

## Maintenance

### Regular Tasks

**Daily:**
- Review pending alerts in `protocol1_alerts`
- Check compliance score trend
- Investigate CRITICAL violations

**Weekly:**
- Review violation patterns by rule_id
- Analyze top violated rules
- Review anomaly detection alerts
- Check system performance impact

**Monthly:**
- Archive old violation logs (if >90 days and non-critical)
- Review rule effectiveness
- Update documentation
- Review and update alert thresholds

### Data Retention

Protocol-1 automatically retains:
- **Violation logs:** 2 years (compliance requirement)
- **Alerts:** Indefinitely (audit trail)

To manually clean old logs:

```sql
-- Archive violations older than 2 years
DELETE FROM protocol1_violation_log
WHERE created_at < NOW() - INTERVAL 2 YEAR
AND severity NOT IN ('CRITICAL', 'HIGH');
```

---

## Support & Documentation

**Questions or Issues?**
- Review this guide
- Check violation logs in database
- Review Laravel logs: `storage/logs/laravel.log`
- Contact platform security team

**Further Reading:**
- `app/Services/Protocol1/Protocol1Specification.php` - Full rule definitions
- `app/Services/Protocol1/Protocol1Validator.php` - Validation logic
- `app/Services/Protocol1/Protocol1Monitor.php` - Monitoring implementation
- `config/protocol1.php` - Configuration options

---

**Protocol-1 Version:** 1.0.0
**Last Updated:** 2026-01-16
**Status:** Production Ready
