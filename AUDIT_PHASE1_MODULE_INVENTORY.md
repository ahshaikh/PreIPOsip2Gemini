# PreIPOsip Platform - Comprehensive Module Audit
## Phase 1: Repository Module Inventory

**Audit Date:** 2025-12-12
**Auditor:** Claude Code Agent
**Repository:** PreIPOsip2Gemini (https://github.com/ahshaikh/PreIPOsip2Gemini)

---

## Executive Summary

This is a comprehensive audit of the PreIPOsip platform, a fully configurable Pre-IPO SIP investment platform. The repository consists of:

- **Backend:** Laravel 11 (PHP 8.3)
- **Frontend:** Next.js 18+ (App Router)
- **Database:** MySQL 8.0 (95+ tables documented, 92+ migrations found)
- **Total Files Analyzed:** 500+ files across backend and frontend

### Key Statistics

| Component 			| Count |
|-------------------------------|-------|
| Backend Controllers 		| 122+ 	|
| Backend Models 		| 119+ 	|
| Backend Services 		| 32+ 	|
| Backend Jobs 			| 13+ 	|
| Backend Middleware 		| 14+ 	|
| Database Migrations 		| 92+ 	|
| Frontend Admin Routes 	| 40+ 	|
| Frontend User Routes 		| 20+ 	|
| Frontend Company Routes 	| 12+ 	|
| Frontend Public Routes 	| 30+ 	|

---

## Module Categorization

Based on the project's Functional Specification Document (FSD), the system is divided into **20 core modules** with **300+ admin-configurable functions**. Below is the mapping of actual code to documented modules:

---

## BACKEND MODULES

### 1. **AUTHENTICATION & AUTHORIZATION MODULE**

**Purpose:** Manages user authentication, authorization, role-based access control (RBAC), and API token management.

**Components Found:**

#### Controllers (6)
- `Api/AuthController.php` - User authentication (login, register, logout)
- `Api/PasswordResetController.php` - Password reset flows
- `Api/TwoFactorAuthController.php` - 2FA implementation
- `Api/SocialLoginController.php` - OAuth social login
- `Api/Company/AuthController.php` - Company user authentication
- `Api/User/TwoFactorAuthController.php` - User-specific 2FA

#### Models (7)
- `User.php` - Core user model
- `UserProfile.php` - Extended user profiles
- `Role.php` - User roles
- `Permission.php` - Permission definitions
- `PersonalAccessToken.php` - API tokens (Sanctum)
- `PasswordResetToken.php` - Password reset tokens
- `Session.php` - Session management

#### Services (2)
- `OtpService.php` - OTP generation and verification
- `VerificationService.php` - Identity verification

#### Middleware (6)
- `Authenticate.php` - Authentication checks
- `AdminIpRestriction.php` - Admin IP whitelisting
- `CheckPermission.php` - Permission-based access control
- `EnsureKycCompleted.php` - KYC verification gate
- `EnsureLegalAcceptance.php` - Legal compliance gate
- `ConcurrentSessionControl.php` - Session management

#### Jobs (1)
- `SendOtpJob.php` - Async OTP sending

---

### 2. **USER MANAGEMENT MODULE**

**Purpose:** Complete user lifecycle management, profile editing, KYC handling, suspension, blocking, wallet adjustments.

#### Controllers (8)
- `Api/Admin/AdminUserController.php` - Admin user management
- `Api/User/ProfileController.php` - User profile management
- `Api/User/SecurityController.php` - Security settings
- `Api/User/UserSettingsController.php` - User preferences
- `Api/User/PrivacyController.php` - Privacy & GDPR
- `Api/User/ActivityController.php` - User activity logs
- `Admin/AdminUserController.php` - Legacy admin controller
- `Api/Admin/BulkOperationsController.php` - Bulk user operations

#### Models (5)
- `User.php` - Core user
- `UserProfile.php` - Profile details
- `UserSettings.php` - User preferences
- `UserNotificationPreference.php` - Notification settings
- `UserLegalAcceptance.php` - Legal consents

#### Services (0)
- **ISSUE:** No dedicated `UserService.php` found - logic likely scattered in controllers

---

### 3. **KYC MANAGEMENT MODULE**

**Purpose:** Document verification, KYC queue management, approval/rejection workflows, compliance tracking.

#### Controllers (2)
- `Api/Admin/KycQueueController.php` - Admin KYC queue management
- `Api/User/KycController.php` - User KYC submission

#### Models (4)
- `UserKyc.php` - KYC metadata
- `KycDocument.php` - Document storage
- `KycRejectionTemplate.php` - Rejection reasons
- `ComplianceRecord.php` - Compliance tracking

#### Services (1)
- `VerificationService.php` - Verification logic (shared with Auth)

#### Jobs (1)
- `ProcessKycJob.php` - Async KYC processing

---

### 4. **INVESTMENT PLANS MODULE**

**Purpose:** Plan creation, bonus configuration (progressive, milestone, consistency), profit sharing setup, plan features.

#### Controllers (2)
- `Api/Admin/PlanController.php` - Admin plan CRUD
- `Api/Public/PlanController.php` - Public plan viewing

#### Models (4)
- `Plan.php` - Investment plans
- `PlanFeature.php` - Plan features list
- `PlanConfig.php` - Dynamic plan configuration
- `PlanEligibilityRule.php` - Eligibility criteria

#### Services (1)
- `PlanEligibilityService.php` - Eligibility checking logic

---

### 5. **SUBSCRIPTION MANAGEMENT MODULE**

**Purpose:** User subscriptions to plans, SIP management, payment scheduling, auto-debit.

#### Controllers (2)
- `Api/User/SubscriptionController.php` - User subscription actions
- `Api/Admin/PaymentController.php` - Admin payment oversight

#### Models (2)
- `Subscription.php` - Active subscriptions
- `SubscriptionHistory.php` - Subscription changes log

#### Services (2)
- `SubscriptionService.php` - Subscription logic
- `AutoDebitService.php` - Auto-debit scheduling

#### Jobs (1)
- `RetryAutoDebitJob.php` - Retry failed auto-debits

---

### 6. **PAYMENT & WITHDRAWAL MODULE**

**Purpose:** Payment gateway integration, transaction processing, webhook handling, withdrawal queue, approval workflows.

#### Controllers (5)
- `Api/User/PaymentController.php` - User payment initiation
- `Api/Admin/PaymentController.php` - Admin payment management
- `Api/User/WithdrawalController.php` - User withdrawal requests
- `Api/Admin/WithdrawalController.php` - Admin withdrawal queue
- `Api/WebhookController.php` - Payment gateway webhooks

#### Models (4)
- `Payment.php` - Payment records
- `Withdrawal.php` - Withdrawal requests
- `Transaction.php` - Transaction ledger
- `PaymentGatewayConfig.php` - Gateway settings

#### Services (6)
- `RazorpayService.php` - Razorpay integration
- `PaymentWebhookService.php` - Webhook processing
- `WithdrawalService.php` - Withdrawal logic
- `ResilientRazorpayService.php` - Resilient Razorpay calls (Circuit Breaker)
- `WalletService.php` - Wallet operations
- `CircuitBreakerService.php` - Circuit breaker pattern

#### Jobs (4)
- `ProcessSuccessfulPaymentJob.php` - Post-payment processing
- `SendPaymentConfirmationEmailJob.php` - Confirmation emails
- `SendPaymentFailedEmailJob.php` - Failure notifications
- `SendPaymentReminderJob.php` - Payment reminders
- `ProcessWebhookRetryJob.php` - Webhook retry logic

---

### 7. **WALLET MANAGEMENT MODULE**

**Purpose:** User wallet balances (deposit, bonus, winnings), transaction history, manual adjustments.

#### Controllers (2)
- `Api/User/WalletController.php` - User wallet view and actions
- (Admin wallet adjustments handled via `AdminUserController`)

#### Models (2)
- `Wallet.php` - Wallet balances
- `Transaction.php` - Transaction ledger

#### Services (1)
- `WalletService.php` - Wallet operations (credit, debit, transfer)

---

### 8. **BONUS CALCULATION ENGINE**

**Purpose:** Progressive, milestone, consistency, referral, celebration bonuses - all configurable.

#### Controllers (2)
- `Api/Admin/AdminBonusController.php` - Admin bonus management
- `Api/User/BonusController.php` - User bonus viewing

#### Models (4)
- `BonusTransaction.php` - Bonus ledger
- `BonusConfig.php` - Bonus rules
- `CelebrationEvent.php` - Celebration events
- `MilestoneBonus.php` - Milestone definitions

#### Services (2)
- `BonusCalculatorService.php` - Core bonus calculation logic
- `CelebrationBonusService.php` - Celebration bonus handling

---

### 9. **REFERRAL SYSTEM MODULE**

**Purpose:** Referral tracking, multi-tier commissions, referral campaigns, promotional materials.

#### Controllers (3)
- `Api/Admin/ReferralController.php` - Admin referral management
- `Api/User/ReferralController.php` - User referral dashboard
- `Api/User/PromotionalMaterialController.php` - Promo material downloads

#### Models (5)
- `Referral.php` - Referral records
- `ReferralCampaign.php` - Campaign definitions
- `ReferralTier.php` - Commission tiers
- `PromotionalMaterial.php` - Downloadable assets
- `PromotionalMaterialDownload.php` - Download tracking

#### Services (1)
- `ReferralService.php` - Referral logic

#### Jobs (1)
- `ProcessReferralJob.php` - Async referral processing

---

### 10. **LUCKY DRAW MODULE**

**Purpose:** Lucky draw execution, prize configuration, winner selection, result publishing.

#### Controllers (2)
- `Api/Admin/LuckyDrawController.php` - Admin draw management
- `Api/User/LuckyDrawController.php` - User draw participation

#### Models (4)
- `LuckyDraw.php` - Draw events
- `LuckyDrawEntry.php` - User entries
- `LuckyDrawWinner.php` - Winner records
- `Prize.php` - Prize definitions

#### Services (1)
- `LuckyDrawService.php` - Draw execution logic

#### Jobs (1)
- `GenerateLuckyDrawEntryJob.php` - Auto-generate entries

---

### 11. **PROFIT SHARING MODULE**

**Purpose:** Profit distribution, eligibility calculation, financial reporting, approval workflows.

#### Controllers (2)
- `Api/Admin/ProfitShareController.php` - Admin profit sharing
- `Api/User/ProfitShareController.php` - User profit share view

#### Models (3)
- `ProfitShare.php` - Profit share periods
- `UserProfitShare.php` - User allocations
- `ProfitShareConfig.php` - Configuration

#### Services (2)
- `ProfitShareService.php` - Distribution logic
- `ProfitSharingService.php` - **DUPLICATE SERVICE - NEEDS CONSOLIDATION**

---

### 12. **PRE-IPO PRODUCTS & INVENTORY MODULE**

**Purpose:** Product catalog, pricing, allocation, bulk purchases, inventory tracking.

#### Controllers (3)
- `Api/Admin/ProductController.php` - Admin product management
- `Api/Admin/BulkPurchaseController.php` - Bulk purchase management
- `Api/Public/ProductDataController.php` - Public product data

#### Models (14)
- `Product.php` - Product catalog
- `ProductHighlight.php` - Key selling points
- `ProductFounder.php` - Founder profiles
- `ProductFundingRound.php` - Funding history
- `ProductKeyMetric.php` - Financial metrics
- `ProductRiskDisclosure.php` - Risk disclosures
- `ProductPriceHistory.php` - Price history
- `ProductMedia.php` - Media gallery
- `ProductDocument.php` - Documents
- `BulkPurchase.php` - Bulk purchases
- `UserInvestment.php` - User holdings
- `AllocationHistory.php` - Allocation logs
- `InventoryAlert.php` - Low stock alerts
- `StockAllocation.php` - Stock allocation tracking

#### Services (2)
- `AllocationService.php` - Allocation logic
- `InventoryService.php` - Inventory tracking

---

### 13. **COMPANY PORTAL (B2B) MODULE**

**Purpose:** Company registration, profile management, deal rooms, investor engagement, analytics.

#### Controllers (12)
- `Api/Company/AuthController.php` - Company authentication
- `Api/Company/CompanyProfileController.php` - Profile management
- `Api/Company/CompanyDealController.php` - Deal management
- `Api/Company/CompanyAnalyticsController.php` - Analytics
- `Api/Company/DocumentController.php` - Document repository
- `Api/Company/FinancialReportController.php` - Financial reports
- `Api/Company/FundingRoundController.php` - Funding rounds
- `Api/Company/InvestorInterestController.php` - Investor tracking
- `Api/Company/OnboardingWizardController.php` - Onboarding flow
- `Api/Company/TeamMemberController.php` - Team management
- `Api/Company/CompanyUpdateController.php` - Company updates
- `Api/Company/CompanyWebinarController.php` - Webinar management
- `Api/Company/CompanyQnaController.php` - Q&A management

#### Models (14)
- `Company.php` - Company profiles
- `CompanyUser.php` - Company staff accounts
- `CompanyOnboardingProgress.php` - Onboarding status
- `CompanyFinancialReport.php` - Financial reports
- `CompanyDocument.php` - Documents
- `CompanyTeamMember.php` - Team members
- `CompanyFundingRound.php` - Funding rounds
- `CompanyUpdate.php` - News/updates
- `Deal.php` - Investment deals
- `CompanyAnalytics.php` - Analytics
- `InvestorInterest.php` - Interest tracking
- `CompanyQna.php` - Q&A
- `CompanyWebinar.php` - Webinars
- `WebinarRegistration.php` - Registrations

---

### 14. **SUPPORT & HELPDESK MODULE**

**Purpose:** Ticketing system, live chat, canned responses, AI-powered support, help center.

#### Controllers (8)
- `Api/Admin/SupportTicketController.php` - Admin ticket management
- `Api/User/SupportTicketController.php` - User ticket creation
- `Api/Admin/LiveChatController.php` - Admin live chat
- `Api/User/LiveChatController.php` - User live chat
- `Api/Admin/CannedResponseController.php` - Canned response management
- `Api/SupportAIController.php` - AI support integration
- `Api/Admin/HelpCenterDashboardController.php` - Help center analytics
- `Api/HelpCenterController.php` - Public help center

#### Models (10)
- `SupportTicket.php` - Support tickets
- `SupportMessage.php` - Ticket messages
- `CannedResponse.php` - Predefined responses
- `ChatSession.php` - Live chat sessions
- `ChatMessage.php` - Chat messages
- `ChatAgentStatus.php` - Agent availability
- `TicketCategory.php` - Ticket categories
- `TicketPriority.php` - Priority levels
- `SupportMetrics.php` - Support KPIs
- `AgentPerformance.php` - Agent metrics

#### Services (2)
- `SupportService.php` - Core support logic
- `SupportAIService.php` - AI-powered support

---

### 15. **KNOWLEDGE BASE & HELP CENTER MODULE**

**Purpose:** Self-service knowledge base, articles, categories, search, analytics, feedback.

#### Controllers (5)
- `Api/Admin/KbCategoryController.php` - Category management
- `Api/Admin/KbArticleController.php` - Article management
- `Api/User/KnowledgeBaseController.php` - User KB access
- `Api/Public/HelpCenterController.php` - Public help center
- `Api/ArticleController.php` - Article viewing
- `Api/CategoryController.php` - Category listing

#### Models (5)
- `KbCategory.php` - KB categories
- `KbArticle.php` - KB articles
- `KbArticleView.php` - View analytics
- `ArticleFeedback.php` - User feedback
- `KbSearchLog.php` - Search analytics

---

### 16. **NOTIFICATION SYSTEM MODULE**

**Purpose:** Multi-channel notifications (email, SMS, push, in-app), templates, preferences, logging.

#### Controllers (6)
- `Api/Admin/NotificationController.php` - Admin notification management
- `Api/User/NotificationController.php` - User notifications
- `Api/Admin/EmailTemplateController.php` - Email template management
- `Api/Admin/SmsTemplateController.php` - SMS template management
- `Api/Admin/PushNotificationConfigController.php` - Push config
- `Api/Admin/NotificationTestingController.php` - Testing interface
- `Api/Admin/NotificationLogController.php` - Notification logs

#### Models (12)
- `Notification.php` - In-app notifications
- `UserNotificationPreference.php` - User preferences
- `EmailTemplate.php` - Email templates
- `EmailLog.php` - Email sending logs
- `SmsTemplate.php` - SMS templates
- `SmsLog.php` - SMS sending logs
- `PushNotificationConfig.php` - Push configuration
- `PushNotificationLog.php` - Push logs
- `NotificationChannel.php` - Channel configuration
- `NotificationRule.php` - Routing rules
- `CommunicationChannel.php` - Communication channels
- `ChannelMessageTemplate.php` - Channel templates

#### Services (6)
- `NotificationService.php` - Core notification routing
- `EmailService.php` - Email sending
- `SmsService.php` - SMS sending
- `ResilientEmailService.php` - Resilient email (Circuit Breaker)
- `ResilientSmsService.php` - Resilient SMS (Circuit Breaker)
- `CircuitBreakerService.php` - Circuit breaker pattern

#### Jobs (3)
- `ProcessEmailJob.php` - Async email sending
- `ProcessNotificationJob.php` - Async notification processing
- (SMS jobs likely missing)

---

### 17. **CMS & CONTENT MANAGEMENT MODULE**

**Purpose:** Page builder, blog, FAQs, tutorials, banners, menus, SEO, redirects.

#### Controllers (17)
- `Api/Admin/CmsController.php` - CMS management
- `Api/Admin/PageController.php` - Page management
- `Api/Admin/PageBlockController.php` - Page block builder
- `Api/Admin/BlogPostController.php` - Blog posts
- `Api/Admin/BlogCategoryController.php` - Blog categories
- `Api/Admin/FaqController.php` - FAQ management
- `Api/Admin/TutorialController.php` - Tutorial management
- `Api/Admin/ContentReportController.php` - Content reports
- `Api/Admin/CompanyController.php` - Company content
- `Api/Admin/DealController.php` - Deal content
- `Api/Public/PageController.php` - Public page viewing
- `Api/Public/CompanyProfileController.php` - Public company profiles
- `Api/Admin/SeoConfigController.php` - SEO configuration
- `Api/Admin/RedirectController.php` - Redirect management
- `Api/Admin/SitemapController.php` - Sitemap generation
- `Api/Admin/RobotsTxtController.php` - Robots.txt editor
- `Api/Admin/ThemeSeoController.php` - Theme and SEO settings

#### Models (18)
- `Page.php` - CMS pages
- `PageVersion.php` - Page versions
- `PageBlock.php` - Page blocks
- `Banner.php` - Banners
- `Redirect.php` - URL redirects
- `Menu.php` - Navigation menus
- `MenuItem.php` - Menu items
- `BlogPost.php` - Blog posts
- `BlogCategory.php` - Blog categories
- `Faq.php` - FAQs
- `FaqCategory.php` - FAQ categories
- `Tutorial.php` - Tutorials
- `ContentReport.php` - Content reports
- `SeoConfig.php` - SEO settings
- `MetaTag.php` - Meta tags
- `OgTag.php` - Open Graph tags
- `ThemeConfig.php` - Theme settings
- `CustomCode.php` - Custom CSS/JS

#### Services (1)
- `SeoAnalyzerService.php` - SEO analysis

---

### 18. **REPORTING & ANALYTICS MODULE**

**Purpose:** Financial reports, user reports, compliance reports, custom report builder, scheduled reports.

#### Controllers (5)
- `Api/Admin/ReportController.php` - General reports
- `Api/Admin/AdvancedReportController.php` - Advanced reporting
- `Api/Admin/AnalyticsController.php` - Analytics dashboards
- `Api/Admin/ComplianceReportController.php` - Compliance reports
- `Api/User/PortfolioController.php` - User portfolio reports

#### Models (8)
- `Report.php` - Report definitions
- `ScheduledReport.php` - Scheduled reports
- `ReportTemplate.php` - Report templates
- `CustomReport.php` - Custom reports
- `ComplianceReport.php` - Compliance reports
- `FinancialReport.php` - Financial reports
- `UserAnalytics.php` - User analytics
- `SystemMetrics.php` - System metrics

#### Services (1)
- `ReportService.php` - Report generation logic

#### Jobs (1)
- `GenerateScheduledReportJob.php` - Async report generation

---

### 19. **COMPLIANCE & LEGAL MODULE**

**Purpose:** Legal documents (T&C, Privacy Policy), versioning, user acceptance tracking, GDPR compliance.

#### Controllers (4)
- `Api/Admin/ComplianceController.php` - Compliance management
- `Api/Admin/ConsentManagementController.php` - Consent management
- `Api/Public/LegalDocumentController.php` - Public legal docs
- `Api/Public/CookieConsentController.php` - Cookie consent

#### Models (7)
- `LegalAgreement.php` - Legal documents
- `LegalAgreementVersion.php` - Document versions
- `UserLegalAcceptance.php` - User acceptances
- `LegalAgreementAuditTrail.php` - Audit logs
- `CookieConsent.php` - Cookie consents
- `ComplianceRecord.php` - Compliance tracking
- `GdprRequest.php` - GDPR requests

---

### 20. **SYSTEM ADMINISTRATION MODULE**

**Purpose:** System settings, feature flags, roles/permissions, backups, logs, monitoring, performance, caching.

#### Controllers (18)
- `Api/Admin/SettingsController.php` - System settings
- `Api/Admin/AdminDashboardController.php` - Admin dashboard
- `Api/Admin/RoleController.php` - Role management
- `Api/Admin/FeatureFlagController.php` - Feature flags
- `Api/Admin/BackupController.php` - Backup management
- `Api/Admin/AuditLogController.php` - Audit logs
- `Api/Admin/AdminActivityController.php` - Admin activity tracking
- `Api/Admin/LogManagementController.php` - Log management
- `Api/Admin/SystemHealthController.php` - System health monitoring
- `Api/Admin/SystemMonitorController.php` - System monitoring
- `Api/Admin/PerformanceMonitoringController.php` - Performance metrics
- `Api/Admin/CacheManagementController.php` - Cache management
- `Api/Admin/DatabaseOptimizationController.php` - Database optimization
- `Api/Admin/IpWhitelistController.php` - IP whitelisting
- `Api/Admin/IntegrationManagementController.php` - Third-party integrations
- `Api/Admin/DeveloperToolsController.php` - Developer tools
- `Api/Admin/DashboardCustomizationController.php` - Dashboard widgets
- `Api/Public/GlobalSettingsController.php` - Public settings

#### Models (20+)
- `Setting.php` - Key-value settings
- `FeatureFlag.php` - Feature toggles
- `Role.php` - Roles
- `Permission.php` - Permissions
- `AuditLog.php` - Audit trail
- `ActivityLog.php` - Activity logs
- `AdminPreference.php` - Admin preferences
- `AdminDashboardWidget.php` - Dashboard widgets
- `SystemHealth.php` - Health metrics
- `PerformanceMetric.php` - Performance data
- `QueueJob.php` - Queue jobs
- `FailedJob.php` - Failed jobs
- `WebhookLog.php` - Webhook logs
- `ApiLog.php` - API logs
- `ErrorLog.php` - Error logs
- `IpWhitelist.php` - IP whitelist
- `Backup.php` - Backup records
- `CacheEntry.php` - Cache entries
- `CronJob.php` - Cron job definitions
- `ApiTestCase.php` - API test cases
- `ApiTestResult.php` - API test results

#### Services (3)
- `CacheService.php` - Cache operations
- `FileUploadService.php` - File upload handling
- `CaptchaService.php` - CAPTCHA verification

---

## FRONTEND MODULES

### 1. **ADMIN PANEL** (`/frontend/app/admin`)

**Total Routes:** 40+ distinct admin pages

**Sub-Modules:**
1. Dashboard (`/admin/dashboard`)
2. User Management (`/admin/users`, `/admin/users/[userId]`)
3. Company User Management (`/admin/company-users`)
4. KYC Queue (`/admin/kyc-queue`)
5. Payment Management (`/admin/payments`)
6. Withdrawal Queue (`/admin/withdrawal-queue`)
7. Bonus Management (`/admin/bonuses`, `/admin/bonuses/management`)
8. Lucky Draw Management (`/admin/lucky-draws`)
9. Profit Sharing (`/admin/profit-sharing`)
10. Inventory Management (`/admin/inventory/*`)
11. Support System (`/admin/support/*`)
12. Help Center Management (`/admin/help-center/*`)
13. Content Management (`/admin/content/*`)
14. Compliance Manager (`/admin/compliance-manager/*`)
15. Notifications (`/admin/notifications/*`)
16. Reports (`/admin/reports/*`)
17. System Management (`/admin/system/*`)
18. Settings (20+ setting pages under `/admin/settings/*`)

---

### 2. **USER DASHBOARD** (`/frontend/app/(user)`)

**Total Routes:** 20+ user-facing pages

**Sub-Modules:**
1. Dashboard (`/dashboard`)
2. Profile (`/Profile`)
3. KYC (`/kyc`)
4. Portfolio (`/portfolio`)
5. Subscription Management (`/subscribe`, `/subscription`, `/plan`)
6. Wallet (`/wallet`)
7. Transactions (`/transactions`)
8. Bonuses (`/bonuses`)
9. Referrals (`/referrals`)
10. Lucky Draws (`/lucky-draws`)
11. Profit Sharing (`/profit-sharing`)
12. Support (`/support`, `/support/[ticketId]`)
13. Notifications (`/notifications`)
14. Settings (`/settings`)
15. Compliance (`/compliance`)
16. Offers (`/offers`, `/offers/[id]`)
17. Promotional Materials (`/materials`, `/promote`)
18. Learning Center (`/learn`)
19. Reports (`/reports`)

---

### 3. **COMPANY PORTAL** (`/frontend/app/company`)

**Total Routes:** 12+ company pages

**Sub-Modules:**
1. Authentication (`/company/login`, `/company/register`)
2. Dashboard (`/company/dashboard`)
3. Profile (`/company/profile`, `/company/account`)
4. Team Management (`/company/team`)
5. Analytics (`/company/analytics`)
6. Deals (`/company/deals`)
7. Documents (`/company/documents`)
8. Financial Reports (`/company/financial-reports`)
9. Funding (`/company/funding`)
10. Investor Interests (`/company/investor-interests`)
11. Q&A (`/company/qna`)
12. Updates (`/company/updates`)
13. Webinars (`/company/webinars`)

---

### 4. **PUBLIC PAGES** (`/frontend/app/(public)`)

**Total Routes:** 30+ public pages

**Sub-Modules:**
1. Landing Pages (`/`, `/home-1` to `/home-7`)
2. Authentication (`/login`, `/signup`)
3. About (`/about`, `/about/story`, `/about/team`, `/about/trust`)
4. Companies (`/companies`, `/companies/[slug]`, `/companies/compare`)
5. Products (`/products`, `/products/[slug]`)
6. Plans (`/plans`)
7. Insights (`/insights`, `/insights/market`, `/insights/news`, `/insights/reports`, `/insights/tutorials`)
8. Blog (`/blog`, `/blog/[slug]`)
9. FAQ (`/faq`)
10. Help Center (`/help-center`, `/help-center/ticket`)
11. Contact (`/contact`)
12. Legal Pages (10+ pages: `/terms`, `/privacy-policy`, `/refund-policy`, `/risk-disclosure`, `/cookie-policy`, `/sebi-regulations`, `/investor-charter`, `/grievance-redressal`, `/aml-kyc-policy`)
13. Tools (`/calculator`, `/verify`)
14. Careers (`/careers`)
15. Press (`/press`)
16. Explore (`/explore`)
17. Private Equity (`/private-equity`)

---

## SHARED COMPONENTS & UTILITIES

### Frontend Components (`/frontend/components`)

**Component Categories:**
1. **UI Components** (`components/ui`) - Reusable shadcn/ui components
2. **Admin Components** (`components/admin`) - Admin-specific components
3. **User Components** (`components/features`) - User-facing features
4. **Company Components** (`components/company`) - Company portal components
5. **Shared Components** (`components/shared`) - Shared across all portals
6. **Support Components** (`components/support`) - Support system components
7. **Help Components** (`components/help`) - Help center components
8. **Legal Components** (`components/legal`) - Legal/compliance components
9. **AML Components** (`components/aml`) - AML/KYC components
10. **Block Components** (`components/blocks`) - Page builder blocks

### Backend Shared Code

1. **Traits** (`app/Http/Traits`, `app/Services/Traits`)
2. **Helpers** (`app/Helpers`)
3. **Events** (`app/Events`)
4. **Exports** (`app/Exports`) - Excel/CSV exports
5. **Mail** (`app/Mail`) - Mailable classes
6. **Notifications** (`app/Notifications`) - Notification classes
7. **Observers** (`app/Observers`) - Model observers
8. **Resources** (`app/Http/Resources`) - API resources
9. **Requests** (`app/Http/Requests`) - Form request validation

---

## INFRASTRUCTURE & CONFIGURATION

### Backend Configuration (`/backend/config`)
- Application, database, cache, queue, mail, services, logging, etc.

### Database (`/backend/database`)
- **Migrations:** 92+ migration files
- **Seeders:** Database seeders
- **Factories:** Model factories for testing

### Testing (`/backend/tests`)
- **Feature Tests** - HTTP endpoint tests
- **Unit Tests** - Isolated service/helper tests
- **Load Testing** - Performance testing scripts

### Deployment (`/backend/deploy`)
- Deployment scripts, supervisor configs, CI/CD configurations

### Documentation (`/backend/docs`)
- API documentation, feature documentation

---

## MODULE SUMMARY TABLE

| #  | Module Name 			| Backend Controllers 	| Backend Models| BackendService| FrontendRoutes| Complexity |
|----|----------------------------------|-----------------------|---------------|---------------|---------------|------------|
| 1  | Authentication & Authorization 	| 6 			| 7 		| 2 		| 3 		| High 	     |
| 2  | User Management 			| 8 			| 5 		| 0 		| 5 		| Medium     |
| 3  | KYC Management 			| 2 			| 4 		| 1 		| 2 		| Medium     |
| 4  | Investment Plans 		| 2 			| 4 		| 1 		| 2 		| High 	     |
| 5  | Subscription Management 		| 2 			| 2 		| 2 		| 3 		| High       |
| 6  | Payment & Withdrawal 		| 5 			| 4 		| 6 		| 3 		| Critical   |
| 7  | Wallet Management 		| 2 			| 2 		| 1 		| 1 		| Critical   |
| 8  | Bonus Calculation Engine 	| 2 			| 4 		| 2 		| 1 		| Critical   |
| 9  | Referral System 			| 3 			| 5 		| 1 		| 2 		| Medium     |
| 10 | Lucky Draw 			| 2 			| 4 		| 1 		| 2 		| Medium     |
| 11 | Profit Sharing 			| 2 			| 3 		| 2* 		| 2 		| High       |
| 12 | Pre-IPO Products & Inventory 	| 3 			| 14 		| 2 		| 3 		| High       |
| 13 | Company Portal (B2B) 		| 12 			| 14 		| 0 		| 13		| High       |
| 14 | Support & Helpdesk 		| 8 			| 10 		| 2 		| 3 		| High       |
| 15 | Knowledge Base & Help Center 	| 6 			| 5 		| 0 		| 2 		| Medium     |
| 16 | Notification System 		| 7 			| 12 		| 6 		| 2 		| High       |
| 17 | CMS & Content Management 	| 17 			| 18 		| 1 		| 15+ 		| High       |
| 18 | Reporting & Analytics 		| 5 			| 8 		| 1 		| 2 		| High       |
| 19 | Compliance & Legal 		| 4 			| 7 		| 0 		| 10+ 		| Medium     |
| 20 | System Administration 		| 18 			| 20+ 		| 3 		| 40+ 		| Critical   |

**Notes:**
- `*` indicates duplicate services found (needs consolidation)
- Complexity ratings: **Critical** (financial/core logic), **High** (complex business logic), **Medium** (standard CRUD + business rules)

---

## ISSUES IDENTIFIED (Initial Scan)

### 🔴 **Critical Issues**

1. **Duplicate Services:** `ProfitShareService.php` and `ProfitSharingService.php` - needs consolidation
2. **Missing Service Layer:** User Management module has no dedicated `UserService.php` - business logic likely in controllers (violates "thin controllers, fat services" principle)
3. **Missing Service Layer:** Company Portal has **12 controllers but 0 services** - major architectural flaw
4. **Missing Service Layer:** Knowledge Base has **6 controllers but 0 services**
5. **Missing Service Layer:** Compliance module has **4 controllers but 0 services**

### 🟡 **High Priority Issues**

6. **Inconsistent Naming:** Some controllers use "Kb" (KbArticleController) while others use full names (KnowledgeBaseArticleController)
7. **Incomplete Jobs:** SMS sending appears to use jobs (`ProcessEmailJob` exists) but no `ProcessSmsJob` found
8. **Test Coverage Unknown:** Test file count not yet analyzed
9. **Migration Count Mismatch:** 92 migrations found vs. 95+ tables documented
10. **Possible Code Duplication:** Multiple controllers for similar functionality (e.g., KbArticleController vs KnowledgeBaseArticleController)

---

## NEXT STEPS (Phase 2+)

1. **Deep dive into each module** to identify:
   - Architectural flaws
   - Anti-patterns
   - Security vulnerabilities
   - Performance bottlenecks
   - Missing tests
   - Code duplication
   - Poor abstractions
   - Inconsistencies

2. **Analyze dependencies** between modules

3. **Review database schema** for normalization, indexing, relationships

4. **Audit security**: Authentication, authorization, input validation, SQL injection, XSS

5. **Performance analysis**: N+1 queries, caching strategy, job queue efficiency

6. **Generate module-wise scores** (0-10) based on:
   - Code quality
   - Architecture
   - Security
   - Performance
   - Testability
   - Maintainability

7. **Create priority-ordered fix roadmap**

8. **Recommend refactored folder structure**

---

**End of Phase 1: Module Inventory**
