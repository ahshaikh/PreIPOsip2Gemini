📊 COMPLETE COMPANIES MODULE FLOW

1. COMPANY REGISTRATION
Company visits: /company/register
   ↓
POST /api/company/register (AuthController)
   ↓
Creates:
   - Company record (status='inactive', is_verified=false)
   - CompanyUser record (status='pending', is_verified=false)
   ↓
Email verification sent

Files:

Controller: backend/app/Http/Controllers/Api/Company/AuthController.php
Models: Company.php, CompanyUser.php
Frontend: frontend/app/company/register/page.tsx

2. ADMIN APPROVAL
Admin reviews: GET /api/admin/company-users
   ↓
Admin approves: POST /api/admin/company-users/{id}/approve
   ↓
Updates:
   - CompanyUser: status='active', is_verified=true
   - Company: status='active', is_verified=true
   ↓
Approval email sent

Requirement: Email must be verified first (email_verified_at set)

Files:

Controller: backend/app/Http/Controllers/Api/Admin/CompanyUserController.php
Frontend: frontend/app/admin/company-users/page.tsx

3. COMPANY ONBOARDING
Company completes onboarding steps:
   1. Basic Profile (name, description, sector) - MANDATORY
   2. Branding (logo upload) - MANDATORY  
   3. Team Members
   4. Financial Reports
   5. Documents
   6. Verification (admin approval) - MANDATORY
   ↓
Profile completion >= 80% required for share listing

Files:

Controller: backend/app/Http/Controllers/Api/Company/OnboardingWizardController.php
Model: backend/app/Models/CompanyOnboardingProgress.php
Frontend: frontend/app/company/dashboard/page.tsx

4. SHARE LISTING SUBMISSION (Self-Service)
Company submits: POST /api/company/share-listings
   ↓
Requirements Check:
   - is_verified = true
   - profile_completed >= 80%
   ↓
Creates: CompanyShareListing (status='pending')
   ↓
Admin reviews: GET /api/admin/share-listings
   ↓
Admin approves: PUT /api/admin/share-listings/{id}
   ↓
Actions:
   - status = 'approved'
   - Creates BulkPurchase record (inventory)
   - Notifies company

Files:

Controller: backend/app/Http/Controllers/Api/Company/ShareListingController.php
Model: backend/app/Models/CompanyShareListing.php
Migration: 2025_12_27_110001_create_company_share_listings_table.php
Activity Tracking: company_share_listing_activities table

5. BULK PURCHASE (INVENTORY) CREATION
When share listing approved → Auto-creates:
   ↓
BulkPurchase record:
   - company_id
   - company_share_listing_id (provenance tracking)
   - product_id
   - face_value_purchased
   - actual_cost_paid
   - discount_percentage
   - extra_allocation_percentage
   - value_remaining (available for user allocation)

Files:

Model: backend/app/Models/BulkPurchase.php

6. DEAL CREATION
Admin or Company creates: POST /api/company/deals
   ↓
Deal record:
   - company_id
   - product_id
   - share_price
   - min_investment, max_investment
   - deal_opens_at, deal_closes_at
   - status='active' (live for investment)
   ↓
Deal Approval Workflow (if configured):
   draft → pending_review → under_review → approved → published

Files:

Model: backend/app/Models/Deal.php
Migration: 2026_01_08_000003_create_deal_approvals_table.php

7. PUBLIC DISCOVERY (INVESTORS)
User visits: /companies or /deals
   ↓
GET /api/investor/companies (NEW - Just Created!)
   ↓
Returns:
   - Companies with active deals
   - User's wallet balance
   - Company financials, documents, team

Files:

Controller: backend/app/Http/Controllers/Api/Investor/InvestorCompanyController.php
Frontend: frontend/app/(public)/companies/[slug]/page.tsx

8. INVESTMENT SUBMISSION
User clicks "Invest"
   ↓
POST /api/investor/companies/{id}/check-eligibility
   ↓
6-Layer Validation:
   1. ✓ User KYC complete
   2. ✓ Wallet balance >= amount
   3. ✓ Company has active deals
   4. ✓ Company not suspended
   5. ✓ All 4 risk acknowledgements provided
   6. ✓ Amount > 0
   ↓
POST /api/investor/investments
   ↓
DB Transaction:
   1. Capture InvestmentDisclosureSnapshot (immutable)
   2. Record RiskAcknowledgements (immutable)
   3. Debit Wallet (WalletService)
   4. Create CompanyInvestment record
   5. COMMIT
   ↓
Success: Investment created

Files:

Controller: backend/app/Http/Controllers/Api/Investor/InvestorInvestmentController.php
Models: CompanyInvestment.php, Wallet.php, InvestmentDisclosureSnapshot.php
Services: BuyEnablementGuardService, WalletService, InvestmentSnapshotService

🗂️ DATABASE SCHEMA SUMMARY
Table					Purpose				Key Relationships
companies				Company master data		→ company_users, deals, bulk_purchases
company_users				Company admin accounts		→ companies, users
company_onboarding_progress		Onboarding tracking		→ companies
company_share_listings			Self-service share submission	→ companies, bulk_purchases
company_share_listing_activities	Activity audit log		→ company_share_listings
bulk_purchases				Share inventory pool		→ companies, products, company_share_listings
deals					Investment opportunities	→ companies, products
deal_approvals				Deal approval workflow		→ deals, users
company_investments 		 	Direct company investments	→ users, companies, investment_disclosure_snapshots
investment_disclosure_snapshots		Immutable risk snapshots	→ company_investments, users, companies
risk_acknowledgements			Risk acceptance records		→ users, companies
wallets					User balances (paise precision)	→ users
user_investments			SIP-based allocations		→ users, bulk_purchases, subscriptions