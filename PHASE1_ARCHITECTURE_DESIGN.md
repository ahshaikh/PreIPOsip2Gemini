# PHASE 1: GOVERNANCE PROTOCOL DATABASE ARCHITECTURE
**Detailed Design Document**

---

## DESIGN PRINCIPLES

1. **Extend, Don't Replace** - Preserve existing Company module
2. **Modular Disclosures** - Each disclosure type is independent
3. **Immutable Approvals** - Approved versions cannot change
4. **Full Audit Trail** - Every action logged
5. **Backward Compatible** - Existing code continues to work

---

## COMPONENT 1: COMPANY MASTER RECORD (Anchor Entity)

### Purpose
Single source of truth for company identity, legal status, and platform lifecycle.

### Design: Extend `companies` Table

**Approach:** Additive migration (no breaking changes)

**New Fields:**

```sql
-- Legal Identity (Regulatory Requirement)
cin VARCHAR(21) NULL COMMENT 'Corporate Identification Number',
pan VARCHAR(10) NULL COMMENT 'Permanent Account Number',
registration_number VARCHAR(50) NULL COMMENT 'Company registration number',
registration_date DATE NULL,
legal_structure ENUM('private_limited', 'public_limited', 'llp', 'partnership', 'sole_proprietorship', 'other') NULL,

-- Governance Metadata
board_size INT NULL COMMENT 'Total board members',
independent_directors INT NULL COMMENT 'Number of independent directors',
audit_committee_size INT NULL COMMENT 'Audit committee members',

-- SEBI & Regulatory
sebi_registered BOOLEAN DEFAULT false,
sebi_registration_number VARCHAR(50) NULL,
sebi_approval_date DATE NULL,
sebi_compliance_status ENUM('compliant', 'non_compliant', 'under_review', 'not_applicable') DEFAULT 'not_applicable',

-- Disclosure Lifecycle
disclosure_stage ENUM('draft', 'submitted', 'under_review', 'clarification_needed', 'approved', 'rejected') DEFAULT 'draft',
disclosure_submitted_at TIMESTAMP NULL,
disclosure_approved_at TIMESTAMP NULL,
disclosure_approved_by BIGINT UNSIGNED NULL COMMENT 'Admin user ID',
disclosure_rejection_reason TEXT NULL,

-- Risk Classification (Admin Judgment - NOT disclosure)
risk_category ENUM('low', 'medium', 'high', 'very_high') NULL COMMENT 'Platform risk assessment',
risk_assessment_date DATE NULL,
risk_assessment_by BIGINT UNSIGNED NULL,

-- Exit Planning
listing_target_exchange ENUM('nse', 'bse', 'nasdaq', 'nyse', 'other') NULL,
listing_target_date DATE NULL,
ipo_status ENUM('not_planned', 'planned', 'in_process', 'listed', 'delisted') DEFAULT 'not_planned',

-- Foreign Keys
FOREIGN KEY (disclosure_approved_by) REFERENCES users(id) ON DELETE SET NULL,
FOREIGN KEY (risk_assessment_by) REFERENCES users(id) ON DELETE SET NULL,

-- Indexes
INDEX (cin),
INDEX (pan),
INDEX (disclosure_stage),
INDEX (sebi_registered),
INDEX (ipo_status)
```

**Why These Fields:**
- **Legal Identity:** Required for regulatory compliance, SEBI filings
- **Governance:** Track corporate governance quality (risk indicator)
- **SEBI:** Platform must track regulatory approval status
- **Disclosure Lifecycle:** Separate from company status (active/inactive)
- **Risk Classification:** Admin tool, NOT mixed with disclosure data
- **Exit Planning:** Investors need to know IPO timeline

**Separation of Concerns:**
- ✅ Legal facts (CIN, PAN) - part of master record
- ✅ Platform judgments (risk_category) - clearly marked as admin assessment
- ❌ Disclosure data (financials, risks) - NOT in this table (goes to disclosure_versions)

---

## COMPONENT 2: DISCLOSURE MODULES (Template Structure)

### Purpose
Define the structure and validation rules for each disclosure type.

### New Table: `disclosure_modules`

```sql
CREATE TABLE disclosure_modules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Module Identity
    code VARCHAR(50) UNIQUE NOT NULL COMMENT 'business, financials, risks, governance, etc.',
    name VARCHAR(255) NOT NULL COMMENT 'Display name: Business Overview, Financial Statements',
    description TEXT NULL,
    
    -- Structure Definition
    schema_version VARCHAR(10) NOT NULL DEFAULT '1.0' COMMENT 'JSON schema version for this module',
    json_schema JSON NOT NULL COMMENT 'JSON Schema for validation',
    required_fields JSON NULL COMMENT 'List of mandatory field paths',
    
    -- Lifecycle
    is_active BOOLEAN DEFAULT true COMMENT 'Can new disclosures use this module?',
    requires_admin_approval BOOLEAN DEFAULT true,
    requires_documents BOOLEAN DEFAULT false COMMENT 'Must upload supporting docs?',
    
    -- Display & Ordering
    display_order INT DEFAULT 0,
    icon VARCHAR(50) NULL,
    category ENUM('core', 'financial', 'legal', 'operational', 'risk', 'governance', 'exit') DEFAULT 'core',
    
    -- Metadata
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX (code),
    INDEX (category),
    INDEX (is_active),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Why This Design:**
- **Template Pattern:** Disclosure modules are reusable templates
- **JSON Schema:** Validate disclosure data structure dynamically
- **Extensible:** Add new disclosure types without code changes
- **Version-Aware:** Schema can evolve over time

**Example Modules:**
```json
{
  "code": "business",
  "name": "Business Overview",
  "json_schema": {
    "type": "object",
    "required": ["business_model", "target_market", "revenue_streams"],
    "properties": {
      "business_model": {"type": "string", "minLength": 100},
      "target_market": {"type": "string"},
      "revenue_streams": {"type": "array", "items": {"type": "string"}},
      "competitive_advantage": {"type": "string"}
    }
  }
}
```

---

## COMPONENT 3: COMPANY DISCLOSURES (Modular Instances)

### Purpose
Link companies to disclosure modules, track lifecycle of each disclosure type.

### New Table: `company_disclosures`

```sql
CREATE TABLE company_disclosures (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Ownership
    company_id BIGINT UNSIGNED NOT NULL,
    disclosure_module_id BIGINT UNSIGNED NOT NULL,
    
    -- Lifecycle Status
    status ENUM('draft', 'submitted', 'under_review', 'clarification_needed', 'approved', 'rejected', 'superseded') DEFAULT 'draft',
    
    -- Workflow Timestamps
    drafted_at TIMESTAMP NULL,
    submitted_at TIMESTAMP NULL,
    submitted_by BIGINT UNSIGNED NULL COMMENT 'CompanyUser ID',
    
    approved_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED NULL COMMENT 'Admin user ID',
    approval_notes TEXT NULL,
    
    rejected_at TIMESTAMP NULL,
    rejected_by BIGINT UNSIGNED NULL,
    rejection_reason TEXT NULL,
    
    superseded_at TIMESTAMP NULL COMMENT 'When a newer version replaced this',
    superseded_by BIGINT UNSIGNED NULL COMMENT 'Disclosure ID that replaced this',
    
    -- Current Version Tracking
    current_version_id BIGINT UNSIGNED NULL COMMENT 'FK to disclosure_versions',
    current_version_number INT DEFAULT 1,
    total_versions INT DEFAULT 0,
    
    -- Clarification Tracking
    clarifications_count INT DEFAULT 0,
    unresolved_clarifications_count INT DEFAULT 0,
    last_clarification_at TIMESTAMP NULL,
    
    -- Metadata
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE KEY (company_id, disclosure_module_id, status) COMMENT 'One active disclosure per module per company',
    INDEX (company_id, status),
    INDEX (disclosure_module_id),
    INDEX (status),
    INDEX (submitted_at),
    INDEX (approved_at),
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (disclosure_module_id) REFERENCES disclosure_modules(id) ON DELETE RESTRICT,
    FOREIGN KEY (submitted_by) REFERENCES company_users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (superseded_by) REFERENCES company_disclosures(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Why This Design:**
- **One Disclosure Per Module:** Company can only have one active "Business Overview" disclosure
- **Lifecycle Tracking:** Full workflow from draft → approved
- **Version Pointer:** `current_version_id` points to latest approved version
- **Supersession:** When a disclosure is updated, old one marked as superseded
- **Clarification Count:** Quick access to pending questions

**State Machine:**
```
draft → submitted → under_review → approved
                       ↓              ↓
                   clarification    (immutable)
                       ↓
                   submitted → ...
                   
rejected (terminal unless resubmitted as new disclosure)
```

---

## COMPONENT 4: DISCLOSURE VERSIONS (Immutable Snapshots)

### Purpose
Store versioned disclosure data. Once approved, versions are IMMUTABLE.

### New Table: `disclosure_versions`

```sql
CREATE TABLE disclosure_versions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Ownership
    company_disclosure_id BIGINT UNSIGNED NOT NULL,
    version_number INT NOT NULL COMMENT 'Sequential: 1, 2, 3...',
    
    -- Disclosure Data (Structured JSON)
    disclosure_data JSON NOT NULL COMMENT 'Validated against module JSON schema',
    
    -- Change Tracking
    changed_fields JSON NULL COMMENT 'Fields modified from previous version',
    change_summary TEXT NULL COMMENT 'Human-readable change description',
    change_reason ENUM('initial', 'correction', 'material_change', 'clarification_response', 'annual_update') NOT NULL,
    
    -- Validation
    is_valid BOOLEAN DEFAULT true COMMENT 'Passes JSON schema validation?',
    validation_errors JSON NULL,
    
    -- Approval State
    is_approved BOOLEAN DEFAULT false,
    approved_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED NULL,
    
    -- Immutability Protection
    is_locked BOOLEAN DEFAULT false COMMENT 'Locked versions cannot be deleted',
    locked_at TIMESTAMP NULL,
    locked_reason VARCHAR(255) NULL COMMENT 'approved, used_in_deal, regulatory_snapshot',
    
    -- Document Linkage
    document_ids JSON NULL COMMENT 'Array of company_document IDs supporting this disclosure',
    
    -- Audit Trail
    created_by BIGINT UNSIGNED NULL COMMENT 'CompanyUser or Admin ID',
    created_by_type ENUM('company_user', 'admin', 'system') DEFAULT 'company_user',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE KEY (company_disclosure_id, version_number),
    INDEX (company_disclosure_id, version_number DESC),
    INDEX (is_approved),
    INDEX (is_locked),
    INDEX (created_at),
    
    FOREIGN KEY (company_disclosure_id) REFERENCES company_disclosures(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Why This Design:**
- **Immutability:** Once `is_locked = true`, version cannot be modified or deleted
- **Structured Data:** JSON validated against module schema
- **Change Tracking:** Know what changed and why
- **Document Linkage:** Reference supporting documents (pitch deck, financials, etc.)
- **Approval Workflow:** Separate approval from creation

**Immutability Enforcement:**
```php
// Model boot method
static::updating(function ($version) {
    if ($version->is_locked) {
        throw new \RuntimeException('Locked disclosure versions are immutable');
    }
});

static::deleting(function ($version) {
    if ($version->is_locked) {
        throw new \RuntimeException('Cannot delete locked disclosure version');
    }
});
```

**Example Disclosure Data:**
```json
{
  "business_model": "B2B SaaS platform for enterprise workflow automation",
  "target_market": "Mid-size to large enterprises in manufacturing, logistics",
  "revenue_streams": [
    "Subscription fees (70%)",
    "Professional services (20%)",
    "Training and support (10%)"
  ],
  "competitive_advantage": "AI-powered workflow optimization engine",
  "key_partnerships": ["SAP", "Oracle", "Salesforce"],
  "market_size_estimate": "USD 15 billion TAM",
  "growth_strategy": "Geographic expansion, product diversification"
}
```

---

## COMPONENT 5: DISCLOSURE CLARIFICATIONS (Q&A System)

### Purpose
Support unlimited clarification rounds between admin and company.

### New Table: `disclosure_clarifications`

```sql
CREATE TABLE disclosure_clarifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Linkage
    company_disclosure_id BIGINT UNSIGNED NOT NULL,
    disclosure_version_id BIGINT UNSIGNED NULL COMMENT 'Specific version this applies to',
    
    -- Question
    question TEXT NOT NULL,
    question_field_path VARCHAR(255) NULL COMMENT 'JSON path to specific field: $.revenue_streams[0]',
    asked_by BIGINT UNSIGNED NOT NULL COMMENT 'Admin user ID',
    asked_at TIMESTAMP NOT NULL,
    
    -- Response
    answer TEXT NULL,
    answered_by BIGINT UNSIGNED NULL COMMENT 'CompanyUser ID',
    answered_at TIMESTAMP NULL,
    
    -- Resolution
    status ENUM('open', 'answered', 'accepted', 'disputed', 'withdrawn') DEFAULT 'open',
    resolved_at TIMESTAMP NULL,
    resolved_by BIGINT UNSIGNED NULL,
    resolution_notes TEXT NULL,
    
    -- Priority & Category
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    category ENUM('missing_info', 'clarification', 'inconsistency', 'verification', 'other') DEFAULT 'clarification',
    
    -- Threading
    parent_clarification_id BIGINT UNSIGNED NULL COMMENT 'For follow-up questions',
    
    -- Escalation
    is_escalated BOOLEAN DEFAULT false,
    escalated_at TIMESTAMP NULL,
    escalation_reason TEXT NULL,
    
    -- Metadata
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX (company_disclosure_id, status),
    INDEX (disclosure_version_id),
    INDEX (status),
    INDEX (priority),
    INDEX (asked_at),
    INDEX (parent_clarification_id),
    
    FOREIGN KEY (company_disclosure_id) REFERENCES company_disclosures(id) ON DELETE CASCADE,
    FOREIGN KEY (disclosure_version_id) REFERENCES disclosure_versions(id) ON DELETE SET NULL,
    FOREIGN KEY (asked_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (answered_by) REFERENCES company_users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_clarification_id) REFERENCES disclosure_clarifications(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Why This Design:**
- **Version-Specific:** Clarifications tied to specific disclosure versions
- **Field-Level:** Can pinpoint exact JSON field that needs clarification
- **Threading:** Follow-up questions via parent_clarification_id
- **Resolution Tracking:** Know when clarifications are resolved
- **Priority System:** Urgent questions escalated

**Workflow:**
```
Admin asks question (status: open)
    ↓
Company answers (status: answered)
    ↓
Admin reviews answer
    ↓
Admin accepts (status: accepted) OR disputes (status: disputed)
    ↓
If disputed, Company provides updated answer
    ↓
Loop until accepted
```

**Dispute Scenario:**
```
Question: "Please explain 70% revenue drop in Q2 2023"
Answer: "One-time customer churn, now recovered"
Status: disputed
Resolution: "Need to provide customer retention metrics"
    ↓
(New version created with updated disclosure)
    ↓
Status: accepted (linked to new version)
```

---

## COMPONENT 6: DISCLOSURE APPROVALS (Workflow Tracking)

### Purpose
Track multi-step approval workflow with audit trail.

### New Table: `disclosure_approvals`

```sql
CREATE TABLE disclosure_approvals (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Linkage
    company_disclosure_id BIGINT UNSIGNED NOT NULL,
    disclosure_version_id BIGINT UNSIGNED NOT NULL,
    
    -- Approval Request
    requested_at TIMESTAMP NOT NULL,
    requested_by BIGINT UNSIGNED NULL COMMENT 'CompanyUser who submitted',
    
    -- Approval Decision
    status ENUM('pending', 'approved', 'rejected', 'withdrawn') DEFAULT 'pending',
    decided_at TIMESTAMP NULL,
    decided_by BIGINT UNSIGNED NULL COMMENT 'Admin user ID',
    
    -- Decision Details
    approval_notes TEXT NULL,
    rejection_reason TEXT NULL,
    conditions TEXT NULL COMMENT 'Conditional approval requirements',
    
    -- Multi-Approver Support (Future)
    requires_multi_approval BOOLEAN DEFAULT false,
    approvers_required INT DEFAULT 1,
    approvers_completed INT DEFAULT 0,
    approvers JSON NULL COMMENT 'Array of {user_id, approved_at, notes}',
    
    -- Compliance Checks
    compliance_checks_passed BOOLEAN DEFAULT false,
    compliance_check_results JSON NULL COMMENT 'Automated validation results',
    
    -- Revocation (Post-Approval)
    is_revoked BOOLEAN DEFAULT false,
    revoked_at TIMESTAMP NULL,
    revoked_by BIGINT UNSIGNED NULL,
    revocation_reason TEXT NULL,
    
    -- Audit Trail
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX (company_disclosure_id),
    INDEX (disclosure_version_id),
    INDEX (status),
    INDEX (requested_at),
    INDEX (decided_at),
    
    FOREIGN KEY (company_disclosure_id) REFERENCES company_disclosures(id) ON DELETE CASCADE,
    FOREIGN KEY (disclosure_version_id) REFERENCES disclosure_versions(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES company_users(id) ON DELETE SET NULL,
    FOREIGN KEY (decided_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (revoked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Why This Design:**
- **Approval History:** Track every approval request
- **Multi-Approver:** Future support for committee-based approvals
- **Revocation:** Can revoke approval if fraud discovered
- **Compliance Integration:** Store automated check results

**State Machine:**
```
pending → approved → (locked in disclosure_versions)
    ↓
rejected → (company can resubmit new version)

approved → revoked (emergency only)
```

---

## COMPLETE SCHEMA DIAGRAM

```
companies (existing - extended)
    ├── company_users (existing)
    ├── company_versions (existing - keep for backward compat)
    └── company_disclosures (NEW)
            ├── disclosure_modules (NEW - template)
            ├── disclosure_versions (NEW - versioned data)
            │       ├── company_documents (existing - supporting docs)
            │       └── disclosure_approvals (NEW - workflow)
            └── disclosure_clarifications (NEW - Q&A)
```

---

## GOVERNANCE RULES (Enforced)

### Rule 1: Immutability After Approval

**Enforcement:**
```sql
-- disclosure_versions: is_locked = true when approved
-- Model Observer blocks updates/deletes
-- CompanyObserver already protects company.frozen_at
```

### Rule 2: One Active Disclosure Per Module

**Enforcement:**
```sql
-- company_disclosures: UNIQUE(company_id, disclosure_module_id, status)
-- Application validates before insert
```

### Rule 3: Version Sequence Integrity

**Enforcement:**
```sql
-- disclosure_versions: UNIQUE(company_disclosure_id, version_number)
-- Auto-increment version_number in service layer
```

### Rule 4: Clarifications Must Be Resolved Before Approval

**Enforcement:**
```sql
-- Application checks: unresolved_clarifications_count = 0
-- Approval workflow validates this condition
```

### Rule 5: No Hard Deletes

**Enforcement:**
```sql
-- All tables have deleted_at (SoftDeletes)
-- Observer prevents hard delete on locked versions
```

---

## BACKWARD COMPATIBILITY GUARANTEES

### 1. Existing Company Module Unaffected

- ✅ `companies` table: Only additive columns (nullable)
- ✅ `company_versions`: Untouched, continues to work
- ✅ `CompanyService`: New fields optional
- ✅ `CompanyObserver`: Works with new fields (nullable = ignored)

### 2. Existing APIs Continue Working

- ✅ `GET /api/v1/companies` - Returns companies + new fields (null if not set)
- ✅ `POST /api/v1/admin/companies` - New fields optional in validation
- ✅ `PUT /api/v1/company/company-profile/update` - New fields ignored if not sent

### 3. Existing Frontend Unaffected

- ✅ Public company listing page - New fields don't break rendering
- ✅ Company detail page - New fields can be optionally displayed
- ✅ Admin company CRUD - New fields appear as optional

### 4. Migration Path

```php
// Migration 1: Extend companies table (nullable fields)
// Migration 2: Create disclosure_modules table
// Migration 3: Create company_disclosures table
// Migration 4: Create disclosure_versions table
// Migration 5: Create disclosure_clarifications table
// Migration 6: Create disclosure_approvals table

// All migrations additive, rollback safe
```

---

## NEXT STEPS

1. ✅ Architecture design complete
2. ⏭️ Create migration files
3. ⏭️ Create Eloquent models
4. ⏭️ Create service layer
5. ⏭️ Document API contracts

---

**END OF ARCHITECTURE DESIGN**
