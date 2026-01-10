# Phase 3 - Issuer Disclosure Workflows & UX Safeguards

**Document Version:** 1.0
**Date:** 2026-01-10
**Phase:** Issuer-Side Submission Workflows

---

## Table of Contents

1. [Core Principles](#core-principles)
2. [Dashboard & Progress Tracking](#dashboard--progress-tracking)
3. [Draft Editing Workflow](#draft-editing-workflow)
4. [Submission Workflow](#submission-workflow)
5. [Error Reporting System](#error-reporting-system)
6. [Clarification Conversations](#clarification-conversations)
7. [Role-Based Access Control](#role-based-access-control)
8. [UX Safeguards](#ux-safeguards)
9. [API Documentation](#api-documentation)
10. [Dispute Prevention](#dispute-prevention)

---

## 1. Core Principles

### Never Allow Silent Edits

**Problem:** Company edits approved disclosure data without admin knowledge → Investors see manipulated data → Legal disputes.

**Solution:**
- Approved disclosures are **locked** and **immutable**
- Attempts to edit approved data are **blocked** at policy level
- UI shows clear error: "Cannot edit approved disclosure. Use 'Report Error' to submit corrections."
- All edit attempts **logged** to audit trail

**Code Implementation:**
```php
// CompanyDisclosurePolicy->update()
if ($disclosure->status === 'approved') {
    return Response::deny(
        'Cannot edit approved disclosure. Use "Report Error" to submit corrections.'
    );
}
```

---

### Treat Issuer Honesty as First-Class Signal

**Problem:** Companies fear penalty for reporting errors → Hide mistakes → Worse problems later.

**Solution:**
- **"Report Error" action** is prominent in UI, not buried
- Self-reported corrections **do NOT** overwrite approved data
- Original approved data **preserved permanently**
- Admin **notified** but encouraged to view as positive signal
- Error report creates **new draft** for review

**How This Prevents Disputes:**
- Investor A invested seeing Revenue = ₹10Cr (Version 1, approved)
- Company discovers error, reports Revenue should be ₹8Cr
- System creates Version 2 draft, **Version 1 still exists**
- If litigation occurs, court can see:
  - What data was approved
  - What investors saw
  - When company self-reported
  - What changed and why

**Code Implementation:**
```php
// CompanyDisclosureService->reportErrorInApprovedDisclosure()
// Does NOT modify $disclosure
$newDraft = new CompanyDisclosure([
    'supersedes_disclosure_id' => $disclosure->id,
    'created_from_error_report' => true,
    // ... corrected data ...
]);
```

---

### Correction-Friendly

**Problem:** Fear of reporting errors → Inaccurate disclosures persist.

**Solution:**
- Low-friction error reporting workflow
- No penalties for self-correction
- Clear messaging: "We encourage honesty. Reporting errors helps everyone."
- Admin reviews corrections, not punishments

---

### Draft-Friendly

**Problem:** "Submit" is scary if data might be incomplete → Companies delay.

**Solution:**
- **Save draft anytime** (no submission required)
- Completion percentage shown clearly
- Draft can be edited unlimited times
- Clear "Next Action" indicators
- Only 100% complete drafts can be submitted

**UX Logic:**
```javascript
// Frontend
if (completionPercentage < 100) {
  return (
    <Button disabled>
      Submit for Review
      <Tooltip>Complete all required fields first ({completionPercentage}% done)</Tooltip>
    </Button>
  );
}
```

---

### Clear Next-Actions

**Problem:** "What do I do now?" → Confusion → Delays.

**Solution:**
- Dashboard shows **prioritized next actions**
- Each action has clear call-to-action
- Blockers surfaced prominently
- Progress bars for each tier

**Example Dashboard Output:**
```json
{
  "next_actions": [
    {
      "type": "answer_clarifications",
      "priority": "high",
      "message": "Answer 2 clarification(s) for Financial Performance",
      "disclosure_id": 123
    },
    {
      "type": "fix_rejected",
      "priority": "high",
      "message": "Fix rejected disclosure: Business Model",
      "disclosure_id": 456
    },
    {
      "type": "submit_for_review",
      "priority": "high",
      "message": "Submit Legal & Compliance for review",
      "disclosure_id": 789
    }
  ]
}
```

---

## 2. Dashboard & Progress Tracking

### Tier Progress

Shows company exactly where they stand:

```
Tier 1: Basic Information
[████████░░] 80% (4/5 modules approved)

Blockers:
❌ Business Model - REJECTED (Fix required)

Next Actions:
1. Fix rejected disclosure: Business Model (HIGH PRIORITY)
2. Complete Legal & Compliance (60% done)

---

Tier 2: Financial & Offering
[████░░░░░░] 40% (2/5 modules approved)

Status: Cannot enable buying until Tier 2 complete
```

**UX Safeguard:**
- Companies see **exactly** what's blocking progress
- No mystery about "why can't we go live?"
- Clear path forward

---

### Blockers Surface

**Types of Blockers:**
1. **Rejected Disclosures:** Admin rejected with reason
2. **Open Clarifications:** Questions needing answers
3. **Incomplete Drafts:** Required fields missing

**UX Display:**
```json
{
  "blockers": [
    {
      "type": "rejected",
      "severity": "high",
      "module_name": "Business Model",
      "reason": "Revenue projections lack supporting documentation",
      "rejected_at": "2026-01-08"
    },
    {
      "type": "clarifications_needed",
      "severity": "medium",
      "module_name": "Financial Performance",
      "clarification_count": 3
    }
  ]
}
```

**How This Prevents Disputes:**
- Company can't claim "we didn't know there was a problem"
- All blockers documented with timestamps
- Rejection reasons visible in audit trail

---

## 3. Draft Editing Workflow

### Save Anytime

**UX Flow:**
1. Company opens disclosure module
2. Fills in some fields
3. Clicks "Save Draft"
4. System saves, shows completion %
5. Company can leave and come back later

**Code Implementation:**
```php
// CompanyDisclosureService->saveDraft()
$disclosure->completion_percentage = $this->calculateCompletionPercentage(
    $disclosureData,
    $module->json_schema
);
```

**Safeguard:**
- Drafts are **private** (not visible to investors)
- Completion % calculated from JSON schema `required` fields
- No pressure to complete in one session

---

### Edit Logging

**Every draft edit is logged:**
```json
{
  "draft_edit_history": [
    {
      "edited_at": "2026-01-10T14:30:00Z",
      "edited_by": 123,
      "edit_reason": "Updated Q4 revenue figures",
      "fields_changed": ["revenue.q4_2025", "revenue.annual_2025"],
      "change_count": 2
    }
  ]
}
```

**How This Prevents Disputes:**
- If company claims "we never changed that", edit log proves otherwise
- Admin can see **when** and **why** changes were made
- Audit trail for regulatory compliance

---

### Editable States

**Can Edit:**
- `draft`: Initial creation
- `rejected`: Admin rejected, company must fix
- `clarification_required`: Admin needs more info, company can edit

**Cannot Edit:**
- `submitted`: Under admin review (to prevent moving target)
- `under_review`: Admin is reviewing (locked)
- `approved`: Immutable (use "Report Error" instead)

**UX Implementation:**
```javascript
// Frontend
if (disclosure.status === 'approved') {
  return (
    <Alert variant="info">
      This disclosure is approved and locked. To correct any errors, use the
      <Button variant="link">Report Error</Button> action.
    </Alert>
  );
}
```

**How This Prevents Manipulation:**
- Company can't edit while admin is reviewing
- Company can't silently edit approved data
- Clear messaging about why editing is blocked

---

## 4. Submission Workflow

### Validation Before Submit

**Checks:**
1. ✅ Completion = 100%
2. ✅ Status = 'draft'
3. ✅ Not locked
4. ✅ User has `submit` permission

**Code Implementation:**
```php
// CompanyDisclosureService->canSubmit()
return $disclosure->status === 'draft'
    && $disclosure->completion_percentage === 100
    && !$disclosure->is_locked;
```

**UX Implementation:**
```javascript
if (completionPercentage < 100) {
  return (
    <Button disabled>
      Submit for Review ({completionPercentage}% complete)
    </Button>
  );
}

return (
  <Button onClick={handleSubmit}>
    Submit for Review
    <ConfirmDialog>
      Once submitted, this disclosure will be locked for editing while
      under review. Are you sure you're ready to submit?
    </ConfirmDialog>
  </Button>
);
```

---

### Submission Notes

Companies can provide context for reviewers:

```json
{
  "submission_notes": "Updated revenue figures based on Q4 2025 actuals. Supporting documents attached as 'Q4_Revenue_Report.pdf'."
}
```

**How This Prevents Disputes:**
- Provides context for admin
- Reduces back-and-forth clarifications
- Logged permanently

---

### Locked During Review

**UX Display:**
```
Status: Under Review
Submitted: 2026-01-10 at 2:30 PM

This disclosure is locked while under admin review. You cannot edit it
until the admin either approves, rejects, or requests clarifications.

Estimated Review Time: 5-10 business days
```

**Safeguard:**
- Prevents "moving target" where company edits while admin reviews
- Admin sees exactly what was submitted
- Timestamp proves what was submitted when

---

## 5. Error Reporting System

### The Anti-Silent-Edit Safeguard

**Scenario:**
```
Company discovers error in approved Financial Performance disclosure:
- Original (approved): "Revenue FY2025: ₹100 Cr"
- Correct value: "Revenue FY2025: ₹85 Cr"
```

**What Happens:**

**Step 1: Company Clicks "Report Error"**
```javascript
<Card status="approved">
  <Title>Financial Performance</Title>
  <Status>✅ Approved</Status>
  <Actions>
    <Button variant="warning" icon={AlertIcon}>
      Report Error or Omission
    </Button>
  </Actions>
</Card>
```

**Step 2: Company Fills Error Report Form**
```javascript
<Form>
  <Field label="What was wrong?">
    <Textarea name="error_description" required>
      The revenue figure was overstated due to an accounting error
      discovered during our year-end audit.
    </Textarea>
  </Field>

  <Field label="Why is this correction needed?">
    <Textarea name="correction_reason" required>
      Our auditors found that ₹15 Cr in revenue was incorrectly recognized
      in FY2025 instead of FY2026. GAAP compliance requires correction.
    </Textarea>
  </Field>

  <Field label="Corrected Data">
    <JSONEditor
      schema={module.json_schema}
      value={correctedData}
      showDiff={true}
      originalData={approvedData}
    />
  </Field>

  <Alert variant="info">
    Your original approved disclosure will NOT be modified. We will create
    a new draft with your corrections for admin review. This is the
    transparent way to handle errors.
  </Alert>

  <Button type="submit">Submit Error Report</Button>
</Form>
```

**Step 3: System Creates New Draft (Does NOT Modify Approved)**
```php
// Backend
$errorReport = DisclosureErrorReport::create([
    'company_disclosure_id' => $approvedDisclosure->id,
    'error_description' => 'Revenue overstated...',
    'correction_reason' => 'Auditor correction...',
    'original_data' => $approvedDisclosure->disclosure_data,
    'corrected_data' => $correctedData,
]);

$newDraft = new CompanyDisclosure([
    'supersedes_disclosure_id' => $approvedDisclosure->id,
    'created_from_error_report' => true,
    'error_report_id' => $errorReport->id,
    'disclosure_data' => $correctedData,
    'status' => 'draft',
    'version_number' => $approvedDisclosure->version_number + 1,
]);
```

**Step 4: Admin Notified**
```
📧 Email to Admin:

Subject: Self-Reported Error in Approved Disclosure

Company XYZ has reported an error in their approved Financial Performance
disclosure.

Original Value: Revenue FY2025 = ₹100 Cr (Version 2, approved 2026-01-05)
Corrected Value: Revenue FY2025 = ₹85 Cr (Version 3, pending review)

Reason: Auditor correction for revenue recognition error

Action Required: Review Version 3 draft

[View Error Report] [View New Draft] [View Diff]

Note: The original approved disclosure (Version 2) has been preserved and
remains visible. No data has been overwritten.
```

**Step 5: Company Sees Confirmation**
```javascript
<SuccessAlert>
  <Title>Error Report Submitted</Title>
  <Message>
    Thank you for your transparency. We've created a new draft (Version 3)
    with your corrections. An admin has been notified and will review it.

    The original approved disclosure (Version 2) remains unchanged and
    visible to investors until the new version is approved.
  </Message>

  <Actions>
    <Button href="/disclosures/new-draft">View New Draft</Button>
    <Button href="/error-reports/123">View Error Report</Button>
  </Actions>
</SuccessAlert>
```

---

### Why This Prevents Disputes

**Scenario: Investor sues company for false revenue claims**

**Without Error Reporting System:**
```
Investor: "You showed revenue of ₹100 Cr when I invested"
Company: "No, it was always ₹85 Cr, you must be mistaken"
Court: "No audit trail, impossible to verify"
```

**With Error Reporting System:**
```
Court Review of Disclosure History:

Version 2 (Approved 2026-01-05):
- Revenue: ₹100 Cr
- Status: Approved
- Investor A bought 1000 shares on 2026-01-07 (2 days after approval)
- Version Hash: a3f7b2...

Error Report (Filed 2026-01-15):
- Reported by: Company CFO
- Error: "Revenue overstated by ₹15 Cr"
- Reason: "Auditor correction for GAAP compliance"
- Self-reported: YES

Version 3 (Approved 2026-01-20):
- Revenue: ₹85 Cr
- Supersedes: Version 2
- Status: Approved

Court Finding:
✅ Company self-reported error 10 days after investor purchase
✅ Original data preserved (proves investor saw ₹100 Cr)
✅ Company acted transparently
→ Mitigating factor in liability determination
```

---

## 6. Clarification Conversations

### Threaded Q&A

**Admin Question:**
```json
{
  "clarification_id": 456,
  "question_subject": "Revenue Breakdown Clarification",
  "question_body": "Please break down your ₹100 Cr revenue by product line",
  "field_path": "revenue.annual_2025",
  "priority": "high",
  "is_blocking": true,
  "due_date": "2026-01-17"
}
```

**Company Answer:**
```json
{
  "answer_body": "Product A: ₹60 Cr\nProduct B: ₹30 Cr\nProduct C: ₹10 Cr",
  "supporting_documents": [
    {
      "file_name": "Revenue_Breakdown_FY2025.xlsx",
      "file_path": "s3://disclosures/123/revenue_breakdown.xlsx"
    }
  ]
}
```

**UX Display:**
```
┌─────────────────────────────────────────┐
│ Clarification #456                      │
├─────────────────────────────────────────┤
│ Admin asked (2026-01-10):               │
│ Please break down your ₹100 Cr revenue  │
│ by product line                         │
│                                         │
│ Priority: HIGH | Due: Jan 17            │
│ Blocking: YES                           │
├─────────────────────────────────────────┤
│ Your Answer (not yet submitted):        │
│ [Textarea]                              │
│                                         │
│ Attach Supporting Documents:            │
│ [File Upload]                           │
│                                         │
│ <Button>Submit Answer</Button>          │
└─────────────────────────────────────────┘
```

**Safeguard:**
- All Q&A is **threaded** (not scattered emails)
- Questions linked to specific fields (`field_path`)
- Answers **logged permanently**
- Cannot approve disclosure until all clarifications resolved

---

### Version Awareness

**Problem:** Admin asks question about Version 1, company edits to Version 2, admin confused about which version the question refers to.

**Solution:**
Clarifications linked to specific disclosure version:
```php
DisclosureClarification::create([
    'company_disclosure_id' => $disclosure->id,
    'disclosure_version_snapshot' => $disclosure->disclosure_data, // Snapshot
    'asked_at_version' => $disclosure->version_number,
]);
```

**UX Display:**
```
Admin Question (about Version 2):
"Please explain revenue spike in Q4"

[Version 2 snapshot shown to company while answering]
```

---

## 7. Role-Based Access Control

### Four Roles

**1. Founder**
- **Access:** Everything
- **Can:** Edit all disclosures, submit all, manage users
- **Cannot:** Bypass approval process

**2. Finance**
- **Access:** Financial disclosures (Tier 2)
- **Can:** Edit/submit financial modules
- **Cannot:** Edit legal modules, manage users

**3. Legal**
- **Access:** Legal/compliance disclosures
- **Can:** Edit/submit legal modules
- **Cannot:** Edit financial modules, manage users

**4. Viewer**
- **Access:** Read-only to all disclosures
- **Can:** View disclosures, view clarifications
- **Cannot:** Edit anything, submit anything

---

### Permission Enforcement

**At Policy Level (Server):**
```php
// CompanyDisclosurePolicy->update()
public function update(User $user, CompanyDisclosure $disclosure): Response
{
    $role = $this->getUserRole($user, $disclosure->company);

    // Check role access to module
    if (!$role->canAccessModule($disclosure->module)) {
        return Response::deny(
            "Your {$role->getRoleDisplayName()} role cannot edit {$disclosure->module->name}"
        );
    }
}
```

**At UI Level (Client):**
```javascript
// Frontend
const permissions = usePermissions(disclosure);

return (
  <DisclosureEditor>
    {!permissions.canEdit && (
      <Alert variant="warning">
        You have read-only access to this disclosure. Your role
        ({userRole}) cannot edit {module.name}.
      </Alert>
    )}

    <JSONEditor
      disabled={!permissions.canEdit}
      value={disclosureData}
    />

    {permissions.canEdit && (
      <Button onClick={saveDraft}>Save Draft</Button>
    )}
  </DisclosureEditor>
);
```

**How This Prevents Manipulation:**
- Finance team can't edit legal disclosures (out of expertise)
- Legal team can't manipulate financials
- Viewers can't make any changes
- Audit log shows which role made which edits

---

## 8. UX Safeguards

### 1. Confirm Before Irreversible Actions

**Submit Disclosure:**
```javascript
<ConfirmDialog
  title="Submit for Review?"
  message="Once submitted, this disclosure will be locked for editing while under review. Make sure all information is accurate."
  confirmLabel="Yes, Submit"
  onConfirm={handleSubmit}
/>
```

**Report Error:**
```javascript
<ConfirmDialog
  title="Report Error in Approved Disclosure"
  message="This will create a new draft for admin review. The original approved disclosure will be preserved. Are you sure you want to report an error?"
  confirmLabel="Yes, Report Error"
  onConfirm={handleReportError}
/>
```

---

### 2. Show What Will Happen

**Before Submit:**
```
What happens next:
1. ✅ Disclosure locked for editing
2. 📧 Admin notified for review
3. ⏰ Estimated review time: 5-10 business days
4. 📝 You'll be able to answer clarifications if admin asks
5. ✅ If approved, data becomes public and immutable
```

**Before Error Report:**
```
What happens next:
1. ✅ Original approved disclosure preserved (investors still see it)
2. 📝 New draft created with your corrections
3. 📧 Admin notified about self-reported error
4. ⏰ Admin reviews your correction
5. ✅ If approved, new version becomes public

Original disclosure will remain visible until new version is approved.
```

---

### 3. Clear Error Messages

**Bad:**
```
Error: Cannot perform action
```

**Good:**
```
Cannot submit disclosure

Reason: Disclosure is only 75% complete

What to do:
- Complete the 3 missing required fields:
  • Board composition
  • Shareholder list
  • Registered office address

Once all fields are complete, you'll be able to submit.
```

---

### 4. Progress Indicators

```
Financial Performance Disclosure

[████████░░] 80% Complete

Required Fields (4/5):
✅ Annual revenue
✅ Quarterly breakdown
✅ Profit margins
✅ Key customers
❌ Operating expenses ← Complete this field

[Save Draft] [Submit for Review (disabled)]
```

---

## 9. API Documentation

### Company Endpoints

```
GET    /api/company/dashboard
POST   /api/company/disclosures
POST   /api/company/disclosures/{id}/submit
POST   /api/company/disclosures/{id}/report-error
POST   /api/company/clarifications/{id}/answer
```

### Dashboard Response

```json
{
  "status": "success",
  "data": {
    "tier_progress": [
      {
        "tier": 1,
        "tier_label": "Basic Information",
        "completed": 4,
        "total": 5,
        "percentage": 80,
        "is_complete": false
      }
    ],
    "blockers": [
      {
        "type": "rejected",
        "severity": "high",
        "module_name": "Business Model",
        "reason": "Insufficient detail"
      }
    ],
    "next_actions": [
      {
        "type": "fix_rejected",
        "priority": "high",
        "message": "Fix rejected disclosure: Business Model"
      }
    ]
  }
}
```

---

## 10. Dispute Prevention

### How Each Safeguard Prevents Disputes

| Safeguard | Dispute Scenario Prevented | How It Works |
|-----------|---------------------------|--------------|
| **Immutable Approved Data** | Company edits revenue after investors buy | Approved disclosures locked, cannot be edited |
| **Error Reporting (No Overwrite)** | Company claims "data was always correct" | Original approved data preserved, error report logged |
| **Edit Logging** | Company denies making changes | Every edit logged with timestamp, user, reason |
| **Version Hashing** | Company claims disclosure was tampered | SHA-256 hash verifies data integrity |
| **Clarification Threading** | "You never asked about that" | All Q&A logged with timestamps, linked to fields |
| **Submission Timestamps** | "We submitted days ago" | Blockchain-level timestamp proof |
| **Role-Based Access** | "Finance team manipulated legal data" | Roles enforce who can edit what |
| **Completion Validation** | "We didn't know fields were required" | Cannot submit <100% complete |
| **Locked During Review** | Company edits while admin reviews | Status prevents editing during review |
| **Dashboard Blockers** | "We didn't know it was rejected" | All blockers shown prominently |

---

## Conclusion

This issuer workflow system is designed to:
1. ✅ **Encourage honesty** (error reporting without penalty)
2. ✅ **Prevent manipulation** (immutable approved data)
3. ✅ **Ensure transparency** (full audit trails)
4. ✅ **Reduce disputes** (timestamped proof of everything)
5. ✅ **Guide users** (clear next actions)

Every safeguard has a **purpose** and **prevents a specific attack or dispute scenario**.

---

**End of Document**
