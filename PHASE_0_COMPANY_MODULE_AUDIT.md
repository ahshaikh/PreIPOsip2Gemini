# PHASE 0: COMPANY MODULE COMPREHENSIVE AUDIT
**PreIPOsip Platform - Governance Protocol Implementation**

**Date:** January 10, 2026  
**Auditor:** Claude (Principal Systems Auditor)  
**Scope:** Complete Company Module Architecture (Source → Flow → Consumption)  
**Purpose:** Pre-implementation audit before adding governance protocol features

---

## EXECUTIVE SUMMARY

This audit provides a complete architectural map of the Company module, documenting all data sources, storage mechanisms, transformations, business logic, and consumption points. This foundation is **required** before implementing any new governance features.

### Key Architectural Findings

**✅ STRENGTHS:**
- Advanced versioning system with immutability controls (FIX 33, 34, 35)
- Comprehensive freeze mechanism for regulatory compliance (FIX 5)
- Well-separated service layer abstraction
- Soft deletes with deletion protection
- Observer pattern for cross-cutting concerns
- Multi-tenant infrastructure ready (enterprise features)

**⚠️ CRITICAL RISKS:**
- Sector migration incomplete (string + FK coexistence)
- No formal approval workflow state machine
- Versioning overhead (full snapshots on every save)
- Observer exceptions can block saves
- Dual entity coupling (Company ↔ CompanyUser)
- Missing null checks in 13 of 16 controllers

**🔥 DANGEROUS ZONES (Do Not Touch):**
- `Company::booted()` event handlers (slug, versioning, immutability)
- `CompanyObserver` freeze enforcement logic
- `CompanyVersion` immutability enforcement
- Foreign key cascade rules
- `CompanyUser` approval state machine

**✅ SAFE EXTENSION POINTS:**
- Service layer methods (CompanyService)
- New API endpoints under `/api/v1/company/*`
- Frontend components in `/company/*`
- New scopes on Company model
- Analytics tracking methods

---

## 1. DATA SOURCES — How Company Data is Created

### 1.1 Creation Paths

#### Path A: Public Registration Flow
**Entry:** `POST /api/v1/company/register`  
**Controller:** `App\Http\Controllers\Api\Company\AuthController@register`  
**Service:** `App\Services\CompanyService@registerCompany`  

**Flow Diagram:**
```
User Fills Registration Form (Frontend)
    ↓
POST /api/v1/company/register
    ↓
CompanyService::registerCompany() [DB TRANSACTION]
    ├─→ Create Company
    │   - name, sector, website
    │   - status: 'inactive'
    │   - is_verified: false
    │   - profile_completion_percentage: 10
    │   - Slug auto-generated in Company::booted()
    │
    └─→ Create CompanyUser
        - email, hashed password
        - status: 'pending'
        - is_verified: false
        - email_verified_at: null
    ↓
Fire Registered Event → Email Verification Sent
    ↓
Return { company, user }
```

**File:** `backend/app/Services/CompanyService.php:21-59`

**State After Registration:**
```php
Company {
    status: 'inactive',
    is_verified: false,
    profile_completed: false,
    profile_completion_percentage: 10,
}

CompanyUser {
    status: 'pending',  // Awaiting admin approval
    is_verified: false,
    email_verified_at: null,  // Awaiting email verification
}
```

**Validation Rules:**
```php
'company_name' => 'required|string|max:255',
'sector' => 'required|string|max:255',
'email' => 'required|email|unique:company_users,email',
'password' => 'required|string|min:8|confirmed',
'contact_person_name' => 'required|string|max:255',
'website' => 'nullable|url',
'phone' => 'nullable|string|max:20',
```

**Critical Notes:**
- FIX 19: Email verification required before approval
- Transaction ensures both Company and CompanyUser created atomically
- Failure rolls back both creates
- No direct login after registration (pending approval)

---

#### Path B: Admin Direct Creation
**Entry:** `POST /api/v1/admin/companies`  
**Controller:** `App\Http\Controllers\Api\Admin\CompanyController@store`  
**Middleware:** `permission:products.view`

**Flow Diagram:**
```
Admin Panel Form
    ↓
POST /api/v1/admin/companies
    ↓
Validate Request
    ↓
Company::create($data)
    ↓
Company::creating() hook fires
    ├─→ Generate unique slug
    └─→ Save to database
    ↓
Company::saved() hook fires
    └─→ CompanyVersion::createFromCompany() [Auto-versioning]
    ↓
Return Company object
```

**Key Differences from Registration:**
- NO CompanyUser created
- Admin can set `is_verified`, `is_featured`, `status` immediately
- Can bypass approval workflow
- Slug generation: `company-name` → `company-name-1` if duplicate

**Validation:**
```php
'name' => 'required|string|max:255',
'sector' => 'required|string|max:255',
'status' => 'required|in:active,inactive',
'is_featured' => 'boolean',
'is_verified' => 'boolean',
// All other fields optional
```

**File:** `backend/app/Http/Controllers/Api/Admin/CompanyController.php:32-90`

---

### 1.2 Update Paths

#### Path C: Company Portal Self-Update
**Entry:** `PUT /api/v1/company/company-profile/update`  
**Controller:** `App\Http\Controllers\Api\Company\CompanyProfileController@update`  
**Auth:** `auth:sanctum` (CompanyUser)

**Flow:**
```
CompanyUser (authenticated)
    ↓
Get $company = $companyUser->company
    ↓
Validate update request
    ↓
Company::updating() hook
    ├─→ Check if frozen (CompanyObserver)
    ├─→ Check if hasApprovedListing()
    │   └─→ Block protected fields if true
    └─→ Update slug if name changed
    ↓
$company->update($data)
    ↓
Company::saved() hook
    └─→ Create CompanyVersion snapshot
    ↓
CompanyService::updateProfileCompletion($company)
    ↓
Return updated company
```

**Immutability Rules (FIX 34):**
If `hasApprovedListing()` returns true, these fields CANNOT be changed:
- `name`, `sector`, `founded_year`, `ceo_name`
- `latest_valuation`, `total_funding`

**Protection Bypass:** None (even super-admin cannot bypass via this endpoint)

**File:** `backend/app/Http/Controllers/Api/Company/CompanyProfileController.php:91-130`

---

#### Path D: Admin Override Update
**Entry:** `PUT /api/v1/admin/companies/{id}`  
**Controller:** `App\Http\Controllers\Api\Admin\CompanyController@update`  
**Middleware:** `permission:products.view`

**Flow:**
```
Admin Panel Edit Form
    ↓
PUT /admin/companies/{id}
    ↓
Company::updating() hook
    ├─→ Check if frozen (CompanyObserver)
    │   ├─→ If super-admin: ALLOW + LOG to audit_logs
    │   └─→ Else: THROW RuntimeException
    └─→ Check hasApprovedListing() (same as Path C)
    ↓
$company->update($validated)
    ↓
Company::saved() → CompanyVersion created
    ↓
Return updated company
```

**File:** `backend/app/Http/Controllers/Api/Admin/CompanyController.php:114-155`

---

### 1.3 Mutation Actors & Permissions

| Actor | Create | Update Own | Update All | Delete | Approve | Freeze |
|-------|--------|-----------|-----------|---------|---------|--------|
| **Unauthenticated Public** | ✅ Register | ❌ | ❌ | ❌ | ❌ | ❌ |
| **CompanyUser (Auth)** | ❌ | ✅ Own company | ❌ | ❌ | ❌ | ❌ |
| **Admin** | ✅ Direct | ✅ All (if not frozen) | ✅ All | ⚠️ Protected | ✅ Users | ✅ Yes |
| **Super-Admin** | ✅ | ✅ Override frozen | ✅ | ⚠️ Soft delete | ✅ | ✅ |
| **System (Observer)** | ❌ | ✅ Auto-versioning | ✅ | ❌ | ❌ | ✅ Auto |

**Notes:**
- Deletion protected by `HasDeletionProtection` trait
- Frozen companies: Only super-admin can edit (logged to audit_logs)
- `CompanyObserver` enforces freeze rules at model level

---

## 2. DATA MODEL & STORAGE

### 2.1 Primary Table: `companies`

**Migration:** `2025_12_02_100001_create_content_management_tables.php:128-168`

```sql
CREATE TABLE companies (
    -- Identity
    id                              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name                            VARCHAR(255) NOT NULL,
    slug                            VARCHAR(255) UNIQUE NOT NULL,
    
    -- Contact & Location
    email                           VARCHAR(255) NULL,
    phone                           VARCHAR(255) NULL,
    address                         VARCHAR(255) NULL,
    city                            VARCHAR(255) NULL,
    state                           VARCHAR(255) NULL,
    country                         VARCHAR(255) NULL,
    
    -- Profile
    description                     TEXT NULL,
    logo                            VARCHAR(255) NULL,
    website                         VARCHAR(255) NULL,
    sector                          VARCHAR(255) NOT NULL,  -- Legacy string
    founded_year                    VARCHAR(4) NULL,
    headquarters                    VARCHAR(255) NULL,
    ceo_name                        VARCHAR(255) NULL,
    employees_count                 INT NULL,
    
    -- Financial
    latest_valuation                DECIMAL(20,2) NULL,
    funding_stage                   VARCHAR(255) NULL,  -- 'Seed', 'Series A', etc.
    total_funding                   DECIMAL(20,2) NULL,
    
    -- Social
    linkedin_url                    VARCHAR(255) NULL,
    twitter_url                     VARCHAR(255) NULL,
    facebook_url                    VARCHAR(255) NULL,
    
    -- Metadata
    key_metrics                     JSON NULL,
    investors                       JSON NULL,
    
    -- Flags
    is_featured                     BOOLEAN DEFAULT false,
    status                          ENUM('active', 'inactive') DEFAULT 'active',
    is_verified                     BOOLEAN DEFAULT false,      -- Added later
    profile_completed               BOOLEAN DEFAULT false,      -- Added later
    profile_completion_percentage   INT DEFAULT 0,              -- Added later
    
    -- Enterprise Features (Multi-tenant)
    max_users_quota                 INT NULL,
    settings                        JSON NULL,
    
    -- Timestamps
    created_at                      TIMESTAMP NULL,
    updated_at                      TIMESTAMP NULL,
    deleted_at                      TIMESTAMP NULL,  -- SoftDeletes
    
    -- Indexes
    INDEX(sector),
    INDEX(status)
);
```

---

### 2.2 Enhancement Migrations

#### A. Sector Normalization (Incomplete)
**Migration:** `2026_01_04_233820_add_sector_id_to_companies_table.php`

```sql
ALTER TABLE companies
    ADD COLUMN sector_id BIGINT UNSIGNED NULL AFTER sector,
    ADD FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE SET NULL;
```

**⚠️ CRITICAL ISSUE:**
- Migration added `sector_id` FK but did NOT:
  1. Populate data from `sector` string
  2. Update validation to require `sector_id`
  3. Update API to accept/return `sector_id`
  4. Deprecate `sector` string field

**Current State:** BOTH fields exist, validation still uses string

---

#### B. Freeze Mechanism (FIX 5)
**Migration:** `2026_01_07_100001_add_frozen_at_to_companies.php`

```sql
ALTER TABLE companies
    ADD COLUMN frozen_at TIMESTAMP NULL,
    ADD COLUMN frozen_by_admin_id BIGINT UNSIGNED NULL,
    ADD FOREIGN KEY (frozen_by_admin_id) REFERENCES users(id) ON DELETE SET NULL,
    ADD INDEX (frozen_at);
```

**Purpose:** Prevent retroactive changes after company data used in deals/purchases

**Enforced by:** `CompanyObserver::updating()`

---

### 2.3 Related Tables

#### `company_users`
**Purpose:** Authentication for company portal access  
**Migration:** `2025_12_04_110000_create_company_users_system.php:15-30`

```sql
CREATE TABLE company_users (
    id                              BIGINT UNSIGNED PRIMARY KEY,
    company_id                      BIGINT UNSIGNED NULL,  -- ⚠️ NULLABLE
    email                           VARCHAR(255) UNIQUE NOT NULL,
    password                        VARCHAR(255) NOT NULL,
    contact_person_name             VARCHAR(255) NOT NULL,
    contact_person_designation      VARCHAR(255) NULL,
    phone                           VARCHAR(255) NULL,
    status                          ENUM('pending', 'active', 'suspended', 'rejected') DEFAULT 'pending',
    is_verified                     BOOLEAN DEFAULT false,
    email_verified_at               TIMESTAMP NULL,
    rejection_reason                TEXT NULL,
    remember_token                  VARCHAR(100) NULL,
    created_at                      TIMESTAMP,
    updated_at                      TIMESTAMP,
    deleted_at                      TIMESTAMP NULL,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
```

**State Machine:**
```
pending → (email_verify) → (admin_approve) → active
    ↓                                            ↓
rejected                                    suspended
                                                ↓
                                         (reactivate) → active
```

**Model:** `backend/app/Models/CompanyUser.php`

**Key Methods:**
- `approve()` - Checks email_verified_at, sets active
- `reject($reason)` - Sets rejected status
- `isActive()` - Returns status === 'active' AND is_verified
- `isPending()` - Returns status === 'pending'

---

#### `company_versions` (FIX 33)
**Purpose:** Immutable version history for audit compliance  
**Migration:** `2026_01_08_000001_create_company_versions_table.php`

```sql
CREATE TABLE company_versions (
    id                              BIGINT UNSIGNED PRIMARY KEY,
    company_id                      BIGINT UNSIGNED NOT NULL,
    version_number                  INT NOT NULL,
    snapshot_data                   JSON NOT NULL,  -- Full company object
    changed_fields                  JSON NULL,      -- Array of field names
    change_summary                  TEXT NULL,
    field_diffs                     JSON NULL,      -- {field: {old, new}}
    is_approval_snapshot            BOOLEAN DEFAULT false,  -- FIX 35
    deal_id                         BIGINT UNSIGNED NULL,
    is_protected                    BOOLEAN DEFAULT false,
    protected_at                    TIMESTAMP NULL,
    protection_reason               TEXT NULL,
    created_by                      BIGINT UNSIGNED NULL,
    created_by_type                 VARCHAR(255) DEFAULT 'user',
    ip_address                      VARCHAR(45) NULL,
    user_agent                      TEXT NULL,
    created_at                      TIMESTAMP,
    updated_at                      TIMESTAMP,
    
    UNIQUE KEY (company_id, version_number),
    INDEX (company_id, version_number),
    INDEX (is_approval_snapshot),
    INDEX (is_protected),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

**Immutability Enforcement:**
```php
// CompanyVersion::booted()
static::updating(function () {
    throw new \RuntimeException(
        'Company versions are immutable. Create a new version instead.'
    );
});
```

**Model:** `backend/app/Models/CompanyVersion.php`

**Factory Method:**
```php
CompanyVersion::createFromCompany(
    Company $company,
    ?array $changedFields = null,
    ?string $reason = null,
    bool $isApprovalSnapshot = false,
    ?int $approvalId = null
): CompanyVersion
```

**Automatic Creation:** Triggered by `Company::saved()` hook when versionable fields change

---

#### `company_snapshots` (FIX 5)
**Purpose:** Regulatory snapshots at critical events  
**Migration:** `2026_01_07_100001_add_frozen_at_to_companies.php:31-47`

```sql
CREATE TABLE company_snapshots (
    id                              BIGINT UNSIGNED PRIMARY KEY,
    company_id                      BIGINT UNSIGNED NOT NULL,
    company_share_listing_id        BIGINT UNSIGNED NULL,
    bulk_purchase_id                BIGINT UNSIGNED NULL,
    snapshot_data                   JSON NOT NULL,
    snapshot_reason                 VARCHAR(255) NOT NULL,
    snapshot_at                     TIMESTAMP NOT NULL,
    snapshot_by_admin_id            BIGINT UNSIGNED NULL,
    created_at                      TIMESTAMP,
    updated_at                      TIMESTAMP,
    
    INDEX (company_id, snapshot_at),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (company_share_listing_id) REFERENCES company_share_listings(id),
    FOREIGN KEY (bulk_purchase_id) REFERENCES bulk_purchases(id),
    FOREIGN KEY (snapshot_by_admin_id) REFERENCES users(id) ON DELETE SET NULL
);
```

**Trigger Points:**
- `listing_approval` - When CompanyShareListing approved
- `deal_launch` - When Deal created
- `bulk_purchase` - When inventory purchased

---

### 2.4 Eloquent Relationships

**File:** `backend/app/Models/Company.php:186-261`

```php
// Authentication & Enterprise
company_users() → HasMany → CompanyUser       // Portal users
users() → HasMany → User                      // Multi-tenant investors
plans() → HasMany → Plan                      // Enterprise-exclusive plans

// Content & Documents
financialReports() → HasMany → CompanyFinancialReport
documents() → HasMany → CompanyDocument
teamMembers() → HasMany → CompanyTeamMember
fundingRounds() → HasMany → CompanyFundingRound
updates() → HasMany → CompanyUpdate
webinars() → HasMany → CompanyWebinar

// Deals & Listings
deals() → HasMany → Deal
shareListings() → HasMany → CompanyShareListing

// Versioning (FIX 33)
versions() → HasMany → CompanyVersion (ordered DESC)
approvalSnapshots() → HasMany → CompanyVersion (where is_approval_snapshot = true)
```

---

### 2.5 Model Configuration

**File:** `backend/app/Models/Company.php`

**Traits:**
```php
use HasFactory;              // Factory support
use SoftDeletes;             // Never hard delete
use HasDeletionProtection;   // Prevent deletion with dependencies
use HasWorkflowActions;      // Admin suggested actions
```

**Fillable Fields:**
```php
protected $fillable = [
    'name', 'slug', 'description', 'logo', 'website',
    'sector', 'founded_year', 'headquarters', 'ceo_name',
    'latest_valuation', 'funding_stage', 'total_funding',
    'linkedin_url', 'twitter_url', 'facebook_url',
    'key_metrics', 'investors',
    'is_featured', 'status', 'is_verified',
    'profile_completed', 'profile_completion_percentage',
    'max_users_quota', 'settings',  // Enterprise features
];
```

**Casts:**
```php
protected $casts = [
    'latest_valuation' => 'decimal:2',
    'total_funding' => 'decimal:2',
    'key_metrics' => 'array',
    'investors' => 'array',
    'settings' => 'array',
    'is_featured' => 'boolean',
    'is_verified' => 'boolean',
    'profile_completed' => 'boolean',
];
```

**Deletion Protection:**
```php
protected $deletionProtectionRules = [
    'deals' => 'active deals',
    'products' => 'products/inventory',
    'users' => 'company users',
];
```

---

## 3. BUSINESS LOGIC LAYER

### 3.1 Service: CompanyService

**File:** `backend/app/Services/CompanyService.php`

#### Method: `registerCompany(array $data): array`

**Purpose:** Handle complete company registration with transactional integrity

**Transaction:** YES (DB::transaction)

**Process:**
```php
1. Create Company
   - name, sector, website
   - status: 'inactive'
   - is_verified: false
   - profile_completion_percentage: 10 (base score)

2. Create CompanyUser
   - Hashed password
   - status: 'pending'
   - is_verified: false

3. Fire Registered Event
   - Sends email verification link

4. Log registration

5. Return ['company' => Company, 'user' => CompanyUser]
```

**Error Handling:** Transaction rolls back on exception

**Lines:** 21-59

---

#### Method: `updateProfileCompletion(Company $company): void`

**Purpose:** Calculate and update profile completion percentage

**Algorithm:**
```php
Base Fields (77 points):
    name: 5%
    description: 10%
    logo: 10%
    website: 5%
    sector: 5%
    founded_year: 5%
    headquarters: 5%
    ceo_name: 5%
    latest_valuation: 10%
    funding_stage: 5%
    total_funding: 5%
    linkedin_url: 3%
    twitter_url: 2%
    facebook_url: 2%

Relationship Bonuses (28 points):
    teamMembers()->exists(): +10%
    financialReports()->exists(): +10%
    fundingRounds()->exists(): +5%
    documents()->exists(): +3%

Total: Capped at 100%
Profile Completed Flag: >= 80%
```

**⚠️ Performance Issue:**
- Executes 4 database queries (`exists()`) on relationships
- Not eager loaded
- Could optimize with single query + count check

**Side Effects:**
- Updates `profile_completion_percentage`
- Sets `profile_completed` boolean

**Lines:** 67-119

---

### 3.2 Observer: CompanyObserver

**File:** `backend/app/Observers/CompanyObserver.php`

**Purpose:** Enforce immutability of company data after freeze (FIX 5 - Regulatory compliance)

#### Event: `updating(Company $company)`

**Immutable Fields (59 total):**
```php
// Core Identity
'name', 'sector', 'founded_year', 'headquarters', 'ceo_name',
'website', 'cin', 'pan',

// Financial Disclosures
'latest_valuation', 'total_funding', 'funding_stage',
'last_funding_round', 'last_funding_date', 'last_funding_amount',
'revenue_last_year', 'net_profit_last_year', 'burn_rate',

// Regulatory
'sebi_registered', 'sebi_registration_number', 'legal_structure',

// Product/Market
'product_description', 'market_segment', 'competitive_advantage',
'key_customers', 'key_partners',

// Risk Disclosures
'risk_factors', 'pending_litigations', 'regulatory_risks', 'market_risks',
// ... (full list: 59 fields)
```

**Logic:**
```php
1. IF company.frozen_at IS NULL:
   → ALLOW (no restrictions)

2. IF auth()->user()->hasRole('super-admin'):
   → ALLOW edit
   → LOG override to audit_logs table
   → CRITICAL: All changes tracked

3. ELSE:
   → CHECK getDirty() for immutable field violations
   → IF violations found:
      → Log::critical() with context
      → THROW RuntimeException
      → BLOCK save operation
```

**Error Message:**
```
"Company data is frozen after inventory purchase. Cannot edit: [field names].
Only additive disclosures allowed via CompanyUpdate model.
Contact super-admin for corrective edits."
```

**Critical Notes:**
- Exception bubbles up to controller
- Returns generic 500 error (not HTTP-aware)
- Blocks entire save operation (no partial updates)

---

#### Event: `deleting(Company $company)`

```php
IF company.frozen_at IS NOT NULL:
    → Log::critical()
    → THROW RuntimeException("Cannot delete frozen company")
    → Suggest soft delete instead
```

---

### 3.3 Model Boot Hooks

**File:** `backend/app/Models/Company.php:76-159`

#### Event: `creating`

```php
static::creating(function ($company) {
    if (empty($company->slug)) {
        $company->slug = static::generateUniqueSlug($company->name);
    }
});
```

**Slug Generation:**
```php
1. Base slug: Str::slug($name)
2. Check if exists in database
3. If exists, append: {slug}-1, {slug}-2, etc.
4. Continue until unique found

Example:
  "SpaceX" → "spacex"
  "SpaceX" (2nd) → "spacex-1"
  "SpaceX" (3rd) → "spacex-2"
```

**Critical:** Slug generation moved to model (FIX: Module 18)
- Previously in controller with random suffixes
- Now predictable and clean

---

#### Event: `updating`

```php
static::updating(function ($company) {
    // FIX 34: Enforce immutability after listing approval
    if ($company->hasApprovedListing()) {
        $protectedFields = [
            'name', 'sector', 'founded_year', 'ceo_name',
            'latest_valuation', 'total_funding',
        ];

        $changedProtectedFields = array_intersect(
            $protectedFields,
            array_keys($company->getDirty())
        );

        if (!empty($changedProtectedFields)) {
            throw new \RuntimeException(
                "Cannot modify protected fields after listing approval: " .
                implode(', ', $changedProtectedFields)
            );
        }
    }

    // Auto-update slug if name changed
    if ($company->isDirty('name') && !$company->isDirty('slug')) {
        $company->slug = static::generateUniqueSlug($company->name, $company->id);
    }
});
```

**Protection Check:**
```php
public function hasApprovedListing(): bool
{
    $hasApprovedShareListing = CompanyShareListing::where('company_id', $this->id)
        ->where('status', 'approved')
        ->exists();

    $hasActiveDeal = $this->deals()
        ->where('status', 'active')
        ->exists();

    return $hasApprovedShareListing || $hasActiveDeal;
}
```

**Lines:** 388-400

---

#### Event: `saved`

```php
static::saved(function ($company) {
    // FIX 33: Create version snapshot after save

    $versionableFields = [
        'name', 'description', 'logo', 'website', 'sector',
        'founded_year', 'headquarters', 'ceo_name', 'latest_valuation',
        'funding_stage', 'total_funding', 'key_metrics', 'investors',
        'is_verified', 'status',
    ];

    $changedFields = $company->wasChanged()
        ? array_intersect($versionableFields, array_keys($company->getChanges()))
        : [];

    if (!empty($changedFields)) {
        try {
            CompanyVersion::createFromCompany(
                $company,
                $changedFields,
                'Company data updated'
            );
        } catch (\Exception $e) {
            Log::error('Failed to create company version', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            // SWALLOWS EXCEPTION - Continues silently
        }
    }
});
```

**⚠️ CRITICAL ISSUE:**
- Exception is caught and logged
- Save operation continues even if versioning fails
- Could lead to compliance gaps
- No admin notification of versioning failure

**Lines:** 124-158

---

### 3.4 Versioning System (FIX 33, 34, 35)

**Model:** `App\Models\CompanyVersion`  
**File:** `backend/app/Models/CompanyVersion.php`

#### Immutability Enforcement

```php
protected static function booted()
{
    static::updating(function () {
        throw new \RuntimeException(
            'Company versions are immutable. Create a new version instead.'
        );
    });
}
```

**Result:** Once created, versions can NEVER be updated

---

#### Factory Method: `createFromCompany()`

**Signature:**
```php
public static function createFromCompany(
    Company $company,
    ?array $changedFields = null,
    ?string $reason = null,
    bool $isApprovalSnapshot = false,
    ?int $approvalId = null
): CompanyVersion
```

**Process:**
```php
1. Get latest version_number for company
2. Increment: $versionNumber = ($latest ?? 0) + 1

3. Auto-detect changed fields if not provided:
   $changedFields = array_keys($company->getDirty())

4. Generate change summary:
   "Updated: Company Name, Valuation, CEO Name (Reason)"

5. Create CompanyVersion:
   - snapshot_data: $company->toArray() [FULL SNAPSHOT]
   - changed_fields: ['name', 'valuation', ...]
   - change_summary: "Updated: ..."
   - created_by: auth()->id()
   - is_approval_snapshot: bool
   - approval_id: reference
   - ip_address, user_agent

6. Return CompanyVersion
```

**⚠️ STORAGE OVERHEAD:**
- Stores FULL company object in JSON
- No compression
- No diff-based storage
- Large database growth over time

---

#### Helper Methods

```php
// Company Model (Lines: 382-439)

getLatestVersion(): ?CompanyVersion
  → Returns most recent version

getVersionCount(): int
  → Total versions for this company

createApprovalSnapshot(int $approvalId, string $approvalType): CompanyVersion
  → Create immutable snapshot at approval
  → Sets is_approval_snapshot = true

getOriginalApprovalSnapshot(): ?CompanyVersion
  → Get FIRST approval snapshot (baseline)
```

---

## 4. DATA CONSUMPTION — APIs & Frontend

### 4.1 Backend API Endpoints

#### Public Endpoints (Unauthenticated)

| Method | Endpoint | Controller | Purpose |
|--------|----------|------------|---------|
| GET | `/api/v1/companies` | `Public\CompanyProfileController@index` | List verified companies |
| GET | `/api/v1/companies/sectors` | `Public\CompanyProfileController@sectors` | Distinct sectors |
| GET | `/api/v1/companies/{slug}` | `Public\CompanyProfileController@show` | Company detail |
| POST | `/api/v1/company/register` | `Company\AuthController@register` | Registration |
| POST | `/api/v1/company/login` | `Company\AuthController@login` | Login |

**Filters (index):**
- `sector`: String filter
- `search`: Name/description
- `sort_by`: `latest`, `valuation_high`, `valuation_low`, `name`
- `page`, `per_page`

**Show Endpoint:**
- Only shows: `status = 'active'` AND `is_verified = true`
- Eager loads: financialReports, documents (public only), teamMembers, fundingRounds, updates
- Tracks analytics: `CompanyAnalytics::incrementMetric('profile_views')`

---

#### Company Portal Endpoints (auth:sanctum, CompanyUser)

| Method | Endpoint | Controller | Purpose |
|--------|----------|------------|---------|
| POST | `/api/v1/company/logout` | `AuthController@logout` | Revoke token |
| GET | `/api/v1/company/profile` | `AuthController@profile` | Get user + company |
| PUT | `/api/v1/company/profile` | `AuthController@updateProfile` | Update contact |
| POST | `/api/v1/company/change-password` | `AuthController@changePassword` | Password |
| PUT | `/api/v1/company/company-profile/update` | `CompanyProfileController@update` | Update company |
| POST | `/api/v1/company/company-profile/upload-logo` | `CompanyProfileController@uploadLogo` | Logo upload |
| GET | `/api/v1/company/company-profile/dashboard` | `CompanyProfileController@dashboard` | Dashboard stats |

**Dashboard Response:**
```json
{
  "success": true,
  "stats": {
    "profile_completion": 10,
    "financial_reports_count": 0,
    "documents_count": 0,
    "team_members_count": 0,
    "funding_rounds_count": 0,
    "updates_count": 0,
    "published_updates_count": 0,
    "is_verified": false,
    "status": "inactive"
  },
  "company": { /* Full object */ }
}
```

**Logo Upload:**
- Validation: `image|mimes:jpeg,png,jpg,svg|max:2048`
- Storage: `storage/company-logos/{filename}`
- Deletes old logo
- Triggers profile completion update (+10%)

---

#### Admin Endpoints (permission:products.view)

| Method | Endpoint | Middleware | Purpose |
|--------|----------|------------|---------|
| GET | `/api/v1/admin/companies` | `permission:products.view` | List all |
| POST | `/api/v1/admin/companies` | `permission:products.view` | Create |
| GET | `/api/v1/admin/companies/{id}` | `permission:products.view` | Get |
| PUT | `/api/v1/admin/companies/{id}` | `permission:products.view` | Update |
| DELETE | `/api/v1/admin/companies/{id}` | `permission:products.view` | Delete |

**Company Users Management:**

| Method | Endpoint | Middleware | Purpose |
|--------|----------|------------|---------|
| GET | `/api/v1/admin/company-users` | `permission:users.view` | List users |
| GET | `/api/v1/admin/company-users/statistics` | `permission:users.view` | Stats |
| POST | `/api/v1/admin/company-users/{id}/approve` | `permission:users.edit` | Approve |
| POST | `/api/v1/admin/company-users/{id}/reject` | `permission:users.edit` | Reject |
| POST | `/api/v1/admin/company-users/{id}/suspend` | `permission:users.suspend` | Suspend |
| POST | `/api/v1/admin/company-users/{id}/reactivate` | `permission:users.edit` | Reactivate |

**Versioning Endpoints:**

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v1/admin/company-versions` | List all versions |
| GET | `/api/v1/admin/company-versions/stats` | Version stats |
| GET | `/api/v1/admin/company-versions/compare` | Compare versions |
| GET | `/api/v1/admin/companies/{company}/versions` | Company versions |
| GET | `/api/v1/admin/companies/{company}/approval-snapshots` | Approval snapshots |
| GET | `/api/v1/admin/companies/{company}/version-timeline` | Timeline |
| GET | `/api/v1/admin/companies/{company}/field-history/{field}` | Field history |

---

### 4.2 Frontend Consumption

#### Public Pages

**Route:** `/companies`  
**File:** `frontend/app/(public)/companies/page.tsx`

**Features:**
- Grid layout (responsive)
- Search + sector filter
- Sort options
- Pagination
- Company cards with logo, sector, location, valuation, verified badge

**API Calls:**
```typescript
useQuery(['sectors'], () => api.get('/companies/sectors'))
useQuery(['companies', page, search, sector, sortBy], () =>
  api.get('/companies', { params: {...} })
)
```

---

**Route:** `/companies/{slug}`  
**File:** `frontend/app/(public)/companies/[slug]/page.tsx`

**Features:**
- Hero section
- Tabbed content: Overview, Team, Financials, Documents, Updates
- ⚠️ **Key metrics sidebar - MOCK DATA**
- ⚠️ **Shareholders chart - MOCK DATA**
- ⚠️ **Financial charts - MOCK DATA**
- YouTube embeds
- Active deals
- Express interest CTA

**API Call:**
```typescript
useQuery(['company', slug], () => api.get(`/companies/${slug}`))
```

**⚠️ CRITICAL ISSUE:**
```typescript
const mockKeyIndicators = {
  faceValue: 10,
  bookValue: 850,
  peRatio: 45.2,
  // ... FAKE DATA IN PRODUCTION
};
```

---

#### Company Portal

**Authentication:**
- Separate token: `localStorage.company_token`
- Separate client: `lib/companyApi.ts`

**Route:** `/company/dashboard`  
**File:** `frontend/app/company/dashboard/page.tsx`

**API Call:**
```typescript
useQuery(['company-dashboard'], () =>
  companyApi.get('/company-profile/dashboard')
)
```

---

**Route:** `/company/profile`  
**File:** `frontend/app/company/profile/page.tsx`

**Features:**
- Logo upload with preview
- Three-section form: Basic, Financial, Social
- Client-side validation (⚠️ Can be bypassed)

**API Calls:**
```typescript
useQuery(['company-profile'], () => companyApi.get('/profile'))

useMutation(data => companyApi.put('/company-profile/update', data))

useMutation(file => {
  const formData = new FormData();
  formData.append('logo', file);
  return companyApi.post('/company-profile/upload-logo', formData);
})
```

**⚠️ BUG (FIXED):**
- Previously used `useState(() => {...}, [company])`
- useState doesn't accept dependency arrays
- Changed to `useEffect` in FIX

---

#### Admin Panel

**Route:** `/admin/content/companies`  
**File:** `frontend/app/admin/content/companies/page.tsx`

**Features:**
- Data table with sorting
- Search
- Add/Edit dialog
- Delete confirmation

**API Calls:**
```typescript
useQuery(['admin-companies', search], () =>
  api.get('/admin/companies', { params: { search } })
)

useMutation(newCompany => api.post('/admin/companies', newCompany))
useMutation(({ id, data }) => api.put(`/admin/companies/${id}`, data))
useMutation(id => api.delete(`/admin/companies/${id}`))
```

---

**Route:** `/admin/company-users`  
**File:** `frontend/app/admin/company-users/page.tsx`

**Features:**
- Dashboard cards: Total, Pending, Active, Verified, Suspended
- Status filters
- Approve/Reject/Suspend actions
- Reason dialogs

**API Calls:**
```typescript
useQuery(['company-users-stats'], () =>
  api.get('/admin/company-users/statistics')
)

useMutation(id => api.post(`/admin/company-users/${id}/approve`))
useMutation(({ id, reason }) =>
  api.post(`/admin/company-users/${id}/reject`, { rejection_reason: reason })
)
```

---

## 5. RISK & SMELL DETECTION

### 5.1 Critical Risks

#### RISK 1: Sector Migration Incomplete
**Severity:** HIGH  
**Impact:** Data inconsistency, query failures

**Evidence:**
- Migration added `sector_id` FK
- Validation still requires `sector` string
- No data migration script
- Frontend still sends/receives string
- Queries use string: `where('sector', $request->sector)`

**Files:**
- `2026_01_04_233820_add_sector_id_to_companies_table.php`
- `CompanyController.php` validation
- `companies/page.tsx` frontend

**Recommendation:**
1. Write data migration script
2. Update validation to `sector_id`
3. Update frontend to send FK
4. Deprecate string field

---

#### RISK 2: Versioning Overhead
**Severity:** MEDIUM  
**Impact:** Database growth, performance

**Evidence:**
- Full snapshot stored on EVERY save
- No compression
- No pruning strategy
- Synchronous operation (delays response)

**Example:**
- 100 companies × 50 edits each = 5,000 version records
- Each with full JSON snapshot
- No retention policy

**Recommendation:**
1. Queue version creation
2. Store diffs instead of snapshots
3. Implement pruning (keep X recent)
4. Exclude non-critical fields

---

#### RISK 3: Observer Exception Handling
**Severity:** HIGH  
**Impact:** Silent failures, compliance gaps

**Evidence:**
```php
// Company::saved() - Lines 150-156
catch (\Exception $e) {
    Log::error('Failed to create company version', [...]);
    // SWALLOWS EXCEPTION
}
```

**Issue:**
- Versioning failure hidden from user
- No admin notification
- Could lead to audit gaps
- User assumes version created

**Recommendation:**
1. Alert admins on failure (email/Slack)
2. Add retry logic
3. Queue with exponential backoff
4. Store failure in monitoring table

---

#### RISK 4: Dual Entity Coupling
**Severity:** MEDIUM  
**Impact:** Refactoring complexity

**Evidence:**
- `Company` = business entity
- `CompanyUser` = authentication
- One-to-many: `company_users.company_id → companies.id`
- Registration creates BOTH
- Admin creation creates Company WITHOUT CompanyUser

**Questions:**
- Can CompanyUser exist without company_id? (FK allows NULL)
- Can multiple users manage one company? (Relationship implies yes, no enforcement)
- What if CompanyUser deleted but Company remains?

**Recommendation:**
1. Enforce `company_id NOT NULL` constraint
2. Document multi-user workflow
3. Add database constraint
4. Consider junction table if many-to-many

---

#### RISK 5: Missing Null Checks
**Severity:** MEDIUM  
**Impact:** Fatal errors in production

**Evidence:**
- Pattern: `$company = $companyUser->company; $company->id`
- NO null check in 13 of 16 controllers
- If company_id references deleted company → crash

**Files Affected:**
- TeamMemberController (6 methods)
- FinancialReportController (6 methods)
- DocumentController (6 methods)
- FundingRoundController (4 methods)
- CompanyDealController (6 methods)
- CompanyQnaController (5 methods)
- CompanyWebinarController (8 methods)
- CompanyUpdateController (5 methods)
- ShareListingController (6 methods)
- OnboardingWizardController (4 methods)
- CompanyAnalyticsController (2 methods)
- InvestorInterestController (3 methods)
- UserManagementController (2 methods)

**Mitigation Created:**
- `BaseCompanyController` with `getCompanyOrFail()` helper
- Not yet implemented in controllers

**Recommendation:**
1. Gradually refactor controllers
2. Priority: Profile → Updates → Documents
3. Add to coding standards

---

#### RISK 6: Mock Data in Production
**Severity:** CRITICAL  
**Impact:** Regulatory risk, investor confusion

**Evidence:**
```typescript
// frontend/app/(public)/companies/[slug]/page.tsx
const mockKeyIndicators = {
  faceValue: 10,
  bookValue: 850,
  peRatio: 45.2,
  // ... FAKE FINANCIAL DATA
};
```

**Issue:**
- Production users see FAKE metrics
- No indicator that data is mock
- Misleading for investors
- Potential regulatory violation

**Recommendation:**
1. Remove mock data IMMEDIATELY
2. Show "Data not available" placeholder
3. Add backend endpoints for real data
4. Add admin panel for data entry

---

#### RISK 7: File Storage Strategy
**Severity:** LOW  
**Impact:** Broken images, storage bloat

**Issues:**
1. **Assumption:** Laravel storage link exists
2. **Old files:** Deleted on new upload (breaks version history)
3. **No CDN:** Direct storage access
4. **No optimization:** Large images stored as-is

**Recommendation:**
1. Store absolute URLs or use accessor
2. Keep old files (versioning)
3. Add CDN layer
4. Queue image optimization

---

### 5.2 Smells & Anti-Patterns

#### SMELL 1: Status Ambiguity

**Multiple Status Systems:**
```php
// Company
status: 'active' | 'inactive'
is_verified: boolean
is_featured: boolean
frozen_at: timestamp

// CompanyUser
status: 'pending' | 'active' | 'suspended' | 'rejected'
is_verified: boolean
email_verified_at: timestamp
```

**Confusion:**
- "verified" means different things
- "active" means different things
- No composite methods

**Recommendation:**
```php
// Add to Company model
public function isPubliclyVisible(): bool {
    return $this->status === 'active' 
        && $this->is_verified === true;
}
```

---

#### SMELL 2: Loose Null Checks

**Example:**
```php
// CompanyService::updateProfileCompletion()
if (!empty($company->$field)) {
    $completionPercentage += $weight;
}
```

**Issue:**
- `!empty()` treats `0`, `"0"`, `false` as empty
- No distinction between unset vs zero
- Valid data ignored

**Recommendation:**
```php
if ($company->$field !== null && $company->$field !== '') {
    $completionPercentage += $weight;
}
```

---

#### SMELL 3: N+1 Relationship Checks

**Example:**
```php
if ($company->teamMembers()->exists()) {  // Query 1
    $completionPercentage += 10;
}
if ($company->financialReports()->exists()) {  // Query 2
    $completionPercentage += 10;
}
// ... 4 queries total
```

**Recommendation:**
```php
$company->loadCount(['teamMembers', 'financialReports', ...]);
if ($company->team_members_count > 0) {
    $completionPercentage += 10;
}
```

---

#### SMELL 4: Frontend Validation Only

**Example:**
```typescript
// frontend: year validation
const yearRegex = /^\d{4}$/;
if (!yearRegex.test(founded_year)) {
  errors.founded_year = 'Invalid year';
}

// backend: no validation
'founded_year' => 'nullable|string|max:4',  // Accepts "abcd"
```

**Recommendation:**
```php
'founded_year' => 'nullable|integer|min:1800|max:' . date('Y'),
```

---

### 5.3 Safe Extension Points ✅

**You can safely modify:**

1. **Service Layer**
   - Add methods to `CompanyService`
   - No impact on existing logic

2. **Scopes**
   - Add query scopes to `Company` model
   - Chainable, backward compatible

3. **API Endpoints**
   - Add routes under `/api/v1/company/*`
   - Existing endpoints unchanged

4. **Frontend Pages**
   - Add pages to `/company/*`
   - Existing routes unaffected

5. **Analytics Methods**
   - Add to `CompanyAnalytics` model
   - No coupling

6. **New Observers**
   - Add event handlers (created, deleted)
   - Existing observers continue

7. **Versioning Fields**
   - Add fields to track in versions
   - Existing versions valid

---

### 5.4 Dangerous Refactor Zones 🔥

**DO NOT MODIFY without architect approval:**

1. **Company::booted() Hooks**
   - Controls slug, versioning, immutability
   - Changes affect ALL operations
   - High breakage risk

2. **CompanyObserver Logic**
   - Enforces regulatory compliance
   - Must preserve audit logs
   - Critical for SEBI compliance

3. **CompanyUser Approval Flow**
   - Multi-step state machine
   - Tight coupling with Company status
   - Email verification dependencies

4. **Foreign Key Relationships**
   - Cascades affect data integrity
   - Must migrate existing data
   - Orphan risk

5. **Versioning System**
   - Immutability is regulatory requirement
   - Cannot modify past versions
   - Audit trail critical

6. **Storage Paths**
   - Changing breaks existing URLs
   - Must migrate files
   - Version history references

---

## 6. TECHNICAL DEBT INVENTORY

### High Priority

| Issue | Impact | Effort | File/Location |
|-------|--------|--------|---------------|
| Sector migration incomplete | Data inconsistency | Medium | Migrations, Controllers, Frontend |
| Mock data in production | Regulatory risk | Low | `companies/[slug]/page.tsx` |
| No file cleanup | Storage bloat | Medium | `CompanyProfileController` |
| Versioning overhead | DB growth | High | `Company::saved()` hook |
| Observer error swallowing | Compliance gaps | Low | `Company::saved()` lines 150-156 |

### Medium Priority

| Issue | Impact | Effort | File/Location |
|-------|--------|--------|---------------|
| Status ambiguity | Developer confusion | Low | `Company`, `CompanyUser` models |
| Dual entity coupling | Refactor difficulty | High | Architecture |
| Missing null checks | Fatal errors | Medium | 13 controllers |
| N+1 queries | Performance | Low | `CompanyService:87-106` |

### Low Priority

| Issue | Impact | Effort | File/Location |
|-------|--------|--------|---------------|
| Loose null checks | Data quality | Low | `CompanyService` |
| Frontend-only validation | Security | Low | All forms |
| No image optimization | Performance | Medium | Upload endpoints |

---

## 7. COMPLIANCE & AUDIT NOTES

### 7.1 Implemented Controls ✅

1. **Immutability After Approval**
   - FIX 5: `frozen_at` prevents edits
   - FIX 34: Model hooks enforce protected fields
   - Observer blocks unauthorized changes
   - Super-admin override logged

2. **Version History**
   - FIX 33: Auto-versioning
   - FIX 35: Approval snapshots
   - Immutable records
   - Field-level tracking

3. **Audit Trails**
   - User ID on all changes
   - IP address + user agent
   - Observer logs violations
   - Super-admin overrides logged

4. **Data Retention**
   - Soft deletes
   - Versions survive changes
   - Snapshots linked to events

### 7.2 Gaps

1. **No Formal Approval Workflow**
   - No approval status tracking
   - No multi-approver support
   - No approval history

2. **No Investor Disclosure Logs**
   - Don't track who viewed what version
   - No timestamp of disclosure
   - Required for disputes

3. **No Data Lineage**
   - No source tracking (manual/import/API)
   - No external validation (MCA, SEBI)

4. **No Backup Strategy**
   - No documented backup policy
   - No restore procedures
   - No off-site storage

---

## 8. CONCLUSION & NEXT STEPS

### 8.1 Readiness Assessment

**Maturity Level:** ★★★★☆ (4/5 - Production Ready with Gaps)

**Foundation Strengths:**
- Sophisticated versioning system
- Immutability controls in place
- Service layer abstraction
- Comprehensive relationships
- Soft delete protection

**Critical Gaps:**
- Sector migration must be completed
- Mock data must be removed
- Versioning overhead needs optimization
- Null safety needs improvement

---

### 8.2 Before Adding Governance Protocol

**MANDATORY Actions:**

1. ✅ **Complete Sector Migration**
   - Migrate data: string → sector_id
   - Update validation
   - Update API contracts
   - Update frontend

2. ✅ **Remove Mock Data**
   - Delete hardcoded financials
   - Add "Not Available" placeholders
   - Create admin entry points

3. ✅ **Add Backend Validation**
   - Phone format
   - Year range (1800-current)
   - URL sanitization

4. ⚠️ **Optimize Versioning**
   - Queue creation
   - Store diffs not snapshots
   - Add pruning

5. ⚠️ **Fix Error Handling**
   - Alert on version failures
   - HTTP-aware observer exceptions
   - Retry logic

---

### 8.3 Governance Protocol Safe Integration Points

**Where to Add New Features:**

1. **Approval Workflow**
   - New table: `company_approvals`
   - New service: `CompanyApprovalService`
   - Hook into existing `CompanyUser::approve()`

2. **Disclosure Management**
   - New relationship: `disclosures()`
   - Observer event: `disclosureViewed`
   - Separate from core company data

3. **Compliance Checks**
   - New service: `CompanyComplianceService`
   - Read-only access to versions
   - No modification of existing data

4. **Audit Reports**
   - New endpoint: `/api/v1/admin/companies/{id}/audit-trail`
   - Aggregate from versions + snapshots
   - No new columns on companies table

---

### 8.4 What NOT to Do

**Prohibited Changes:**

1. ❌ Modify `Company::booted()` hooks
2. ❌ Change `CompanyVersion` structure
3. ❌ Alter `CompanyObserver` freeze logic
4. ❌ Modify foreign key cascades
5. ❌ Change slug generation algorithm
6. ❌ Update existing version records

**Why:** These are critical infrastructure with regulatory implications

---

## APPENDIX A: Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     DATA SOURCES                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   Public     │  │  CompanyUser │  │    Admin     │     │
│  │ Registration │  │ Self-Update  │  │   Direct     │     │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘     │
│         │                  │                  │              │
└─────────┼──────────────────┼──────────────────┼──────────────┘
          │                  │                  │
          ▼                  ▼                  ▼
┌─────────────────────────────────────────────────────────────┐
│                   SERVICE LAYER                              │
├─────────────────────────────────────────────────────────────┤
│  CompanyService::registerCompany()                          │
│  CompanyService::updateProfileCompletion()                  │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                    MODEL LAYER                               │
├─────────────────────────────────────────────────────────────┤
│  Company::creating() → Generate slug                        │
│  Company::updating() → Check immutability + slug update     │
│  Company::saved() → Create CompanyVersion                   │
├─────────────────────────────────────────────────────────────┤
│  CompanyObserver::updating() → Enforce freeze               │
│  CompanyObserver::deleting() → Block frozen deletion        │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                   DATABASE STORAGE                           │
├─────────────────────────────────────────────────────────────┤
│  companies (main data)                                       │
│  company_users (auth)                                        │
│  company_versions (audit trail)                             │
│  company_snapshots (regulatory)                             │
│  company_* (related tables)                                 │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                 CONSUMPTION LAYER                            │
├─────────────────────────────────────────────────────────────┤
│  PUBLIC API → /companies (list, show)                       │
│  COMPANY API → /company/company-profile/* (CRUD)           │
│  ADMIN API → /admin/companies/* (management)                │
│  ADMIN API → /admin/company-versions/* (audit)              │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                   FRONTEND APPS                              │
├─────────────────────────────────────────────────────────────┤
│  /companies → Public listing/detail                         │
│  /company/* → Company portal (auth required)                │
│  /admin/content/companies → Admin management                │
│  /admin/company-users → User approval workflow              │
└─────────────────────────────────────────────────────────────┘
```

---

## APPENDIX B: State Machine Diagram

```
COMPANY STATUS FLOW:
┌──────────┐
│Registration│
└─────┬─────┘
      │ (Service creates)
      ▼
┌──────────┐
│ inactive │ ← Default state
│is_verified│   - Not public
│ = false   │   - Pending review
└─────┬─────┘
      │ (Admin verifies)
      ▼
┌──────────┐
│ inactive │
│is_verified│
│ = true    │
└─────┬─────┘
      │ (Admin activates)
      ▼
┌──────────┐     ┌─────────────┐
│  active  │────→│ Deal/Listing│
│is_verified│     │  Approved   │
│ = true    │     └──────┬──────┘
│           │            │
│ PUBLIC    │            │ (Creates snapshot)
│ VISIBLE   │            ▼
└─────┬─────┘     ┌─────────────┐
      │           │  frozen_at  │
      │           │   = NOW     │
      │           │             │
      │           │ IMMUTABLE   │
      │           └─────────────┘
      │ (Admin deactivates)
      ▼
┌──────────┐
│ inactive │
│is_verified│ ← Hidden from public
│ = true    │   Can reactivate
└──────────┘


COMPANY USER STATUS FLOW:
┌──────────┐
│Registration│
└─────┬─────┘
      │
      ▼
┌──────────┐
│ pending  │ ← Awaiting email verify + admin approval
│email_ver │
│ = null   │
└─────┬─────┘
      │ (User clicks email link)
      ▼
┌──────────┐
│ pending  │
│email_ver │
│ = NOW    │
└─────┬─────┘
      │ (Admin approves)
      ▼
┌──────────┐     ┌──────────┐
│  active  │────→│suspended │
│is_verified│     │          │
│ = true    │     └────┬─────┘
│           │          │
│ CAN LOGIN │          │ (Admin reactivates)
└─────┬─────┘          │
      │                ▼
      └────────────────┘
      
      ▼ (Admin rejects)
┌──────────┐
│ rejected │
│ TERMINAL │ ← Cannot be undone
└──────────┘
```

---

**END OF PHASE 0 AUDIT REPORT**

---

**Next Steps:**
1. Review this audit with team
2. Address mandatory fixes
3. Approve governance protocol design
4. Begin implementation on safe extension points
