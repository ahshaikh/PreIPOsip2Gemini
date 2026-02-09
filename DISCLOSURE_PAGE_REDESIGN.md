# Company Disclosures Page - Redesign Documentation

## Overview
Complete redesign of the Company Disclosures page (`/company/disclosures`) following founder-dignity-first principles and collaborative compliance approach.

---

## ✅ Implemented Features

### 1️⃣ Platform Governance Overview
**Location**: Header section

**Components**:
- Current Platform Governance Status (Pre-Investment, Investment Enabled, Full Transparency, IPO Ready)
- Current Tier badge
- Progress toward next tier indicator
- Context-aware messaging based on tier level

**Purpose**: Answers "Where am I in the platform lifecycle?"

---

### 2️⃣ Disclosure Completion Indicator
**Location**: Progress bar card

**Features**:
- **Requirement-based progress** (not document count)
- Shows: X of Y required disclosures completed
- Only counts approved disclosures as complete
- Helper text: "Progress reflects approved disclosure requirements, not document count"
- Dynamic based on current tier

**Key Principle**: A requirement is complete ONLY when its disclosure thread is Approved.

---

### 3️⃣ Disclosure Modules by Category
**Location**: Main content area

**Categories**:
- 🛡️ Governance (blue) - Board structure, policies
- 📈 Financial (green) - Financial statements, funding
- 📄 Legal & Risk (purple) - Compliance, contracts, risk
- 🏢 Operational (orange) - Business operations, team

**Features**:
- Filterable tabs (All, Governance, Financial, Legal & Risk, Operational)
- Grouped display with category icons and descriptions
- Each module shows:
  - Title and description
  - Current status badge
  - Required/Optional indicator
  - Last updated timestamp
  - Contextual alerts (action requested, approved, pending)

**Actions**:
- Start new disclosure (for not_started)
- View thread (opens timeline view)
- Respond/Edit (when applicable)
- Disabled "No action needed" for approved

---

### 4️⃣ Respectful Language System
**Implemented throughout**:

| ❌ Old (Punitive) | ✅ New (Collaborative) |
|-------------------|------------------------|
| Rejected | Action Requested |
| Needs Fixing | Clarification Requested |
| Waiting | Pending Review |
| Failed | Needs Update |

**Status Badges**:
- Draft (gray)
- Pending Review (blue)
- Approved (green)
- Action Requested (amber, not red)

**Alert Messages**:
- "Action Requested" (not "Rejection")
- "Guidance:" (not "You must fix:")
- "This disclosure has been approved and is now part of your platform record" (celebratory)

---

### 5️⃣ Active Clarifications Section
**Location**: Bottom of page (when present)

**Features**:
- Displays platform clarification requests
- Shows overdue status (non-punitive badge)
- Respond button (disabled if platform restricts)
- Amber-themed for attention without alarm

---

## 🎨 Design Principles Applied

### Founder Dignity
✅ Professional, calm, collaborative tone
✅ No adversarial language
✅ Guidance-focused (not punishment)
✅ Clear next actions

### Requirement-Centric
✅ Progress based on fulfilled requirements
✅ Not document count
✅ Not upload count
✅ Only approved = complete

### Collaborative Framing
✅ "Disclosure Management" (not "Compliance")
✅ "Collaborative review process"
✅ "Action Requested" (invitation to respond)
✅ Platform as partner, not enforcer

### Scale-Appropriate
✅ Works for small companies
✅ Works for IPO-bound companies
✅ Clear tier progression
✅ Non-intimidating empty states

---

## 📋 Still To Implement

### Disclosure Thread View (Detail Page)
**Path**: `/company/disclosures/[id]`

**Requirements**:
- Timeline-style audit trail (like GitHub PR)
- Append-only history
- Timeline entries:
  - Company submissions
  - Admin clarification requests
  - Admin approvals
  - Timestamps and actors
- No edit/delete capability (immutable)
- Reply controls only when eligible
- Document attachments in timeline
- Respectful language throughout

**Suggested Component**: `DisclosureThreadView`

### Backend Enhancements Needed

1. **Add `category` field to disclosure modules**:
   ```php
   // Currently frontend infers from name
   // Should be explicit: 'governance', 'financial', 'legal', 'operational'
   ```

2. **Add `is_required` field**:
   ```php
   // Track which disclosures are required for current tier
   ```

3. **Add `last_updated` timestamp**:
   ```php
   // Show when disclosure was last modified
   ```

4. **Enhance `getIssuerCompanyData()` to include**:
   ```php
   'disclosures' => [
       'category' => 'governance', // NEW
       'is_required' => true,      // NEW
       'last_updated' => '...',    // NEW
       'description' => '...',     // NEW
   ]
   ```

---

## 🧪 Testing Scenarios

### Test 1: Empty State
- New company with no disclosures
- Should show: "No Disclosure Requirements Yet"
- Friendly, non-intimidating message

### Test 2: Mixed Statuses
- Some approved, some pending, some action-requested
- Should show: Correct progress percentage
- Should group by category correctly

### Test 3: All Approved
- All required disclosures approved
- Should show: 100% completion
- Should show: "All disclosure requirements met"

### Test 4: Action Requested
- Disclosure with clarification_required status
- Should show: Amber "Action Requested" badge
- Should show: Respectful guidance message
- Should show: "Respond" button

### Test 5: Platform Restrictions
- Company suspended/frozen
- Should show: Platform status banner
- Should disable: Edit/respond buttons
- Should explain: Why actions are blocked

---

## 📐 Component Structure

```
app/company/disclosures/
├── page.tsx                      ✅ Main page (redesigned)
├── [id]/
│   └── page.tsx                  🔲 Thread view (TODO)
│   └── respond/
│       └── page.tsx              🔲 Response form (TODO)
└── components/
    ├── DisclosureThreadView.tsx  🔲 Timeline component (TODO)
    ├── DisclosureReplyForm.tsx   🔲 Reply form (TODO)
    └── CategoryFilter.tsx        ✅ Implemented inline
```

---

## 🎯 Key Metrics

| Metric | Old | New |
|--------|-----|-----|
| Language sentiment | Punitive | Collaborative |
| Progress basis | Document count | Requirement fulfillment |
| Status visibility | Unclear | Clear, color-coded |
| Action clarity | Vague | Explicit buttons |
| Category organization | Flat list | Grouped by domain |
| Empty state | Missing | Friendly explanation |

---

## 🔄 Migration Notes

### Data Normalization
- Frontend now expects `is_required` field
- Frontend infers `category` if not provided
- Backend should add these fields to contract

### Language Updates Applied
- All "Rejected" → "Action Requested"
- All "Needs Fixing" → "Clarification Requested"
- All "Waiting" → "Pending Review"

### Progress Calculation Changed
**Before**: `approved / total_disclosures`
**After**: `approved_required / total_required`

Only required disclosures count toward completion.

---

## 📞 Support

For questions about this redesign:
- See `frontend/app/company/disclosures/page.tsx` (main implementation)
- See `BACKEND_FRONTEND_CONTRACT_FIX.md` (data contract)
- See component documentation in code comments
