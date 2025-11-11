# FSD Quick Reference & Implementation Checklist
## PreIPO SIP Platform - Developer Guide

**Document Purpose:** Quick reference for developers implementing the fully configurable PreIPO SIP platform.

---

## CORE PRINCIPLE: 100% Admin Configurable

**Zero Hardcoded Values** - Everything must be:
- ✅ Stored in database
- ✅ Configurable via admin panel
- ✅ Changeable without code deployment
- ✅ Version controlled (for critical settings)
- ✅ Audit logged (who changed what, when)

---

## MODULE CHECKLIST

### 1. SYSTEM CONFIGURATION (20 Functions)
- [ ] Module on/off toggles (Registration, Login, Investment, Withdrawal, Referral, Lucky Draw, Profit Share, KYC, Support, Bonuses)
- [ ] Maintenance mode with custom message
- [ ] Backup configuration and scheduling
- [ ] Cron job management (enable/disable, schedule editing, manual execution)

### 2. INVESTMENT PLANS (15 Functions)
- [ ] Create unlimited plans (A, B, C, D, E, F, etc.)
- [ ] Edit all plan attributes (name, amount, duration, bonuses)
- [ ] Progressive bonus configuration (rate, formula, month-by-month override)
- [ ] Milestone bonus configuration (unlimited milestones at any month)
- [ ] Consistency bonus configuration (amount, streak multipliers)
- [ ] Referral multiplier tiers (unlimited tiers with custom multipliers)
- [ ] Profit sharing percentage per plan
- [ ] Lucky draw entries per plan
- [ ] Celebration bonuses per plan
- [ ] Plan features list (add/edit/delete unlimited)
- [ ] Eligibility rules (age, KYC, country restrictions)
- [ ] Upgrade/downgrade rules and penalties
- [ ] Pause/cancel rules
- [ ] Plan comparison table customization
- [ ] Duplicate plan feature

### 3. PRE-IPO PRODUCTS (12 Functions)
- [ ] Add unlimited products with complete details
- [ ] Edit all product fields
- [ ] Product media gallery (unlimited images/videos)
- [ ] Pricing configuration (face value, market price, history)
- [ ] Allocation rules (auto/manual, priority, limits)
- [ ] Company information (about, highlights, founders, funding)
- [ ] Financial information (revenue, P&L, documents)
- [ ] Risk disclosures (unlimited, categorized)
- [ ] News & updates (unlimited articles)
- [ ] Document management (prospectus, statements, legal)
- [ ] Compliance information (SEBI approval, regulatory)
- [ ] Archive/delete products

### 4. BULK PURCHASE MANAGEMENT (9 Functions)
- [ ] Add bulk purchase (product, cost, discount %, extra allocation %)
- [ ] Edit bulk purchase details
- [ ] View real-time allocation status per purchase
- [ ] View allocation history
- [ ] Manual allocation from bulk purchase
- [ ] Inventory dashboard per product
- [ ] Low stock alerts configuration
- [ ] Reorder suggestions based on allocation rate

### 5. BONUS CONFIGURATION (20 Functions)
- [ ] Global bonus on/off controls per type
- [ ] Progressive bonus global override
- [ ] Milestone bonus global override
- [ ] Bonus calculation formula editor (JavaScript)
- [ ] View all bonus transactions with filters
- [ ] Manual bonus entry for any user
- [ ] Reverse/cancel incorrectly credited bonus
- [ ] Bulk bonus processing (CSV upload or select users)
- [ ] Referral bonus settings (amount, completion criteria)
- [ ] Referral tier configuration (unlimited tiers)
- [ ] Referral campaign manager (limited-time campaigns)
- [ ] Celebration events management (add unlimited events)
- [ ] Birthday bonus configuration
- [ ] Anniversary bonus configuration
- [ ] Bonus allocation source configuration
- [ ] Max bonus percentage cap
- [ ] Bonus rounding rules
- [ ] Bonus processing frequency
- [ ] Bonus testing/calculation tool

### 6. LUCKY DRAW CONFIGURATION (15 Functions)
- [ ] Draw frequency configuration (monthly, quarterly, custom)
- [ ] Prize structure configuration (unlimited tiers)
- [ ] Entry rules per plan
- [ ] Bonus entries for on-time payments/streaks
- [ ] Create new draw manually
- [ ] Edit draw before execution
- [ ] Cancel draw
- [ ] Manual draw execution interface
- [ ] Automatic draw execution (cron)
- [ ] Prize distribution (auto-credit to wallet or shares)
- [ ] Winner management (view, disqualify, replace)
- [ ] Result publishing controls (privacy settings)
- [ ] Winner certificates generation
- [ ] Draw video upload for transparency
- [ ] Draw statistics and analytics

### 7. PROFIT SHARING CONFIGURATION (10 Functions)
- [ ] Profit sharing global settings (frequency, auto-calculate)
- [ ] Profit share percentage per plan
- [ ] Profit calculation formula configuration
- [ ] Eligibility criteria (min months, min investment)
- [ ] Create profit share period
- [ ] Calculate distribution preview
- [ ] Approve & distribute
- [ ] Manual adjustments per user
- [ ] Reverse distribution (if error)
- [ ] Publish financial report with visibility controls

### 8. USER MANAGEMENT (18 Functions)
- [ ] View all users (filters, search, export)
- [ ] View user details (all tabs: profile, KYC, subscriptions, etc.)
- [ ] Create new user manually (admin entry)
- [ ] Edit user profile (any field)
- [ ] Delete user (soft delete with anonymization)
- [ ] Suspend user (temporary with reason)
- [ ] Block user (permanent with blacklisting options)
- [ ] Unblock/unsuspend user
- [ ] Adjust user wallet balance manually
- [ ] Manual bonus award to user
- [ ] Override investment allocation
- [ ] Force payment processing
- [ ] Send email to user
- [ ] Send SMS to user
- [ ] Send push notification
- [ ] Bulk user actions (email, status change, export, delete)
- [ ] Advanced user search with multiple criteria
- [ ] User segmentation for targeted actions

### 9. KYC MANAGEMENT (12 Functions)
- [ ] Document type configuration (required/optional)
- [ ] Add custom document types
- [ ] Auto-verification settings (Aadhaar, PAN, Bank APIs)
- [ ] KYC queue management with filters
- [ ] Document verification interface (zoom, rotate, OCR)
- [ ] Verification checklist (cannot approve without completing)
- [ ] Approve KYC
- [ ] Reject KYC with detailed reasons
- [ ] Request resubmission with instructions
- [ ] Add verification notes (internal)
- [ ] KYC statistics dashboard
- [ ] KYC compliance report generation

### 10. PAYMENT & WITHDRAWAL (17 Functions)
- [ ] Payment gateway setup (multiple gateways)
- [ ] Payment methods configuration (enable/disable, fees)
- [ ] Auto-debit configuration (mandate settings)
- [ ] View all payments with filters
- [ ] View payment details
- [ ] Manual payment entry (offline payments)
- [ ] Refund payment (full or partial)
- [ ] Handle failed payments (retry, contact user)
- [ ] Withdrawal settings (limits, fees, auto-approval)
- [ ] Withdrawal fee tiers configuration
- [ ] View withdrawal queue with SLA indicators
- [ ] View withdrawal details with fraud checks
- [ ] Approve withdrawal
- [ ] Reject withdrawal
- [ ] Process withdrawal (manual or API)
- [ ] Bulk withdrawal processing
- [ ] Withdrawal analytics

### 11. FRONTEND MANAGEMENT (21 Functions)
- [ ] Homepage content editor (all sections)
- [ ] About Us page editor
- [ ] How It Works page editor
- [ ] Plans page customization
- [ ] Products page customization
- [ ] Contact Us page editor
- [ ] FAQ page manager (categories, questions)
- [ ] Blog system (posts, categories, tags)
- [ ] Custom page builder (drag-drop blocks)
- [ ] Header menu editor (multi-level)
- [ ] Footer menu editor (columns, links)
- [ ] Color scheme configuration (all colors)
- [ ] Typography configuration (fonts, sizes)
- [ ] Logo & branding uploads
- [ ] Custom CSS/JS code
- [ ] Responsive design settings
- [ ] Custom form builder
- [ ] Lead capture forms
- [ ] Announcement banner
- [ ] Promotional banners
- [ ] Popup/modal manager

### 12. SEO & META MANAGEMENT (7 Functions)
- [ ] Global SEO configuration
- [ ] Per-page SEO settings (title, description, OG tags)
- [ ] SEO analysis tool with scoring
- [ ] Sitemap manager (auto-generate, submit to search engines)
- [ ] Robots.txt editor
- [ ] Redirects manager (301, 302)
- [ ] Analytics integration (GA, Facebook Pixel, others)

### 13. NOTIFICATION SYSTEM (20 Functions)
- [ ] Email provider configuration (SMTP, SendGrid, etc.)
- [ ] Email templates manager (view all)
- [ ] Edit email templates (subject, body with variables)
- [ ] Email variables system
- [ ] Email sending rules per template
- [ ] Email logs & tracking (opens, clicks)
- [ ] SMS provider configuration
- [ ] SMS templates manager
- [ ] Edit SMS templates (max 160 chars)
- [ ] SMS sending rules
- [ ] SMS logs
- [ ] Push notification configuration (FCM, OneSignal)
- [ ] Push templates manager
- [ ] Send manual push notifications
- [ ] In-app notification manager
- [ ] Notification preferences (what users can control)
- [ ] Notification channels priority
- [ ] Notification batching configuration
- [ ] Critical notifications override
- [ ] Notification testing tool

### 14. REPORTING & ANALYTICS (21 Functions)
- [ ] Revenue report
- [ ] Profit & Loss statement
- [ ] Bonus distribution report
- [ ] Investment analysis report
- [ ] Cash flow statement
- [ ] Transaction report
- [ ] User growth report
- [ ] User retention report
- [ ] KYC completion report
- [ ] User demographics report
- [ ] Subscription performance report
- [ ] Payment collection report
- [ ] Referral performance report
- [ ] Product performance report
- [ ] Portfolio performance report
- [ ] SEBI compliance report
- [ ] TDS report
- [ ] AML compliance report
- [ ] Audit trail report
- [ ] Custom report builder
- [ ] Scheduled reports

### 15. SYSTEM SETTINGS (27 Functions)
- [ ] Basic site settings (name, contact, address, timezone)
- [ ] Operational settings (pagination, timeouts, file limits)
- [ ] Maintenance mode
- [ ] Backup settings
- [ ] Cron jobs configuration
- [ ] Password policy
- [ ] Two-factor authentication (2FA) settings
- [ ] IP whitelisting
- [ ] CAPTCHA configuration
- [ ] Rate limiting
- [ ] SSL/HTTPS settings
- [ ] Email queue settings
- [ ] Email throttling
- [ ] Email blacklist
- [ ] Payment limits configuration
- [ ] Payment security settings
- [ ] Payment webhook configuration
- [ ] Notification channels priority
- [ ] API access configuration
- [ ] Webhook configuration (outgoing)
- [ ] Third-party integration management
- [ ] Database optimization
- [ ] Cache management
- [ ] Log management
- [ ] Performance monitoring
- [ ] Role management
- [ ] Permission management

### 16. SUPPORT SYSTEM (12 Functions)
- [ ] Ticket system settings (enabled, auto-assign logic)
- [ ] Ticket categories management
- [ ] Ticket priority levels configuration
- [ ] Canned responses (templates)
- [ ] Ticket auto-close configuration
- [ ] Live chat settings
- [ ] Chat agents management
- [ ] Chat transcript storage
- [ ] Knowledge base configuration
- [ ] KB categories management
- [ ] KB articles creation
- [ ] KB search analytics

### 17. COMPLIANCE & LEGAL (9 Functions)
- [ ] Terms & Conditions editor (versioned)
- [ ] Privacy Policy editor (versioned)
- [ ] Refund/Cancellation Policy editor
- [ ] Risk Disclosure Statement editor
- [ ] Cookie consent banner configuration
- [ ] User data export (GDPR)
- [ ] User data deletion (Right to be Forgotten)
- [ ] Data retention policy configuration
- [ ] Consent management

### 18. ADVANCED ADMIN FEATURES (18 Functions)
- [ ] Customizable admin dashboard (drag-drop widgets)
- [ ] Widget configuration per admin
- [ ] Dark mode toggle
- [ ] Bulk import users (CSV)
- [ ] Bulk update users
- [ ] Bulk import investments (offline)
- [ ] Data export wizard (any data type)
- [ ] Global activity log
- [ ] Admin audit trail
- [ ] Change log (before/after values)
- [ ] System health dashboard
- [ ] Error tracking
- [ ] Queue monitor
- [ ] Performance profiler
- [ ] Database query tool (SQL editor)
- [ ] API testing tool
- [ ] Task scheduler
- [ ] Feature flags

---

## DATABASE ARCHITECTURE

**Required Tables:** 55+

### Core Tables:
1. `users` - User accounts
2. `user_profiles` - Extended profile info
3. `user_kyc` - KYC data
4. `kyc_documents` - Document storage references
5. `plans` - Investment plans (configurable)
6. `plan_features` - Plan features list
7. `subscriptions` - User subscriptions
8. `payments` - Payment transactions
9. `products` - Pre-IPO products
10. `bulk_purchases` - Inventory management
11. `user_investments` - Share allocations
12. `bonus_transactions` - All bonuses
13. `referrals` - Referral tracking
14. `lucky_draws` - Draw definitions
15. `lucky_draw_entries` - User entries
16. `profit_shares` - Profit distribution periods
17. `user_profit_shares` - Individual distributions
18. `wallets` - User wallet balances
19. `transactions` - Financial transactions
20. `withdrawals` - Withdrawal requests
21. `support_tickets` - Support tickets
22. `support_messages` - Ticket messages
23. `notifications` - In-app notifications
24. `email_logs` - Email tracking
25. `sms_logs` - SMS tracking
26. `settings` - All system settings (key-value)
27. `pages` - CMS pages
28. `menus` - Navigation menus
29. `menu_items` - Menu links
30. `blog_posts` - Blog articles
31. `faqs` - FAQ items
32. `forms` - Custom forms
33. `form_submissions` - Form data
34. `banners` - Promotional banners
35. `redirects` - URL redirects
36. `activity_logs` - User/admin activity
37. `audit_trails` - Admin action logs
38. `roles` - User roles
39. `permissions` - Permission definitions
40. `role_permissions` - Role-permission mapping
41. `sessions` - Active sessions
42. `password_resets` - Reset tokens
43. `email_templates` - Email templates
44. `sms_templates` - SMS templates
45. `payment_gateways` - Gateway configs
46. `webhooks` - Webhook endpoints
47. `api_keys` - API authentication
48. `backups` - Backup logs
49. `cron_jobs` - Scheduled tasks
50. `reports` - Saved reports
51. `scheduled_reports` - Report schedules
52. `feature_flags` - Feature toggles
53. `consents` - User consents
54. `data_exports` - Export requests
55. `cache_records` - Cache management

**Key Points:**
- All tables need `created_at`, `updated_at` timestamps
- Critical tables need soft deletes (`deleted_at`)
- Foreign keys with proper indexing
- Audit fields (`created_by`, `updated_by`) where applicable
- JSON columns for flexible metadata

---

## TECHNICAL STACK

### Backend:
- **Framework:** Laravel 11 (PHP 8.3)
- **Database:** MySQL 8.0
- **Cache:** Redis 6.0+
- **Queue:** Laravel Queue with Redis driver
- **Storage:** Local or AWS S3

### Frontend:
- **User Interface:** React.js 18+ or Blade templates
- **Admin Panel:** React.js with Tailwind CSS
- **Styling:** Tailwind CSS 3.0+
- **Icons:** Heroicons or Lucide React

### Integrations:
- **Payment:** Razorpay, PayU
- **SMS:** MSG91, Twilio
- **Email:** SendGrid, SMTP
- **KYC:** DigiLocker, Income Tax API
- **Analytics:** Google Analytics, Facebook Pixel

---

## DEVELOPMENT PHASES

### Phase 1: Foundation (Weeks 1-4)
- [ ] Database schema creation
- [ ] Authentication system
- [ ] Admin panel base structure
- [ ] Settings management system
- [ ] Role & permission system

### Phase 2: Core Features (Weeks 5-10)
- [ ] User registration & KYC
- [ ] Plan management system
- [ ] Payment integration
- [ ] Subscription management
- [ ] Bonus calculation engine
- [ ] Portfolio management

### Phase 3: Advanced Features (Weeks 11-14)
- [ ] Referral system
- [ ] Lucky draw system
- [ ] Profit sharing system
- [ ] Wallet & withdrawals
- [ ] Bulk purchase management
- [ ] Support system

### Phase 4: Frontend & CMS (Weeks 15-18)
- [ ] Public website pages
- [ ] Content management system
- [ ] Page builder
- [ ] SEO management
- [ ] Blog system
- [ ] Forms system

### Phase 5: Reporting & Analytics (Weeks 19-20)
- [ ] All financial reports
- [ ] User reports
- [ ] Compliance reports
- [ ] Custom report builder
- [ ] Dashboard analytics

### Phase 6: Notifications & Communication (Weeks 21-22)
- [ ] Email system with templates
- [ ] SMS system with templates
- [ ] Push notifications
- [ ] In-app notifications
- [ ] Notification preferences

### Phase 7: Testing & Polish (Weeks 23-26)
- [ ] Unit testing (80% coverage)
- [ ] Integration testing
- [ ] User acceptance testing
- [ ] Performance testing
- [ ] Security testing
- [ ] Bug fixes
- [ ] Documentation

### Phase 8: Deployment (Week 27-28)
- [ ] Server setup
- [ ] Database optimization
- [ ] Caching configuration
- [ ] SSL setup
- [ ] Backup system
- [ ] Monitoring setup
- [ ] Launch!

---

## CRITICAL IMPLEMENTATION NOTES

### 1. Settings Management
**Never hardcode values.** Create a settings table:
```sql
CREATE TABLE settings (
    id BIGINT PRIMARY KEY,
    `key` VARCHAR(255) UNIQUE,
    value TEXT,
    type ENUM('string', 'number', 'boolean', 'json', 'text'),
    group VARCHAR(100),
    description TEXT,
    updated_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

Use helper function:
```php
function setting($key, $default = null) {
    return Settings::get($key, $default);
}
```

### 2. Plan Configuration Storage
Store plan bonus formulas as JSON:
```json
{
  "progressive_bonus": {
    "enabled": true,
    "start_month": 4,
    "rate": 0.6,
    "formula": "(month - 3) * 0.6 * multiplier",
    "overrides": {
      "12": 5.4,
      "24": 10.8
    }
  }
}
```

### 3. Bonus Calculation Engine
Create a dedicated service:
```php
class BonusCalculator {
    public function calculate(Subscription $sub, Payment $payment) {
        // Progressive
        // Milestone
        // Consistency
        // All configurable from database
    }
}
```

### 4. Permission System
Every admin action must check permission:
```php
if (!auth()->user()->can('approve-kyc')) {
    abort(403);
}
```

### 5. Audit Logging
Log every significant action:
```php
AuditLog::create([
    'user_id' => auth()->id(),
    'action' => 'kyc.approved',
    'target_type' => 'User',
    'target_id' => $user->id,
    'old_values' => $old,
    'new_values' => $new,
    'ip_address' => request()->ip()
]);
```

### 6. Queue Everything Heavy
Don't block requests:
```php
// Send email
SendEmailJob::dispatch($user, $template);

// Calculate bonuses
CalculateBonusJob::dispatch($subscription);

// Generate report
GenerateReportJob::dispatch($reportType, $filters);
```

### 7. Cache Aggressively
```php
Cache::remember('plans', 3600, function() {
    return Plan::active()->get();
});
```

### 8. Transaction Safety
Always use database transactions for financial operations:
```php
DB::transaction(function() use ($user, $amount) {
    $user->wallet->increment('balance', $amount);
    Transaction::create([...]);
    BonusTransaction::create([...]);
});
```

---

## TESTING CHECKLIST

### Unit Tests (80% Coverage):
- [ ] User model tests
- [ ] Plan model tests
- [ ] Bonus calculator tests
- [ ] Payment processing tests
- [ ] Wallet transaction tests
- [ ] Referral logic tests
- [ ] Settings management tests

### Integration Tests:
- [ ] Complete user registration flow
- [ ] KYC submission and approval flow
- [ ] Payment and allocation flow
- [ ] Bonus calculation flow
- [ ] Withdrawal flow
- [ ] Lucky draw execution
- [ ] Profit sharing distribution

### Security Tests:
- [ ] SQL injection attempts
- [ ] XSS attempts
- [ ] CSRF protection
- [ ] Authentication bypass attempts
- [ ] Authorization checks
- [ ] File upload validation
- [ ] Rate limiting

### Performance Tests:
- [ ] 10,000 concurrent users
- [ ] Bonus calculation for 30,000 users
- [ ] Report generation speed
- [ ] Database query optimization
- [ ] Page load times

---

## LAUNCH CHECKLIST

### Pre-Launch:
- [ ] All features tested
- [ ] Security audit completed
- [ ] Performance optimization done
- [ ] Backup system tested
- [ ] SSL certificate installed
- [ ] Payment gateways in live mode
- [ ] Email/SMS services configured
- [ ] Analytics installed
- [ ] Legal pages finalized
- [ ] Admin trained
- [ ] Support team trained

### Launch Day:
- [ ] Final database backup
- [ ] Deploy to production
- [ ] Smoke testing all critical flows
- [ ] Monitor error logs continuously
- [ ] Monitor server resources
- [ ] Monitor payment success rate
- [ ] Team on standby

### Post-Launch (First Week):
- [ ] Daily monitoring
- [ ] User feedback collection
- [ ] Bug triage and fixes
- [ ] Performance monitoring
- [ ] Support ticket response

---

**Ready for Development!** 🚀
