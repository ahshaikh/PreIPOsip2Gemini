# PHASE 1: EXISTING SCHEMA ANALYSIS
**Pre-Implementation Inspection**

---

## EXISTING COMPANY SCHEMA (From Audit)

### Primary Table: `companies`

**Current Structure:**
```sql
-- Migration: 2025_12_02_100001_create_content_management_tables.php
CREATE TABLE companies (
    -- Identity
    id BIGINT UNSIGNED PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    
    -- Contact
    email VARCHAR(255) NULL,
    phone VARCHAR(255) NULL,
    address VARCHAR(255) NULL,
    city VARCHAR(255) NULL,
    state VARCHAR(255) NULL,
    country VARCHAR(255) NULL,
    
    -- Profile
    description TEXT NULL,
    logo VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    sector VARCHAR(255) NOT NULL,              -- ⚠️ Legacy string
    sector_id BIGINT UNSIGNED NULL,            -- ✅ New FK (incomplete migration)
    founded_year VARCHAR(4) NULL,
    headquarters VARCHAR(255) NULL,
    ceo_name VARCHAR(255) NULL,
    employees_count INT NULL,
    
    -- Financial
    latest_valuation DECIMAL(20,2) NULL,
    funding_stage VARCHAR(255) NULL,
    total_funding DECIMAL(20,2) NULL,
    
    -- Social
    linkedin_url VARCHAR(255) NULL,
    twitter_url VARCHAR(255) NULL,
    facebook_url VARCHAR(255) NULL,
    
    -- Metadata (JSON)
    key_metrics JSON NULL,
    investors JSON NULL,
    
    -- Platform Control Flags
    is_featured BOOLEAN DEFAULT false,
    status ENUM('active', 'inactive') DEFAULT 'active',
    is_verified BOOLEAN DEFAULT false,         -- Added in enhancement migration
    profile_completed BOOLEAN DEFAULT false,   -- Added in enhancement migration
    profile_completion_percentage INT DEFAULT 0, -- Added in enhancement migration
    
    -- Freeze Mechanism (FIX 5)
    frozen_at TIMESTAMP NULL,                  -- Added in freeze migration
    frozen_by_admin_id BIGINT UNSIGNED NULL,   -- Added in freeze migration
    
    -- Enterprise Features (Multi-tenant)
    max_users_quota INT NULL,                  -- Added in multi-tenant audit
    settings JSON NULL,                        -- Added in multi-tenant audit
    
    -- Timestamps
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,  -- SoftDeletes
    
    -- Indexes
    INDEX(sector),
    INDEX(status),
    INDEX(frozen_at),
    
    -- Foreign Keys
    FOREIGN KEY (frozen_by_admin_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE SET NULL
);
```

---

## EXISTING VERSIONING SYSTEM

### Table: `company_versions`

**Purpose:** Immutable audit trail (FIX 33, 34, 35)

```sql
-- Migration: 2026_01_08_000001_create_company_versions_table.php
CREATE TABLE company_versions (
    id BIGINT UNSIGNED PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    version_number INT NOT NULL,
    
    -- Snapshot Data
    snapshot_data JSON NOT NULL,              -- ⚠️ Full company object
    changed_fields JSON NULL,                 -- Array of field names
    change_summary TEXT NULL,
    field_diffs JSON NULL,                    -- {field: {old, new}}
    
    -- Approval Snapshots (FIX 35)
    is_approval_snapshot BOOLEAN DEFAULT false,
    deal_id BIGINT UNSIGNED NULL,
    
    -- Protection
    is_protected BOOLEAN DEFAULT false,
    protected_at TIMESTAMP NULL,
    protection_reason TEXT NULL,
    
    -- Audit Trail
    created_by BIGINT UNSIGNED NULL,
    created_by_type VARCHAR(255) DEFAULT 'user',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY (company_id, version_number),
    INDEX (company_id, version_number),
    INDEX (is_approval_snapshot),
    INDEX (is_protected),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

**⚠️ Issues:**
- Stores FULL company object in snapshot_data (not modular)
- No structured disclosure modules
- Cannot version individual disclosure sections
- Tightly coupled to Company model structure

---

### Table: `company_snapshots`

**Purpose:** Regulatory snapshots at critical events (FIX 5)

```sql
-- Migration: 2026_01_07_100001_add_frozen_at_to_companies.php
CREATE TABLE company_snapshots (
    id BIGINT UNSIGNED PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    company_share_listing_id BIGINT UNSIGNED NULL,
    bulk_purchase_id BIGINT UNSIGNED NULL,
    
    snapshot_data JSON NOT NULL,              -- ⚠️ Full company data
    snapshot_reason VARCHAR(255) NOT NULL,    -- 'listing_approval', 'deal_launch'
    snapshot_at TIMESTAMP NOT NULL,
    snapshot_by_admin_id BIGINT UNSIGNED NULL,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX (company_id, snapshot_at),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (company_share_listing_id) REFERENCES company_share_listings(id),
    FOREIGN KEY (bulk_purchase_id) REFERENCES bulk_purchases(id),
    FOREIGN KEY (snapshot_by_admin_id) REFERENCES users(id) ON DELETE SET NULL
);
```

**⚠️ Issues:**
- Similar to company_versions (full snapshot approach)
- No modular disclosure support

---

## ANALYSIS: SAFE EXTENSION POINTS

### ✅ Safe to Extend: `companies` table

**Rationale:**
- Already has enhancement migrations (is_verified, frozen_at, sector_id)
- Pattern established for adding platform control fields
- No breaking changes if columns are nullable
- Service layer (CompanyService) can handle new fields

**Proposed Extensions:**
- Legal identity fields (CIN, PAN, registration_number)
- Governance metadata (board_size, independent_directors)
- Compliance flags (sebi_registered, sebi_approval_date)
- Disclosure status tracking (disclosure_stage, disclosure_approved_at)

**Risk:** LOW
- Migrations are additive only
- Existing code won't break (fields nullable)
- Observer protects frozen companies

---

### ⚠️ Risky to Modify: `company_versions`

**Why:**
- Immutability enforced by Model (cannot update)
- Existing snapshots rely on current structure
- Changing snapshot_data structure = breaking change

**Proposal:** DO NOT MODIFY
- Create NEW table: `company_disclosures` (modular approach)
- Keep company_versions for backward compatibility
- Use company_versions for general audit trail
- Use company_disclosures for structured governance data

**Risk if Modified:** HIGH
- Could corrupt existing audit trail
- Breaks immutability guarantees
- Regulatory compliance failure

---

### ✅ Safe to Create: New Tables

**Rationale:**
- No impact on existing functionality
- Foreign keys ensure referential integrity
- Can coexist with existing system

**Proposed New Tables:**
1. `disclosure_modules` - Template/structure for disclosure types
2. `company_disclosures` - Modular disclosure instances
3. `disclosure_versions` - Versioned disclosure data
4. `disclosure_clarifications` - Q&A system
5. `disclosure_approvals` - Approval workflow tracking

**Risk:** NONE
- Additive only
- No existing code depends on these

---

## RISKS & COUPLING IDENTIFIED

### Risk 1: CompanyObserver Immutability

**File:** `backend/app/Observers/CompanyObserver.php`

**Current Behavior:**
- Blocks edits to 59 fields when `frozen_at` is set
- Throws RuntimeException on violation
- Allows super-admin override (logged)

**Governance Impact:**
- ✅ Good: Protects approved disclosures
- ⚠️ Challenge: How to update disclosure WITHOUT updating company?
- 💡 Solution: Store disclosures in SEPARATE table, not companies table

**Action:** Leverage existing freeze mechanism, don't modify

---

### Risk 2: CompanyVersion Auto-Creation

**File:** `backend/app/Models/Company.php:124-158`

**Current Behavior:**
- `saved()` hook creates CompanyVersion on ANY change to versionable fields
- Full snapshot stored in JSON

**Governance Impact:**
- ⚠️ Could create conflicting versions if disclosure system also versions
- ⚠️ Performance overhead (two versioning systems)

**Solution:**
- Disable company_versions for disclosure-related fields
- OR: Make disclosure system primary, keep company_versions for audit
- Document which system is authoritative

---

### Risk 3: FundingRoundController Side Effects

**File:** `backend/app/Http/Controllers/Api/Company/FundingRoundController.php:80-87`

**Current Behavior:**
- Updates `company.total_funding` when funding round created
- Updates `company.latest_valuation` directly

**Governance Impact:**
- ❌ BAD: Business logic in controller
- ❌ BAD: Bypasses disclosure approval workflow
- ❌ BAD: Could modify frozen company data

**Solution:**
- Refactor to service layer (as identified in B.4 audit)
- Route through disclosure system
- Require admin approval for financial changes

---

### Risk 4: Sector Migration Incomplete

**Current State:**
- `companies.sector` (string) - Legacy, still in use
- `companies.sector_id` (FK) - New, nullable, not enforced

**Governance Impact:**
- ⚠️ Data inconsistency
- ⚠️ Unclear which field is authoritative
- ⚠️ Migration path uncertain

**Recommendation:**
- COMPLETE sector migration BEFORE governance rollout
- Prioritize in Phase 0.5 (pre-Phase 1)

---

## EXTENSION STRATEGY

### 1. Companies Table Extensions

**Add to existing table:**
- Legal identity fields (regulatory requirement)
- Governance metadata (board composition)
- Disclosure lifecycle fields (stage, approval)
- Compliance timestamps (SEBI approval, listing dates)

**Method:** Additive migration
**Risk:** LOW
**Backward Compat:** ✅ Yes (nullable fields)

---

### 2. New Disclosure Tables

**Create separate tables:**
- Modular disclosure architecture
- Version management independent of company_versions
- Clarification workflow
- Approval tracking

**Method:** New migrations
**Risk:** NONE
**Backward Compat:** ✅ Yes (no existing dependencies)

---

### 3. Preserve Existing Versioning

**Keep company_versions as-is:**
- General audit trail
- Backward compatibility
- Non-disclosure changes

**Use disclosure_versions for:**
- Structured disclosure data
- Module-specific versioning
- Approval snapshots

**Coexistence Strategy:**
- Two versioning systems in parallel
- Clear documentation of which is authoritative
- Migration path to eventually consolidate (future)

---

## NEXT STEPS

1. ✅ Schema analysis complete
2. ⏭️ Propose companies table extensions
3. ⏭️ Design disclosure table architecture
4. ⏭️ Create migrations
5. ⏭️ Create models
6. ⏭️ Document architecture

---

**END OF SCHEMA ANALYSIS**
