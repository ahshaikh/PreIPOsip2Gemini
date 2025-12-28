# Operational Runbook: Governance & Monitoring System
## Production Deployment and Operations Guide

**Date:** 2025-12-28
**Status:** Pre-Production Deployment Requirements
**Purpose:** This system is **NOT** "drop into prod and forget". This runbook defines the **required** training, procedures, and thresholds before production deployment.

---

## EXECUTIVE SUMMARY

**THIS DOCUMENT IS MANDATORY READING BEFORE ENABLING:**
- Auto-resolution of stuck states
- System health monitoring dashboards
- Alert notification systems
- Admin action constraints

**DANGER ZONES:**
- Auto-fix can move money without human approval (if enabled)
- Dashboards can create false confidence if not understood correctly
- Alerts can be ignored if admins are not trained on severity levels
- Thresholds are conservative defaults and **MUST** be tuned to your actual traffic patterns

---

## SECTION 1: PRE-DEPLOYMENT CHECKLIST

### **CRITICAL: DO NOT ENABLE UNTIL ALL ITEMS COMPLETED**

- [ ] **Admin Training:** All admins completed governance training (Section 2)
- [ ] **Escalation Playbooks:** All playbooks reviewed and assigned (Section 3)
- [ ] **Threshold Tuning:** Production thresholds validated (Section 4)
- [ ] **Monitoring Setup:** External monitoring configured (Section 5)
- [ ] **Kill Switch Test:** Verified auto-resolution can be disabled instantly (Section 6)
- [ ] **Dry Run:** Ran detection-only mode for 7 days without auto-resolution (Section 7)
- [ ] **Legal Review:** Legal team approved cancellation/refund policies (Section 8)
- [ ] **Runbook Drill:** Ran at least one mock escalation drill (Section 9)

---

## SECTION 2: ADMIN TRAINING REQUIREMENTS

### **2.1 Training Modules (Mandatory for All Admins)**

#### **Module 1: Understanding System Boundaries (2 hours)**
**Learning Objectives:**
- Define what is "internal" vs "external" to PreIPOsip
- Identify reconciliation ownership at each boundary
- Understand settlement-based trust vs authorization-based trust

**Required Reading:**
- `SYSTEM_BOUNDARIES_DEFINITION.md` (full document)
- Case Study: "Why pending ≠ reversible" (Razorpay chargebacks)

**Assessment:**
- Admin must correctly classify 10 integration scenarios
- Pass threshold: 9/10 correct

#### **Module 2: Economic Impact Assessment (1.5 hours)**
**Learning Objectives:**
- Read dashboard metrics with economic context
- Distinguish between "12 stuck payments" vs "₹4.2L stuck for 27h affecting 8 users"
- Assess "How bad if we do nothing?" for any alert

**Required Reading:**
- Dashboard interpretation guide (Section 2.2 below)
- Economic impact matrix (Section 2.3 below)

**Assessment:**
- Given 5 dashboard states, admin must correctly prioritize response order
- Pass threshold: 5/5 correct

#### **Module 3: Auto-Fix Dangers and Safeguards (2 hours)**
**Learning Objectives:**
- Understand when auto-fix is safe vs dangerous
- Recognize scenarios that require manual intervention
- Know how to disable auto-fix (kill switch)

**Required Reading:**
- `CRITICAL_SAFETY_ADDENDUM.md` (full document)
- Auto-resolution safeguard system (Section 3.2 below)

**Assessment:**
- Admin must identify 8/10 scenarios as "auto-fix safe" vs "manual review required"
- Admin must demonstrate kill switch activation in <60 seconds

#### **Module 4: Escalation Procedures (1 hour)**
**Learning Objectives:**
- Follow correct escalation chain for each severity level
- Know when to wake up on-call engineer vs wait for business hours
- Understand compensation vs retry coordination

**Required Reading:**
- Escalation playbooks (Section 3 below)
- On-call rotation schedule

**Assessment:**
- Admin must correctly respond to 5 escalation scenarios
- Pass threshold: 5/5 correct

### **2.2 Dashboard Interpretation Guide**

#### **WRONG: Counting Problems**
```
Dashboard says: "12 stuck payments"
Admin thinks: "Not that many, I'll check later"
Reality: ₹4.2L stuck, 8 users can't invest, complaints incoming
```

#### **RIGHT: Assessing Impact**
```
Dashboard says: "₹4.2 lakh stuck for 27 hours affecting 8 users (12 payments). Impact: HIGH - Manual intervention needed"
Admin thinks: "HIGH impact, ₹4.2L is material, 27h is too long, need to act NOW"
Action: Check payment gateway status, manually process if safe
```

#### **Dashboard Metrics to Always Check Together:**
1. **Monetary Exposure:** How much money is at risk?
2. **Time-Weighted Risk:** How long has this been stuck?
3. **User Impact:** How many users are affected?
4. **Economic Impact:** What happens if we do nothing?

#### **Severity Interpretation Table:**
| Impact Level | Monetary Exposure | Time Stuck | Users Affected | Response SLA | Who Responds |
|--------------|-------------------|------------|----------------|--------------|--------------|
| **LOW** | <₹10k | <12h | <5 users | 24 hours | Any admin |
| **MEDIUM** | ₹10k-100k | 12-48h | 5-20 users | 4 hours | Finance admin |
| **HIGH** | ₹100k-500k | 48-168h | 20-100 users | 1 hour | Finance lead |
| **CRITICAL** | >₹500k | >168h (7d) | >100 users | 15 minutes | On-call engineer + Finance lead |

### **2.3 Economic Impact Assessment Matrix**

**Purpose:** Train admins to ask "How bad if we do nothing?"

| Scenario | Count | Economic Impact | "If We Do Nothing" |
|----------|-------|-----------------|---------------------|
| **12 stuck payments of ₹500 each (₹6k total), 6h stuck, 8 users** | 12 | LOW | Users will complain, but small amounts, can wait for business hours |
| **3 stuck payments of ₹50k each (₹150k total), 30h stuck, 3 users** | 3 | HIGH | Material amount stuck, users likely filed complaints, URGENT |
| **50 stuck payments of ₹2k each (₹100k total), 72h stuck, 40 users** | 50 | HIGH | Large user base affected, social media risk, URGENT |
| **1 stuck payment of ₹10M, 10 days stuck, 1 corporate user** | 1 | CRITICAL | Existential risk, legal liability, IMMEDIATE ESCALATION |

**Training Exercise:**
- Given 10 dashboard states, admin must correctly assess economic impact
- Admin must correctly prioritize which to fix first
- Admin must correctly decide auto-fix vs manual review

---

## SECTION 3: ESCALATION PLAYBOOKS

### **3.1 Escalation Chain**

```
┌─────────────────────────────────────────────────┐
│         CRITICAL ALERT DETECTED                 │
└──────────────┬──────────────────────────────────┘
               │
               ▼
     ┌─────────────────┐
     │ Impact: LOW?    │─── YES ──→ Auto-fix eligible (if enabled)
     └────────┬────────┘           OR Admin dashboard queue
              │ NO
              ▼
     ┌─────────────────┐
     │ Impact: MEDIUM? │─── YES ──→ Finance Admin (4h SLA)
     └────────┬────────┘           Investigate + Manual fix
              │ NO
              ▼
     ┌─────────────────┐
     │ Impact: HIGH?   │─── YES ──→ Finance Lead (1h SLA)
     └────────┬────────┘           Immediate investigation
              │ NO                  Call payment gateway if needed
              ▼
     ┌─────────────────┐
     │ CRITICAL        │────────→ On-Call Engineer (15min SLA)
     └─────────────────┘           + Finance Lead
                                   + Notify CTO
                                   + Consider external escalation
```

### **3.2 Playbook 1: Stuck Payment (Pending >24h)**

**Trigger:** Payment stuck in "pending" status for more than 24 hours

**STEP 1: Assess Economic Impact**
- Check monetary exposure (payment amount)
- Check time stuck (hours in pending state)
- Check user impact (user complaints, support tickets)
- Determine severity: LOW / MEDIUM / HIGH / CRITICAL

**STEP 2: Verify Payment Gateway Status**
```bash
# Check Razorpay payment status
php artisan check:payment-status {payment_id}

# Possible statuses:
# - "created" → Payment never attempted by user → SAFE TO CANCEL
# - "authorized" → Payment authorized but not captured → CHECK SETTLEMENT
# - "captured" → Payment captured → CHECK SETTLEMENT STATUS
# - "failed" → Payment failed → SAFE TO MARK AS FAILED
```

**STEP 3: Decision Tree**

```
Gateway Status: "created"
  → Action: Cancel payment, notify user to retry
  → Risk: NONE (user never paid)
  → Auto-fix: SAFE

Gateway Status: "failed"
  → Action: Mark as failed, notify user
  → Risk: NONE
  → Auto-fix: SAFE

Gateway Status: "authorized" OR "captured"
  → CHECK SETTLEMENT STATUS
  → If NOT settled:
     → Action: Provisional credit to wallet (reversible)
     → Risk: MEDIUM (gateway might reverse later)
     → Auto-fix: NO (manual review required)
  → If settled:
     → Action: Permanent credit to wallet
     → Risk: LOW (money actually transferred)
     → Auto-fix: ONLY IF amount <₹10k AND <12h stuck

Gateway Status: "unknown" (API error)
  → Action: DO NOT AUTO-FIX
  → Escalate to manual review
  → Risk: HIGH (uncertainty)
```

**STEP 4: Execute Action**
- If auto-fix safe: Enable auto-resolution (via admin panel)
- If manual review required: Assign to finance admin
- Document decision rationale in alert notes

**STEP 5: User Communication**
- If cancelling: Send cancellation email with retry link
- If processing: Send "We're investigating" email with ETA
- If refunding: Send refund confirmation with expected timeline

**STEP 6: Root Cause Analysis**
- Why did payment get stuck?
- Webhook missed? Gateway downtime? Bug in our code?
- Update playbook with learnings

### **3.3 Playbook 2: Wallet Balance Mismatch**

**Trigger:** Stored wallet balance ≠ computed balance from transactions

**STEP 1: Assess Discrepancy Severity**
- Total monetary discrepancy: ₹X
- Number of affected wallets: N
- Economic impact: LOW / MEDIUM / HIGH / CRITICAL

**STEP 2: Investigate Root Cause**
```bash
# Check transaction history for affected wallet
php artisan wallet:audit {wallet_id}

# Possible causes:
# - Transaction reversal not recorded
# - Double-entry bookkeeping violation
# - Concurrency bug (race condition)
# - Manual admin adjustment without audit trail
```

**STEP 3: Verify Transaction Integrity**
- All credits have matching debits?
- All reversals properly recorded?
- All transactions have paired_transaction_id (where required)?

**STEP 4: Decision Tree**

```
Discrepancy <₹100
  → Likely rounding error or minor bug
  → Auto-fix: NO (investigate first)
  → Log for pattern analysis

Discrepancy ₹100-₹1,000
  → Investigate transaction history
  → If cause identified and safe: Manual fix with approval token
  → If cause unknown: Escalate to engineering

Discrepancy >₹1,000
  → CRITICAL: Freeze affected wallet
  → Escalate to Finance Lead + CTO
  → Full forensic audit required
  → Legal review if fraud suspected
```

**STEP 5: Fix (Only If Approved)**
```bash
# Generate approval token (5-minute expiry)
php artisan wallet:generate-approval-token {wallet_id} --reason="..." --approver={admin_id}

# Execute fix with approval token
php artisan wallet:fix-balance {wallet_id} --approval-token={token}
```

**STEP 6: Post-Fix Verification**
- Recompute balance from transactions
- Verify stored balance now matches
- Audit log reviewed by second admin
- User notified if balance changed

### **3.4 Playbook 3: Alert Fatigue (Too Many Alerts)**

**Trigger:** >100 unresolved alerts OR >50 alerts in 1 hour

**STEP 1: Assess if This is a Systemic Issue**
- Are all alerts of same type? (e.g., all stuck payments)
- Are all alerts from same root cause? (e.g., payment gateway outage)
- Is this a pattern or isolated spike?

**STEP 2: Root Cause First, Not Alert Suppression**
```
BAD RESPONSE: "Too many alerts, let me disable alerting"
GOOD RESPONSE: "Too many alerts, there's a systemic issue I need to fix"
```

**STEP 3: Group Alerts by Root Cause**
```bash
# View alert aggregation
php artisan alerts:aggregate

# Expected output:
# ROOT CAUSE: Payment gateway timeout
#   - 47 stuck payments (₹2.3L total)
#   - Started: 2h ago
#   - Action: Check Razorpay status page

# ROOT CAUSE: Inventory allocation service down
#   - 23 stuck allocations (₹1.1L total)
#   - Started: 30min ago
#   - Action: Restart allocation worker
```

**STEP 4: Fix Root Cause, Not Symptoms**
- Payment gateway down? → Wait for recovery, process queue after
- Our service down? → Restart service, retry failed jobs
- Bug in code? → Deploy hotfix, retry affected entities

**STEP 5: Prevent Future Alert Fatigue**
- Implement alert aggregation (group similar alerts)
- Implement alert escalation (only escalate if not resolved in X time)
- Implement alert suppression (if root cause known, suppress similar alerts)

---

## SECTION 4: THRESHOLD TUNING

### **4.1 Default Thresholds (Conservative)**

**These are STARTING POINTS. You MUST tune them based on your actual traffic patterns.**

```php
// Auto-Resolution Settings
'allow_auto_resolution' => false, // DISABLED by default
'max_auto_resolutions_per_hour' => 5, // Very conservative
'max_auto_resolutions_per_day' => 20,
'max_auto_resolution_value' => 1000, // ₹1,000 max

// Stuck State Detection Thresholds
'stuck_payment_threshold_hours' => 24, // Payments pending >24h
'stuck_investment_threshold_minutes' => 30, // Allocations processing >30min
'stuck_bonus_threshold_hours' => 2, // Bonuses uncredited >2h

// Economic Impact Thresholds
'economic_impact_low_amount' => 10000, // <₹10k
'economic_impact_medium_amount' => 100000, // ₹10k-100k
'economic_impact_high_amount' => 500000, // ₹100k-500k
'economic_impact_critical_amount' => 500000, // >₹500k

'economic_impact_low_time' => 12, // <12h
'economic_impact_medium_time' => 48, // 12-48h
'economic_impact_high_time' => 168, // 48-168h (7 days)

'economic_impact_low_users' => 5, // <5 users
'economic_impact_medium_users' => 20, // 5-20 users
'economic_impact_high_users' => 100, // 20-100 users
```

### **4.2 Threshold Tuning Process**

#### **Phase 1: Detection-Only (7 days minimum)**
**Purpose:** Understand your baseline traffic patterns

**Actions:**
1. Enable stuck state detection (without auto-resolution)
2. Monitor alert volume for 7 days
3. Analyze alert distribution by severity
4. Identify false positives

**Metrics to Track:**
```bash
# Daily alert summary
php artisan alerts:summary --last=7days

# Expected output:
# Day 1: 12 LOW, 3 MEDIUM, 0 HIGH, 0 CRITICAL
# Day 2: 15 LOW, 5 MEDIUM, 1 HIGH, 0 CRITICAL
# ...
# Average: 13 LOW, 4 MEDIUM, 0.3 HIGH, 0 CRITICAL
```

**Tuning Decisions:**
- If >50 LOW alerts per day → Increase "stuck" thresholds (maybe 36h instead of 24h)
- If 0 HIGH/CRITICAL alerts ever → Thresholds might be too loose
- If >10 false positives per day → Tighten detection logic

#### **Phase 2: Manual Resolution (14 days minimum)**
**Purpose:** Train admins on real scenarios before enabling auto-fix

**Actions:**
1. Admins manually review and resolve all alerts
2. Document resolution patterns
3. Identify which scenarios are consistently "safe to auto-fix"

**Metrics to Track:**
```bash
# Resolution pattern analysis
php artisan alerts:resolution-patterns --last=14days

# Expected output:
# Stuck payments (pending >24h):
#   - 87% resolved by "cancel and notify user" (SAFE)
#   - 13% required manual investigation (NOT SAFE)
#
# Stuck investments (processing >30min):
#   - 92% resolved by "retry allocation" (SAFE)
#   - 8% required inventory adjustment (NOT SAFE)
```

**Tuning Decisions:**
- If >95% of LOW alerts resolve the same way → Safe to enable auto-fix for that type
- If <80% consistency → Keep manual review

#### **Phase 3: Limited Auto-Fix (30 days minimum)**
**Purpose:** Test auto-fix in production with tight limits

**Actions:**
1. Enable auto-resolution with very conservative limits:
   - Max 5 resolutions/hour
   - Max 20 resolutions/day
   - Max ₹1,000 per resolution
   - Only LOW impact
2. Monitor for 30 days
3. Review every auto-fixed alert manually

**Metrics to Track:**
```bash
# Auto-fix effectiveness
php artisan alerts:auto-fix-report --last=30days

# Expected output:
# Auto-fixed: 127 alerts
# Success rate: 98.4% (125/127)
# Failed: 2 (both escalated correctly)
# False positives: 0
# User complaints: 0
```

**Tuning Decisions:**
- If success rate >98% for 30 days → Increase limits cautiously
- If any user complaints → Disable and investigate
- If false positives → Tighten severity gating

#### **Phase 4: Production Auto-Fix (Ongoing)**
**Purpose:** Gradual increase of auto-fix capacity

**Actions:**
1. Increase limits by 20% per month (if success rate >98%)
2. Continuously monitor for edge cases
3. Update playbooks with new learnings

**Final Production Thresholds (Example):**
```php
'allow_auto_resolution' => true, // Enabled after 90 days testing
'max_auto_resolutions_per_hour' => 20, // Increased from 5
'max_auto_resolutions_per_day' => 100, // Increased from 20
'max_auto_resolution_value' => 5000, // Increased from ₹1,000
```

### **4.3 Threshold Monitoring and Alerts**

**CRITICAL: Monitor the Monitors**

Set up external monitoring (e.g., DataDog, New Relic) to alert if:
- Auto-resolution disabled unexpectedly
- Alert volume spikes >2x normal
- Critical alerts not acknowledged within SLA
- Dashboard query times >5s (performance degradation)

---

## SECTION 5: EXTERNAL MONITORING SETUP

**DO NOT RELY ONLY ON INTERNAL DASHBOARDS**

### **5.1 Required External Monitors**

#### **Monitor 1: Governance System Health**
```yaml
# DataDog/New Relic monitor config
name: "Governance System Health Check"
query: "SELECT 1 FROM system_health_metrics WHERE last_checked_at > NOW() - INTERVAL 10 MINUTE"
threshold:
  critical: No results in last 10 minutes
  warning: No results in last 5 minutes
alert:
  - on-call-engineer
  - finance-lead
```

#### **Monitor 2: Critical Alert SLA Breach**
```yaml
name: "Critical Alert Not Acknowledged"
query: "SELECT COUNT(*) FROM stuck_state_alerts WHERE severity='critical' AND reviewed=false AND created_at < NOW() - INTERVAL 15 MINUTE"
threshold:
  critical: >0
alert:
  - on-call-engineer
  - cto
```

#### **Monitor 3: Auto-Fix Failure Rate**
```yaml
name: "Auto-Fix Failure Rate Spike"
query: "SELECT (failed / resolved) * 100 FROM auto_resolution_stats WHERE timestamp > NOW() - INTERVAL 1 HOUR"
threshold:
  critical: >5% failure rate
  warning: >2% failure rate
alert:
  - finance-lead
```

---

## SECTION 6: KILL SWITCH PROCEDURES

### **6.1 When to Activate Kill Switch**

**ACTIVATE IMMEDIATELY IF:**
- Auto-fix incorrectly moved money (even once)
- User complained about unauthorized wallet adjustment
- Alert volume spike suggests systemic issue
- Unsure if auto-fix is safe for a new scenario

**HOW TO ACTIVATE:**
```bash
# Method 1: Admin panel (preferred)
Navigate to: /admin/settings/system
Toggle: "Allow Auto-Resolution" → OFF
Confirm: "Disable all auto-resolution immediately"

# Method 2: Database direct (if admin panel down)
UPDATE settings SET value = '0' WHERE key = 'allow_auto_resolution';

# Method 3: Laravel tinker (if database locked)
php artisan tinker
>>> setting()->put('allow_auto_resolution', false);

# Verify kill switch active
php artisan system:health --json | grep allow_auto_resolution
# Expected: "allow_auto_resolution": false
```

### **6.2 Post Kill-Switch Procedures**

**IMMEDIATE (Within 15 minutes):**
1. Notify all admins: "Auto-resolution disabled, reason: {X}"
2. Review all alerts created in last hour (manual processing required)
3. Assess if manual intervention needed for stuck states

**WITHIN 1 HOUR:**
1. Root cause analysis: Why was kill switch activated?
2. Document incident: What happened, what was the impact, what was learned?
3. Decide if re-enable safe OR keep disabled

**BEFORE RE-ENABLING:**
1. Root cause fixed and verified
2. Dry run in staging environment
3. Finance lead approval
4. Update playbooks with new learnings

---

## SECTION 7: DRY RUN REQUIREMENTS

**DO NOT ENABLE AUTO-RESOLUTION UNTIL DRY RUN COMPLETE**

### **7.1 7-Day Detection-Only Dry Run**

**Objective:** Validate detection logic without auto-fix

**Actions:**
```bash
# Day 0: Enable detection
UPDATE settings SET value = '1' WHERE key = 'enable_stuck_state_detection';
UPDATE settings SET value = '0' WHERE key = 'allow_auto_resolution'; # AUTO-FIX OFF

# Daily monitoring
php artisan system:health --dashboard=operations
php artisan alerts:summary

# Review questions:
# - Are we detecting real stuck states?
# - Are there false positives?
# - What is the alert volume distribution by severity?
```

**Success Criteria:**
- <10% false positive rate
- All true stuck states detected within 2x threshold
- No missed critical states
- Dashboard query time <3s

### **7.2 Mock Escalation Drill**

**Objective:** Verify all admins know how to respond to critical alerts

**Scenario:**
```
INJECTED ALERT (via test command):
  Type: Stuck payment
  Amount: ₹250,000
  Time stuck: 96 hours
  User: Corporate client
  Impact: CRITICAL
```

**Expected Response:**
1. On-call engineer alerted within 5 minutes
2. Finance lead notified within 10 minutes
3. Payment gateway status checked within 15 minutes
4. Decision logged in alert notes within 20 minutes
5. User communication sent within 30 minutes

**Drill Command:**
```bash
php artisan test:inject-critical-alert \
  --type=stuck_payment \
  --amount=250000 \
  --hours-stuck=96 \
  --user-id=123

# Verify response
php artisan test:verify-drill-response --drill-id={X}
```

**Pass/Fail:**
- PASS: All response times within SLA, correct escalation chain followed
- FAIL: Any step missed OR >30min total response time → REPEAT DRILL

---

## SECTION 8: LEGAL REVIEW REQUIREMENTS

**BEFORE ENABLING AUTO-CANCELLATION/REFUND:**

### **8.1 Legal Team Review Checklist**

- [ ] **Payment Cancellation Policy:** Legal approved "when can we cancel a payment?"
- [ ] **Refund Policy:** Legal approved "when can we auto-refund?"
- [ ] **User Communication Templates:** Legal reviewed all auto-sent emails
- [ ] **Terms of Service:** Updated to include auto-resolution scenarios
- [ ] **Liability Waiver:** Reviewed liability for incorrect auto-fix
- [ ] **Regulatory Compliance:** Verified compliance with RBI/SEBI guidelines

### **8.2 Scenarios Requiring Legal Approval**

**Scenario 1: Auto-Cancel Payment Pending >24h**
- **Legal Risk:** User claims "I was about to pay, you cancelled unfairly"
- **Mitigation:** Grace period (48h instead of 24h), email warning before cancellation

**Scenario 2: Auto-Refund Stuck Processing**
- **Legal Risk:** Gateway settles later, we refunded already, double-spend
- **Mitigation:** Provisional credits only, permanent credits after settlement verification

**Scenario 3: Wallet Balance Adjustment**
- **Legal Risk:** User claims "You stole money from my wallet"
- **Mitigation:** Never auto-adjust user balances, always manual with admin approval

---

## SECTION 9: RUNBOOK MAINTENANCE

### **9.1 Update Frequency**

**Quarterly Review (Every 3 Months):**
- Review all escalation playbooks
- Update threshold recommendations based on traffic growth
- Review incidents and near-misses
- Update training materials

**Immediate Update Triggers:**
- Any auto-fix failure that caused user complaint
- Any incident requiring kill switch activation
- New integration added (new boundary, new reconciliation needs)
- Regulatory change (RBI/SEBI new rules)

### **9.2 Version Control**

This runbook MUST be version controlled with:
- Git commit for every change
- Changelog documenting what changed and why
- Approval from Finance Lead for critical changes

**Current Version:** 1.0
**Last Updated:** 2025-12-28
**Next Review:** 2025-03-28

---

## SECTION 10: QUICK REFERENCE

### **10.1 Emergency Contacts**

| Role | Name | Phone | Email | Escalation Time |
|------|------|-------|-------|-----------------|
| On-Call Engineer | TBD | TBD | TBD | 15 min (CRITICAL) |
| Finance Lead | TBD | TBD | TBD | 1 hour (HIGH) |
| CTO | TBD | TBD | TBD | 30 min (CRITICAL) |
| Payment Gateway Support | Razorpay | +91-XXX | support@razorpay.com | 2 hours |

### **10.2 Critical Commands**

```bash
# Kill switch (disable auto-resolution)
php artisan system:kill-switch --activate

# Check system health
php artisan system:health --dashboard=all

# View manual review queue
php artisan alerts:manual-queue

# Generate approval token for manual fix
php artisan wallet:generate-approval-token {wallet_id} --reason="..." --approver={admin_id}

# Run mock drill
php artisan test:inject-critical-alert --type=stuck_payment --amount=250000
```

### **10.3 Dashboard URLs**

- Financial Health: `/admin/dashboard/financial-health`
- Operational Health: `/admin/dashboard/operations`
- Manual Review Queue: `/admin/alerts/manual-review`
- Auto-Fix Audit Log: `/admin/alerts/auto-fix-log`

---

**END OF OPERATIONAL RUNBOOK**

**Remember:** This system is a **tool**, not a replacement for human judgment. When in doubt, escalate. When uncertain, disable auto-fix. When facing a novel scenario, update this runbook.
