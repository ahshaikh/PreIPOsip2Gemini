# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PreIPO SIP is a pre-IPO investment platform with three portals: investor-facing (buy pre-IPO shares), company/issuer portal (manage fundraising and disclosures), and admin panel (central control plane). Built on the principle of **Admin Supremacy** — all business logic is configurable via the admin panel without code deployment.

## Tech Stack

- **Backend**: Laravel 11 (PHP 8.2+), MySQL 8.0+, Redis, Laravel Sanctum auth, Spatie Permission RBAC
- **Frontend**: Next.js 16 (App Router), TypeScript, React 19, Tailwind CSS 3, shadcn/ui (Radix), TanStack React Query, Axios
- **Payments**: Razorpay (primary), PayU (supported)
- **Monitoring**: Sentry (both backend and frontend)

## Development Commands

### Backend (from `backend/`)
```bash
composer dev              # Runs Laravel server + queue + logs + Vite concurrently
composer test             # Clear config cache then run all tests
php artisan serve         # Laravel dev server (port 8000)
php artisan test          # Run PHPUnit tests
php artisan test --filter=TestName  # Run a single test class/method
php artisan test tests/Feature      # Run only feature tests
php artisan test tests/Unit         # Run only unit tests
php artisan pint          # Code formatting (Laravel Pint / PHP-CS-Fixer)
php artisan migrate       # Run database migrations
php artisan tinker        # Interactive REPL
php artisan queue:listen --tries=1  # Process queue jobs
```

### Frontend (from `frontend/`)
```bash
npm run dev          # Next.js dev server (port 3000)
npm run build        # Production build
npm run lint         # ESLint
npm run type-check   # TypeScript type checking (tsc --noEmit)
npm run analyze      # Bundle analysis (ANALYZE=true next build)
```

### Testing Environment
PHPUnit uses: `APP_ENV=testing`, `CACHE_DRIVER=array`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`. Tests run with `failOnWarning`, `failOnRisky`, random execution order, and strict global state checking.

## Architecture

### Backend Structure
```
backend/app/
├── Http/Controllers/Api/
│   ├── Admin/       # Admin panel endpoints (~40 controllers)
│   ├── User/        # Investor endpoints
│   ├── Company/     # Issuer portal endpoints
│   ├── Investor/    # Investor-specific views (company profiles, snapshots)
│   └── Public/      # Unauthenticated endpoints
├── Models/          # Eloquent models (200+ tables)
├── Services/        # Business logic (60+ services)
├── Jobs/            # Queue jobs (payment processing, email, notifications)
├── Helpers/         # SettingsHelper.php, BankHelper.php (autoloaded via composer)
└── Domains/         # Domain-driven modules
```

### Frontend Structure
```
frontend/app/
├── (public)/     # Marketing pages (about, plans, companies, FAQ, blog)
├── (user)/       # Investor dashboard routes
├── admin/        # Admin panel routes
└── company/      # Issuer portal routes

frontend/lib/
├── api.ts          # Central Axios instance (baseURL: NEXT_PUBLIC_API_URL)
├── companyApi.ts   # Company portal API client
├── auditApi.ts     # Read-only audit API client
└── auth.ts         # Auth utilities
```

### API Structure
All API routes are under `/api/v1/` defined in `backend/routes/api.php`. Auth via Sanctum bearer tokens (60-min expiry, 7-day refresh). Three auth guards: `web`, `company_api`, `sanctum`.

### Key Architectural Patterns

**Double-Entry Ledger** (`DoubleEntryLedgerService`): Single source of financial truth. Expense-based inventory accounting — inventory cost expensed at purchase, revenue recognized as margin on sale. Every entry must balance (debits = credits). Tables: `ledger_accounts`, `ledger_entries`, `ledger_lines`.

**Protocol 1 Governance** (`config/protocol1.php`): Regulator-grade rule enforcement framework. Modes: `monitor` (log only) → `lenient` (block critical) → `strict` (block critical + high). Controls platform supremacy, immutability, actor separation, attribution, and buy eligibility rules. Minimum 95% compliance score required.

**Investment Snapshots**: Immutable capture of what the investor saw at purchase time — company state, disclosures shown, acknowledgements, financial terms. Hash-protected against tampering. Used for regulatory defense.

**Company Disclosure System** (`CompanyDisclosureService`): Issuer-authored, platform-controlled. Unique key: `(company_id, disclosure_module_id)`. State flow: Draft → Submitted → Approved/Rejected. Versioned and immutable once approved. Supports clarification workflows.

**Payment Processing Pipeline**: Razorpay webhook → `PaymentWebhookService` → `ProcessSuccessfulPaymentJob` (queued) → bonus calculation → share allocation → referral processing → ledger entries → wallet update → notification. Orchestrated via `payment_sagas` table.

**Wallet System** (`WalletService`): Three wallet types (deposit, bonus, winnings). Immutable transaction ledger. TDS deduction integrated with bonus awards. Fund locks for pending transactions.

## Critical Business Rules

These are **frozen as of February 1, 2026** (the "Audit Line"):

- **Admin Supremacy**: Every feature must be toggleable from admin without code deployment. All admin actions audit-logged.
- **Backend is sole financial authority**: Frontend is a declarative terminal — no business logic in UI.
- **No money invention**: `WalletService` moves entitlements but must never create money. Double-entry ledger enforces balance.
- **Product ownership**: `company_id` is mandatory and non-nullable on products. Companies author products, admins approve.
- **One active deal per product**: A product may have only one active deal window at a time.
- **Disabled means unreachable**: Features disabled in admin must be blocked at UI, API, and background job layers.
- **Forward-only migrations**: Legacy schemas (pre-audit-line) are frozen. Migrations must be DDL-only, deterministic, and idempotent.
- **Seeders must be idempotent**: Use `updateOrCreate()`, never bypass state machines or domain invariants.

## System Invariants

- Every financial state transition must produce a balanced, immutable ledger entry
- Investment snapshots are immutable and hash-protected once created
- `(company_id, disclosure_module_id)` is a unique constraint on disclosures
- Axios `baseURL` must end with `/` to avoid double-slash URL issues (see `frontend/lib/api.ts`)
- Controllers using `$this->authorize()` must include the `AuthorizesRequests` trait
- `ON DELETE SET NULL` requires a nullable column in migrations
- Index names must be explicit to avoid MySQL length limit issues

## Anti-Patterns to Avoid

- **Admins as content authors**: Collapses governance/execution roles. Companies author disclosures, admins approve.
- **Frontend-driven authority**: Never let UI components determine workflow outcomes or own business meaning.
- **Retroactive schema fixes**: Never modify old committed migrations. Create new forward-only migrations.
- **Masking errors with guards**: Don't use `Schema::hasTable` or `try/catch` to hide migration failures.
- **Mixed-intent commits**: Never combine governance declarations with code/schema changes.
- **Parallel workflows**: Don't maintain separate admin and user-facing workflows for the same domain entity.

## Key Configuration

- `NEXT_PUBLIC_API_URL`: Frontend API base URL (default: `http://localhost:8000/api/v1/`)
- `SANCTUM_TOKEN_EXPIRATION`: Token validity in minutes (default: 60)
- `PROTOCOL1_ENFORCEMENT_MODE`: Governance mode (`monitor`/`lenient`/`strict`)
- Sanctum token prefix: `preipo_`
- CORS allows `localhost:3000` with credentials

## Documentation Hierarchy

Architecture > Governance > Project Context > README. The README (`README.md`) is a public-facing developer checklist and holds no architectural authority. `ARCHITECTURE_AND_GOVERNANCE_CONTEXT.md` contains system invariants and business rules. `Context/` directory (01.md–25.md) contains detailed project context documents.
