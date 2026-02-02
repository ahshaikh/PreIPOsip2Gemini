# Project Context: PreIPOsip Platform @ preiposip.com

## 1. About This Project
The PreIPOsip Platform is a fully configurable investment platform. It features complex logic for bonuses (progressive, milestone, consistency), referral tiers, and profit sharing—all of which are database-driven.

Fully configurable Pre-IPO SIP platform for investment system allowing users to invest in Pre-IPO companies via Systematic Investment Plans (SIP), bonuses (progressive/milestone/referral), lucky draws, profit sharing, KYC, payments/withdrawals. Admin panel manages 300+ functions across system config, users, reports, notifications. Database: 95+ tables (users, plans, subscriptions, payments, products, bonuses, wallets). Live at preiposip.com; uses Razorpay, MSG91, DigiLocker integrations.​

## 2. Technical Stack

### Backend
* **Framework:** Laravel 11 (PHP 8.3)
* **Database:** MySQL 8.0 (55+ Tables)
* **Cache/Queue:** Redis 6.0+
* **Authentication:** Laravel Sanctum (API Tokens)
* **Testing:** PHPUnit (Target: 80% Coverage)

### Frontend
* **Framework:** Next.js 18+ (App Router)
* **Styling:** Tailwind CSS 3.0+
* **State Management:** React Hooks / Context
* **HTTP Client:** Axios (Interceptors for JWT)

## 3. Key Directories & Structure

### Backend (`/backend`)
* `app/Http/Controllers/Api/` - API Controllers (Admin & User namespaces).
* `app/Models/` - Eloquent models (Must use SoftDeletes & Audit trails).
* `app/Services/` - Business logic isolation (e.g., `BonusCalculator.php`, `PaymentService.php`).
* `app/Jobs/` - Queued jobs for heavy processing (Emails, Calculations).
* `database/migrations/` - Database schema definitions.
* `routes/api.php` - API definitions (Versioned: `/api/v1/...`).

### Frontend (`/frontend`)
* `app/(public)/` - Publicly accessible pages (Landing, Login, Signup).
* `app/(user)/` - Authenticated User Dashboard & Features.
* `app/admin/` - Authenticated Admin Panel.
* `components/` - Reusable UI components (shadcn/ui based).
* `lib/` - Utility functions and API wrappers.

## 4. Coding Standards & Conventions

### General
* **Strict Typing:** Use strict types in PHP and TypeScript interfaces in Frontend.
* **Formatting:** Follow PSR-12 for PHP and Prettier/ESLint for JS/TS.
* **Commentary:** Detailed Commentary everywhere specially editing/modifying the codes.

### Backend Guidelines
1.  **Thin Controllers, Fat Services:** Controllers should only handle request parsing and response formatting. Logic goes into `Services`.
2.  **Route Model Binding:** Use Laravel's route model binding where possible.
3.  **JSON Responses:** Always use `ApiResponseTrait` or standard JSON structures `{ status, message, data, errors }`.
4.  **Security:** Validate ALL inputs via `FormRequest` classes. No `request()->all()` in controllers.

### Frontend Guidelines
1.  **Server vs Client Components:** Default to Server Components. Use `'use client'` only when interaction is needed.
2.  **API Handling:** Use the central `api` utility in `lib/api.ts` which handles token attachment and error interception.

## 5. Common Commands

### Backend Setup & Run
```bash
cd backend
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed  # Seeds critical settings & roles
php artisan serve           # Starts server at http://localhost:8000 
```





## 6. Architecture & Workflows

### Feature Mapping
Feature		Frontend Path			Backend Controller		DB Tables
Auth		/app/(public)/login		AuthController			users, personal_access_tokens
KYC		/app/(user)/kyc			User/KycController		user_kyc, kyc_documents
Invest		/app/(user)/subscription	User/SubscriptionController	subscriptions, plans, payments
Withdraw	/app/(user)/wallet		User/WalletController		withdrawals, wallets, transactions
Admin		/app/(admin)/*			Admin/*Controller		settings, audit


### High Level Architecture

PreIPOsip2Gemini/
│
├── backend/   (Laravel 10)
│   ├── app/
│   │   ├── Console
│   │   ├── Enums
│   │   ├── Exceptions
│   │   ├── Helpers
│   │   ├── Http
│   │   │   ├── Controllers
│   │   │   ├── Middleware
│   │   │   └── Requests
│   │   ├── Jobs
│   │   ├── Models
│   │   ├── Notifications
│   │   ├── Observers
│   │   ├── Providers
│   │   └── Services
│   │
│   ├── routes/ (api, web, console)
│   ├── database/ (migrations, factories, seeders)
│   ├── docs/ (API + Features documentation)
│   ├── tests/ (Feature, Unit, LoadTesting)
│   └── deploy/ (supervisor configs + deployment instructions)
│
└── frontend/  (Next.js 14)
    ├── app/ (App Router pages)
    ├── components/ (UI + modular components)
    ├── context/ (AuthContext)
    ├── lib/ (helpers, secureStorage, api client)
    ├── providers/ (ReactQueryProvider)
    ├── styles/
    ├── public/
    └── types/ (shared TS types)

### Critical Workflows
**Bonus Calculation:** Triggered via Webhook or Job AFTER successful payment. Does not happen synchronously during API request.
**Settings Retrieval:** Use helper setting('key', 'default'). NEVER use config() for business values.

## 7. Specific Warnings & Notes
1. **Concurrency:** Financial transactions (Wallet/Bonus) MUST use DB::transaction.
2. **Queues:** Sending emails and calculating complex bonuses MUST be queued (ShouldQueue).
3. **Logs:** All Admin actions must be logged to audit_trails.
4. **Soft Deletes:** Critical data (Users, Transactions, Plans) must never be permanently deleted.

## 8. Specific Warnings & Notes

1. **Concurrency:** Financial transactions (Wallet/Bonus) MUST use DB::transaction.
2. **Queues:** Sending emails and calculating complex bonuses MUST be queued (ShouldQueue).
3. **Logs:** All Admin actions must be logged to audit_trails.
4. **Soft Deletes:** Critical data (Users, Transactions, Plans) must never be permanently deleted.

## 9. Testing Standards
### Backend Testing

Directory: backend/tests/

Test types:
Feature/ → HTTP endpoint tests
Unit/ → isolated services, helpers
LoadTesting/ → artillery-like scripts

Conventions:
Use Laravel's built-in HTTP testing ($this->getJson(), $this->postJson())
Factories required for all DB entities
Seeders for test scenarios must be deterministic

## 10. Admin Panel Managed Functions (300+)

### These functions are categorized into 18 core modules as defined in the project's FSD. The core principle is "Zero Hardcoded Values", meaning every attribute below is database-driven and configurable via the Admin Panel.

1. **System Configuration & Architecture**
a. *Module Toggles:* Enable/Disable Registration, Login, Investment, Withdrawal, Referral, Lucky Draw, Profit Share, KYC, Support, Bonuses.
b. *Maintenance:* Toggle maintenance mode with custom user-facing messages.
c. *Automation:* Configure and schedule backups; Manage Cron jobs (enable/disable, manual execution).
d. *Security:* Configure IP whitelisting, CAPTCHA settings, and Rate limiting.

2. **Investment Plans Management**
a. *CRUD:* Create unlimited plans (A, B, C...) and edit attributes (Name, Amount, Duration).
b. *Bonus Logic:* Configure Progressive bonus rates, Milestone bonuses (unlimited), and Consistency streak multipliers.
c. *Rules:* Set Eligibility (Age, KYC, Country), Upgrade/Downgrade penalties, and Pause/Cancel rules.
d. *Visuals:* Customize plan comparison tables and feature lists.

3. **Pre-IPO Product Management**
a. *Product Data:* Add/Edit unlimited products with media galleries (Images/Videos).
b. *Financials:* Configure Pricing (Face value, Market price history), Revenue, and P&L data.
c. *Compliance:* Manage Risk disclosures, SEBI approvals, and Legal documents (Prospectus).
d. *Allocation:* Define auto/manual allocation rules, priorities, and investment limits.

4. **Bulk Purchase (Inventory) Management**
a. *Inventory:* Add bulk purchases (Cost, Discount %, Extra Allocation %), View real-time allocation status.
b. *Stock:* Monitor Inventory dashboards, Configure Low stock alerts, and View allocation history.

5. **Bonus Configuration Engine**
a. *Global Controls:* Master On/Off switches for all bonus types.
b. *Formulas:* JavaScript-based formula editor for complex bonus calculations.
c. *Management:* Manual bonus entry, Bulk processing (CSV), and Reversal of incorrectly credited bonuses.
d. *Events:* Manage Referral campaigns, Birthday/Anniversary bonuses, and Celebration events.

6. **Lucky Draw System**
a. *Setup:* Configure Draw frequency (Monthly/Quarterly) and Prize structures (Unlimited tiers).
b. *Execution:* Manual or Cron-based execution, Entry rule configuration (Plan-based, Streak-based).
c. *Transparency:* Upload draw videos, Generate winner certificates, and Publish results.

7. **Profit Sharing System**
a. *Distribution:* Configure Global settings, Calculate distribution previews, and Approve/Distribute funds.
b. *Logic:* Set Profit share percentages per plan and Eligibility criteria (Min months/investment).
c. *Reporting:* Publish financial reports with visibility controls.

8. **User Management**
a. *Operations:* View/Edit details (Profile, KYC), Suspend/Block users, and Adjust Wallet balances manually.
b. *Actions:* Force payment processing, Override investment allocations, and Manual bonus awards.
c. *Communication:* Send individual/bulk Emails, SMS, and Push notifications.

9. **KYC Management**
a. *Verification:* Manage KYC queue, Verify documents (Zoom/Rotate/OCR), and Approve/Reject with reasons.
b. *Config:* Define required Document types and Auto-verification settings (Aadhaar/PAN APIs).

10. **Payment & Withdrawal**
a. *Gateways:* Setup multiple gateways (Razorpay, PayU) and Configure methods (UPI, Card, Netbanking).
b. *Withdrawals:* View queue with SLA indicators, Approve/Reject requests, and Process via API or Manual entry.
c. *Settings:* Define Withdrawal fee tiers, Limits, and Auto-approval rules.

11. **Frontend CMS (Content Management)**
a. *Pages:* Editor for Homepage, About Us, How It Works, Plans, and Contact pages.
b. *Components:* Manage Header/Footer menus, FAQ categories, Blog posts, and Custom forms.
c. *Theming:* Configure Color schemes, Typography, and Logo/Branding assets.

12. **SEO & Meta Management**
a. *Optimization:* Global and Per-page SEO settings (Title, Description, OG Tags).
b. *Tools:* Sitemap manager, Robots.txt editor, and Redirects manager (301/302).

13. **Notification System**
a. *Templates:* Create/Edit Email, SMS, and Push notification templates with variables.
b. *Routing:* Configure providers (SendGrid, Twilio, FCM) and Sending rules.

14. **Reporting & Analytics**
a. *Financial:* Revenue reports, P&L statements, Cash flow, and Transaction logs.
b* *User:* Growth, Retention, Demographics, and KYC completion reports.
c. *Compliance:* SEBI compliance, TDS, and AML reports.

15. **System Settings**
a. *Core:* Site identity, Timezone, Pagination, and File upload limits.
b. *Security:* Password policies, 2FA settings, and Session timeouts.

16. **Support System*
a. *Ticketing:* Manage Support tickets, Categories, Priority levels, and Canned responses.
b. *Live Chat:* Chat agent management and Transcript history.
c. *Help Center:* Complete article and heading management system along with live comments update and visitor logging.

17. **Compliance & Legal**
a. *Documents:* Versioned editor for Terms, Privacy Policy, Refund Policy, and Risk Disclosures.
b. *Data:* GDPR export and "Right to be Forgotten" deletion tools.

18. **Advanced Admin Features**
a. *Dashboard:* Customizable widgets and System health monitoring.
b. *Logs:* Global activity logs, Admin audit trails, and Error tracking.

## 11. React 18+ Admin & User Panels
### The frontend is built using Next.js 18+ (App Router) with a modular architecture mapping directly to the functions above.

1. **Admin Panel (/admin)**
Located in frontend/app/admin, this secure portal manages the entire platform.
Dashboard: /admin/dashboard - Widgets for critical metrics.
User Management: /admin/users, /admin/kyc-queue, /admin/company-users.
Financials: /admin/payments, /admin/withdrawal-queue, /admin/profit-sharing.
Content (CMS): /admin/content/companies, /admin/content/deals, /admin/content/reports.

Settings:
System: /admin/settings/system, /admin/settings/system-health, /admin/settings/backups.
Business Logic: /admin/settings/plans, /admin/settings/bonuses, /admin/settings/product.
Security: /admin/settings/roles, /admin/settings/ip-whitelist, /admin/settings/captcha.
Support: /admin/support (Ticket management system).

2. **User Dashboard (/dashboard)**
Located in frontend/app/(user), this is the authenticated investor view.
Core: /dashboard (Overview), /portfolio (Investment holdings).
Investment: /subscribe (New Plans), /subscription (Active SIPs), /plan (Details).
Financial: /wallet (Deposit/Withdraw), /transactions (History), /profit-sharing.
Engagement: /bonuses, /referrals (Network & Earnings), /lucky-draws, /offers.
Account: /kyc (Document upload), /profile, /settings, /compliance.
Support: /support (Ticket creation), /notifications.

3. **Company Portal (/company)**
Located in frontend/app/company, designed for companies listing on the platform.
Management: /company/dashboard, /company/profile, /company/team.
Deal Flow: /company/deals, /company/funding, /company/investor-interests.
Data: /company/analytics, /company/financial-reports.
Communication: /company/updates, /company/webinars, /company/qna.

## 12. Routes Map

### This document maps the application's functional modules to concrete React routes and components found in the `frontend` codebase. Use this as a navigation guide for development and debugging.

## 1. Admin Panel
**Base Path:** `/admin`
**Layout:** `frontend/app/admin/layout.tsx`

| Function / Module       | Route URL 			    | Codebase File 		 		  | Description 					    |
| :---------------------- | :------------------------------ | :------------------------------------------ | :------------------------------------------------------ |
| **Dashboard** 	  | `/admin/dashboard` 		    | `app/admin/dashboard/page.tsx` 		  | Main admin overview and widgets. 			    |
| **User Management** 	  | `/admin/users` 		    | `app/admin/users/page.tsx` 		  | List, search, filter all users. 			    |
| **User Details** 	  | `/admin/users/[userId]` 	    | `app/admin/users/[userId]/page.tsx` 	  | Detailed view of a specific user (Profile, KYC, Wallet).|
| **KYC Queue** 	  | `/admin/kyc-queue` 		    | `app/admin/kyc-queue/page.tsx` 		  | Pending KYC verification requests. 			    |
| **Withdrawal Queue** 	  | `/admin/withdrawal-queue` 	    | `app/admin/withdrawal-queue/page.tsx` 	  | Pending withdrawal requests processing. 		    |
| **Payment History** 	  | `/admin/payments` 		    | `app/admin/payments/page.tsx` 		  | All transaction and payment logs. 			    |
| **Lucky Draws** 	  | `/admin/lucky-draws` 	    | `app/admin/lucky-draws/page.tsx` 		  | Manage and execute lucky draws. 			    |
| **Profit Sharing** 	  | `/admin/profit-sharing` 	    | `app/admin/profit-sharing/page.tsx` 	  | Calculate and distribute profit shares. 		    |
| **Support Tickets** 	  | `/admin/support`		    | `app/admin/support/page.tsx` 		  | Helpdesk ticket management. 			    |
| **Ticket Details** 	  | `/admin/support/[ticketId]`     | `app/admin/support/[ticketId]/page.tsx`	  | Chat interface for specific support ticket. 	    |
| **Chat Transcripts**    | `/admin/support/chat-transcript`| `app/admin/support/chat-transcript/page.tsx`| View live chat history. 				    |
| **Reports & Analytics** | `/admin/reports` 		    | `app/admin/reports/page.tsx` 	          | System-wide financial and user reports. 		    |
| **Push Notifications**  | `/admin/notifications/push`     | `app/admin/notifications/push/page.tsx`	  | Send manual push notifications. 			    |
| **Audit Logs**   	  | `/admin/system/audit-logs`      | `app/admin/system/audit-logs/page.tsx` 	  | View admin activity trails. 			    |
| **Company Users** 	  | `/admin/company-users`          | `app/admin/company-users/page.tsx`     	  | Manage company representative accounts. 		    |

## 2. Content Management (CMS)
| Function 		| Route URL		 			| Codebase File 						|
| :-------------------- | :-------------------------------------------- | :------------------------------------------------------------ |
| **Companies List** 	| `/admin/content/companies` 			| `app/admin/content/companies/page.tsx` 			|
| **Deals Management** 	| `/admin/content/deals` 			| `app/admin/content/deals/page.tsx` 				|
| **Sectors** 		| `/admin/content/sectors` 			| `app/admin/content/sectors/page.tsx` 				|
| **Tutorials** 	| `/admin/content/tutorials`		 	| `app/admin/content/tutorials/page.tsx` 			|
| **Market Reports** 	| `/admin/content/reports` 			| `app/admin/content/reports/page.tsx` 				|
| **Help Center** 	| `/admin/help-center` 				| `app/admin/help-center/page.tsx` 				|
| **Legal Agreements** 	| `/admin/compliance-manager/legal-agreements` 	| `app/admin/compliance-manager/legal-agreements/page.tsx` 	|

## 3. Admin Settings
**Base Path:** `/admin/settings/...`

| Setting Type 			| Route URL 				| Codebase File					  |
| :---------------------------- | :------------------------------------ | :---------------------------------------------- |
| **System Settings** 		| `/admin/settings/system` 		| `app/admin/settings/system/page.tsx` 		  |
| **System Health** 		| `/admin/settings/system-health` 	| `app/admin/settings/system-health/page.tsx` 	  |
| **Roles & Permissions** 	| `/admin/settings/roles` 		| `app/admin/settings/roles/page.tsx` 		  |
| **Investment Plans** 		| `/admin/settings/plans` 		| `app/admin/settings/plans/page.tsx` 		  |
| **Product Config** 		| `/admin/settings/product` 		| `app/admin/settings/product/page.tsx` 	  |
| **Bonuses** 			| `/admin/settings/bonuses` 		| `app/admin/settings/bonuses/page.tsx` 	  |
| **Referral Campaigns** 	| `/admin/settings/referral-campaigns` 	| `app/admin/settings/referral-campaigns/page.tsx`|
| **Payment Gateways** 		| `/admin/settings/payment-gateways` 	| `app/admin/settings/payment-gateways/page.tsx`  |
| **Notification Config** 	| `/admin/settings/notifications` 	| `app/admin/settings/notifications/page.tsx` 	  |
| **Email Templates** 		| `/admin/settings/email-templates` 	| `app/admin/settings/email-templates/page.tsx`   |
| **Menus** 			| `/admin/settings/menus` 		| `app/admin/settings/menus/page.tsx` 		  |
| **Pages & CMS** 		| `/admin/settings/cms` 		| `app/admin/settings/cms/page.tsx` 		  |
| **Blog Settings** 		| `/admin/settings/blog` 		| `app/admin/settings/blog/page.tsx` 		  |
| **Banners** 			| `/admin/settings/banners` 		| `app/admin/settings/banners/page.tsx` 	  |
| **FAQ Management** 		| `/admin/settings/faq` 		| `app/admin/settings/faq/page.tsx` 		  |
| **Compliance** 		| `/admin/settings/compliance` 		| `app/admin/settings/compliance/page.tsx` 	  |
| **Security (IPs)** 		| `/admin/settings/ip-whitelist` 	| `app/admin/settings/ip-whitelist/page.tsx` 	  |
| **CAPTCHA** 			| `/admin/settings/captcha` 		| `app/admin/settings/captcha/page.tsx` 	  |
| **Backups** 			| `/admin/settings/backups` 		| `app/admin/settings/backups/page.tsx` 	  |
| **SEO & Theme** 		| `/admin/settings/theme-seo` 		| `app/admin/settings/theme-seo/page.tsx` 	  |

## 4. User Dashboard
**Base Path:** `/` (Authenticated)
**Layout:** `frontend/app/(user)/layout.tsx`

| Function / Module 	  | Route URL 		| Codebase File 			| Description 			    |
| :---------------------- | :------------------ | :------------------------------------ | :-------------------------------- |
| **Dashboard**     	  | `/dashboard` 	| `app/(user)/dashboard/page.tsx` 	| User overview, portfolio summary. |
| **Portfolio**           | `/portfolio` 	| `app/(user)/portfolio/page.tsx` 	| Holdings, allocation analysis.    |
| **Active Subscriptions**| `/subscription` 	| `app/(user)/subscription/page.tsx` 	| Manage current SIPs. 		    |
| **New Subscription** 	  | `/subscribe` 	| `app/(user)/subscribe/page.tsx` 	| Flow to purchase a new plan. 	    |
| **Plan Details** 	  | `/plan` 		| `app/(user)/plan/page.tsx` 		| View plan specifics. 		    |
| **Wallet** 		  | `/wallet` 		| `app/(user)/wallet/page.tsx` 		| Balance, Deposit, Withdraw. 	    |
| **Transactions** 	  | `/transactions` 	| `app/(user)/transactions/page.tsx` 	| User payment history. 	    |
| **KYC** 		  | `/kyc` 		| `app/(user)/kyc/page.tsx` 		| Submit/View KYC documents. 	    |
| **Profile** 		  | `/profile` 		| `app/(user)/Profile/page.tsx` 	| Personal details editing. 	    |
| **Settings** 		  | `/settings` 	| `app/(user)/settings/page.tsx` 	| User preferences (Password, 2FA). |
| **Referrals** 	  | `/referrals` 	| `app/(user)/referrals/page.tsx` 	| Referral link, stats, network.    |
| **Bonuses** 		  | `/bonuses` 		| `app/(user)/bonuses/page.tsx` 	| Bonus history and rules. 	    |
| **Lucky Draws** 	  | `/lucky-draws` 	| `app/(user)/lucky-draws/page.tsx` 	| Entries and results. 		    |
| **Profit Sharing** 	  | `/profit-sharing` 	| `app/(user)/profit-sharing/page.tsx` 	| View distributed profits. 	    |
| **Support** 		  | `/support` 		| `app/(user)/support/page.tsx` 	| Create/View support tickets. 	    |
| **Notifications** 	  | `/notifications` 	| `app/(user)/notifications/page.tsx` 	| In-app alerts history. 	    |
| **Reports** 		  | `/reports` 		| `app/(user)/reports/page.tsx` 	| Download statements/reports. 	    |
| **Promote** 		  | `/promote` 		| `app/(user)/promote/page.tsx` 	| Affiliate promotion tools. 	    |
| **Marketing Materials** | `/materials` 	| `app/(user)/materials/page.tsx` 	| Downloadable assets. 		    |
| **Offers** 		  | `/offers` 		| `app/(user)/offers/page.tsx` 		| Special personalized offers. 	    |
| **Compliance** 	  | `/compliance` 	| `app/(user)/compliance/page.tsx` 	| Legal acceptances overview. 	    |
| **Learning Center** 	  | `/learn` 		| `app/(user)/learn/page.tsx` 		| User education resources. 	    |

## 5. Company Portal
**Base Path:** `/company`
**Layout:** `frontend/app/company/layout.tsx`

| Function 	| Route URL			| Codebase File 				|
| :------------ | :---------------------------- | :-------------------------------------------- |
| **Dashboard** | `/company/dashboard` 		| `app/company/dashboard/page.tsx` 		|
| **Login** 	| `/company/login` 		| `app/company/login/page.tsx` 			|
| **Register** 	| `/company/register` 		| `app/company/register/page.tsx` 		|
| **Profile** 	| `/company/profile` 		| `app/company/profile/page.tsx` 		|
| **Analytics** | `/company/analytics` 		| `app/company/analytics/page.tsx` 		|
| **Deals** 	| `/company/deals` 		| `app/company/deals/page.tsx` 			|
| **Documents** | `/company/documents` 		| `app/company/documents/page.tsx` 		|
| **Funding** 	| `/company/funding` 		| `app/company/funding/page.tsx` 		|
| **Investors** | `/company/investor-interests` | `app/company/investor-interests/page.tsx` 	|
| **Q&A** 	| `/company/qna` 		| `app/company/qna/page.tsx` 			|
| **Team** 	| `/company/team` 		| `app/company/team/page.tsx` 			|
| **Updates** 	| `/company/updates` 		| `app/company/updates/page.tsx` 		|
| **Webinars** 	| `/company/webinars` 		| `app/company/webinars/page.tsx` 		|

## 6. Public Pages
**Base Path:** `/`
**Layout:** `frontend/app/layout.tsx`

| Page 				| Route URL 		| Codebase File 				|
| :---------------------------- | :-------------------- | :-------------------------------------------- |
| **Landing Page** 		| `/` 			| `app/(public)/page.tsx` 			|
| **Login** 			| `/login` 		| `app/(public)/login/page.tsx` 		|
| **Signup** 			| `/signup` 		| `app/(public)/signup/page.tsx` 		|
| **About Us** 			| `/about` 		| `app/(public)/about/page.tsx` 		|
| **Contact** 			| `/contact` 		| `app/(public)/contact/page.tsx` 		|
| **Products List** 		| `/products` 		| `app/(public)/products/page.tsx` 		|
| **Product Detail** 		| `/products/[slug]` 	| `app/(public)/products/[slug]/page.tsx` 	|
| **Companies** 		| `/companies` 		| `app/(public)/companies/page.tsx` 		|
| **Company Detail** 		| `/companies/[slug]` 	| `app/(public)/companies/[slug]/page.tsx` 	|
| **Plans** 			| `/plans` 		| `app/(public)/plans/page.tsx` 		|	
| **Blog** 			| `/blog` 		| `app/(public)/blog/page.tsx` 			|
| **FAQ** 			| `/faq` 		| `app/(public)/faq/page.tsx` 			|
| **Help Center** 		| `/help-center` 	| `app/(public)/help-center/page.tsx` 		|
| **Verify Certificate** 	| `/verify` 		| `app/(public)/verify/page.tsx` 		|

## 7. Legal & Compliance
* `/privacy-policy` 	-> `app/(public)/privacy-policy/page.tsx`
* `/terms` 		-> `app/(public)/terms/page.tsx`
* `/refund-policy` 	-> `app/(public)/refund-policy/page.tsx`
* `/risk-disclosure` 	-> `app/(public)/risk-disclosure/page.tsx`
* `/cookie-policy` 	-> `app/(public)/cookie-policy/page.tsx`
* `/sebi-regulations` 	-> `app/(public)/sebi-regulations/page.tsx`
* `/investor-charter` 	-> `app/(public)/investor-charter/page.tsx`
* `/grievance-redressal`-> `app/(public)/grievance-redressal/page.tsx`

## 13. Project Rules for AI Coding Agents

### You must follow these rules when generating code:
1. Never modify architecture without explicitly being asked.
2. Follow existing conventions (folders, naming, middleware, services).
3. All business logic → Service classes (Laravel) or lib functions (Next.js)
4. Controllers/Routes must remain thin.
5. Must write tests for new backend features.
6. Never introduce new dependencies without approval.
7. Ensure types, return shapes, and DTOs remain consistent.
8. All API calls must use lib/api.ts with standardized error handling.

## 14. Special Warnings

### You should never ignore at any cost:
1. Sanctum token handling must not be altered lightly.
2. Queue workers must remain idempotent — no side effects on retry.
3. Observers can easily cause circular logic; modify with caution.
4. Promotions, offers, and onboarding logic are compliance-sensitive.

Copy this as AIinit.md to repo root. Prefix all AI prompts with: "Use this AIinit.md context: [paste file]". Update on major changes.​
