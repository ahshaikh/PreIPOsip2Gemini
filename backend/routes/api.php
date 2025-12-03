<?php
// V-PHASE1-1730-014 (Created) | V-PHASE2-1730-049 | V-PHASE3-1730-087 | V-REMEDIATE-1730-087 | V-FINAL-1730-421 (Granular Perms) | V-FINAL-1730-435 (Security Hardened) | V-FINAL-1730-443 (SEC-8 Applied) | V-FINAL-1730-471 (2FA Routes Added) | V-FINAL-1730-593 (Notification Routes Added)

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth & Public Controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\SocialLoginController;
use App\Http\Controllers\Api\Public\PlanController as PublicPlanController;
use App\Http\Controllers\Api\Public\PageController as PublicPageController;
use App\Http\Controllers\Api\Admin\FaqController as PublicFaqController;
use App\Http\Controllers\Api\Admin\BlogPostController as PublicBlogController;
use App\Http\Controllers\Api\Public\GlobalSettingsController;
use App\Http\Controllers\Api\Public\ProductDataController;
use App\Http\Controllers\Api\Public\LegalDocumentController;

// User Controllers
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\KycController;
use App\Http\Controllers\Api\User\SubscriptionController;
use App\Http\Controllers\Api\User\PaymentController;
use App\Http\Controllers\Api\User\PortfolioController;
use App\Http\Controllers\Api\User\BonusController;
use App\Http\Controllers\Api\User\ReferralController as UserReferralController;
use App\Http\Controllers\Api\User\WalletController;
use App\Http\Controllers\Api\User\SupportTicketController as UserSupportTicketController;
use App\Http\Controllers\Api\User\LuckyDrawController as UserLuckyDrawController;
use App\Http\Controllers\Api\User\ProfitShareController as UserProfitShareController;
use App\Http\Controllers\Api\User\SecurityController;
use App\Http\Controllers\Api\User\PrivacyController;
use App\Http\Controllers\Api\User\TwoFactorAuthController;
use App\Http\Controllers\Api\User\WithdrawalController as UserWithdrawalController;
use App\Http\Controllers\Api\User\ActivityController;
use App\Http\Controllers\Api\User\NotificationController as UserNotificationController;
use App\Http\Controllers\Api\User\UserDashboardController;


// Admin Controllers
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\KycQueueController;
use App\Http\Controllers\Api\Admin\PlanController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\BulkPurchaseController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\Admin\PageController;
use App\Http\Controllers\Api\Admin\EmailTemplateController;
use App\Http\Controllers\Api\Admin\WithdrawalController;
use App\Http\Controllers\Api\Admin\LuckyDrawController as AdminLuckyDrawController;
use App\Http\Controllers\Api\Admin\ProfitShareController as AdminProfitShareController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\PerformanceMonitoringController;
use App\Http\Controllers\Api\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Api\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Api\Admin\BlogPostController as AdminBlogController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\CmsController;
use App\Http\Controllers\Api\Admin\ThemeSeoController;
use App\Http\Controllers\Api\Admin\ReferralController as AdminReferralController; // <-- ALIAS IS HERE
use App\Http\Controllers\Api\Admin\SystemHealthController;
use App\Http\Controllers\Api\Admin\AdminActivityController;
use App\Http\Controllers\Api\Admin\BackupController;
use App\Http\Controllers\Api\Admin\IpWhitelistController;
use App\Http\Controllers\Api\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\Admin\KbArticleController;
use App\Http\Controllers\Api\Admin\KbCategoryController;
use App\Http\Controllers\Api\Admin\ComplianceController;
use App\Http\Controllers\Api\Admin\DealController;
use App\Http\Controllers\Api\Admin\CompanyController;
use App\Http\Controllers\Api\Admin\TutorialController;
use App\Http\Controllers\Api\Admin\ContentReportController;
use App\Http\Controllers\Api\Admin\CompanyUserController;

// Company User Controllers
use App\Http\Controllers\Api\Company\AuthController as CompanyAuthController;
use App\Http\Controllers\Api\Company\CompanyProfileController;
use App\Http\Controllers\Api\Company\FinancialReportController;
use App\Http\Controllers\Api\Company\DocumentController as CompanyDocumentController;
use App\Http\Controllers\Api\Company\TeamMemberController;
use App\Http\Controllers\Api\Company\FundingRoundController;
use App\Http\Controllers\Api\Company\CompanyUpdateController;
use App\Http\Controllers\Api\Company\CompanyDealController;
use App\Http\Controllers\Api\Company\CompanyAnalyticsController;
use App\Http\Controllers\Api\Company\InvestorInterestController;
use App\Http\Controllers\Api\Company\CompanyQnaController;
use App\Http\Controllers\Api\Company\CompanyWebinarController;
use App\Http\Controllers\Api\Company\OnboardingWizardController;

// Public Company Controllers
use App\Http\Controllers\Api\Public\CompanyProfileController as PublicCompanyProfileController;

// Invoice & Webhook
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {

    // --- Public Authentication Routes ---
    Route::middleware('throttle:login')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/login/2fa', [AuthController::class, 'verifyTwoFactor']);
        Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLink']);
        Route::post('/password/reset', [PasswordResetController::class, 'reset']);
    });

    // OTP verification with stricter rate limiting (5 attempts per 10 minutes)
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:5,10');
    Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirectToProvider']);
    Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'handleProviderCallback']);

    // --- Public Data Routes ---
    Route::get('/plans', [PublicPlanController::class, 'index']);
    Route::get('/plans/{slug}', [PublicPlanController::class, 'show']);
    Route::get('/page/{slug}', [PublicPageController::class, 'show']);
    Route::get('/public/faqs', [PublicFaqController::class, 'index']);
    Route::get('/public/blog', [PublicBlogController::class, 'publicIndex']);
    Route::get('/public/blog/{slug}', [PublicBlogController::class, 'publicShow']);
    Route::get('/global-settings', [GlobalSettingsController::class, 'index']);
    Route::get('/products/{slug}/history', [ProductDataController::class, 'getPriceHistory']);

    // --- Dashboard Widgets ---
    Route::get('/announcements/latest', [UserDashboardController::class, 'announcements']);
    Route::get('/offers/active', [UserDashboardController::class, 'offers']);

    // --- Legal Documents (Public) ---
    Route::get('/legal/documents', [LegalDocumentController::class, 'index']);
    Route::get('/legal/documents/{type}', [LegalDocumentController::class, 'show']);
    Route::get('/legal/documents/{type}/download', [LegalDocumentController::class, 'download']);

    // --- Public Company Profiles ---
    Route::prefix('companies')->group(function () {
        Route::get('/', [PublicCompanyProfileController::class, 'index']);
        Route::get('/sectors', [PublicCompanyProfileController::class, 'sectors']);
        Route::get('/{slug}', [PublicCompanyProfileController::class, 'show']);
    });

    // --- KYC CALLBACK (Public) ---
    Route::get('/kyc/digilocker/callback', [KycController::class, 'handleDigiLockerCallback']);

    // --- Webhook Routes (V-SECURITY: Signature verification required) ---
    Route::post('/webhooks/razorpay', [WebhookController::class, 'handleRazorpay'])
        ->middleware(['webhook.verify:razorpay', 'throttle:60,1']); // 60 requests per minute

    // --- Authenticated User Routes ---
    Route::middleware('auth:sanctum')->group(function () {
        
        Route::post('/logout', [AuthController::class, 'logout']);

        // === USER ROUTES ===
        Route::prefix('user')->group(function () {
            // Profile & Security
            Route::get('/profile', [ProfileController::class, 'show']);
            Route::put('/profile', [ProfileController::class, 'update']);
            Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);
            Route::get('/bank-details', [ProfileController::class, 'getBankDetails']);
            Route::put('/bank-details', [ProfileController::class, 'updateBankDetails']);

            // User Settings
            Route::get('/settings', [App\Http\Controllers\Api\User\UserSettingsController::class, 'index']);
            Route::put('/settings', [App\Http\Controllers\Api\User\UserSettingsController::class, 'update']);

            Route::post('/security/password', [SecurityController::class, 'updatePassword']);
            Route::get('/security/export-data', [PrivacyController::class, 'export']);
            Route::post('/security/delete-account', [PrivacyController::class, 'deleteAccount']);
            
            // 2FA Management
            Route::get('/2fa/status', [TwoFactorAuthController::class, 'status']);
            Route::post('/2fa/enable', [TwoFactorAuthController::class, 'enable']);
            Route::post('/2fa/confirm', [TwoFactorAuthController::class, 'confirm']);
            Route::post('/2fa/disable', [TwoFactorAuthController::class, 'disable']);
            Route::post('/2fa/recovery-codes/download', [TwoFactorAuthController::class, 'downloadRecoveryCodes']);
            Route::post('/2fa/recovery-codes/regenerate', [TwoFactorAuthController::class, 'regenerateRecoveryCodes']);
            
            // KYC
            Route::get('/kyc', [KycController::class, 'show']);
            Route::post('/kyc', [KycController::class, 'store']);
            Route::get('/kyc-documents/{id}/view', [KycController::class, 'viewDocument']);
            Route::get('/kyc/digilocker/redirect', [KycController::class, 'redirectToDigiLocker']);
            
            // Subscriptions
            Route::get('/subscription', [SubscriptionController::class, 'show']);
            Route::post('/subscription', [SubscriptionController::class, 'store']);
            Route::post('/subscription/change-plan', [SubscriptionController::class, 'changePlan']);
            Route::post('/subscription/pause', [SubscriptionController::class, 'pause']);
            Route::post('/subscription/resume', [SubscriptionController::class, 'resume']);
            Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
            
            Route::prefix('notifications')->group(function () {
                Route::get('/', [UserNotificationController::class, 'index']); // Use Alias
                Route::get('unread-count', [UserNotificationController::class, 'unreadCount']);
                Route::patch('{id}/read', [UserNotificationController::class, 'markAsRead']);
                Route::post('mark-all-read', [UserNotificationController::class, 'markAllAsRead']);
            });

            // Payments - Financial operations rate limited
            Route::middleware('throttle:financial')->group(function () {
                Route::post('/payment/initiate', [PaymentController::class, 'initiate']);
                Route::post('/payment/verify', [PaymentController::class, 'verify']);
                Route::post('/payment/manual', [PaymentController::class, 'submitManual']);
            });
            Route::get('/payments/{payment}/invoice', [InvoiceController::class, 'download']);

            // Portfolio & Bonuses - Data-heavy endpoints rate limited
            Route::middleware('throttle:data-heavy')->group(function () {
                Route::get('/portfolio', [PortfolioController::class, 'index']);
                Route::get('/portfolio/statement', [PortfolioController::class, 'downloadStatement']);
                Route::get('/portfolio/transactions', [PortfolioController::class, 'transactions']);
                Route::get('/bonuses', [BonusController::class, 'index']);
                Route::get('/bonuses/pending', [BonusController::class, 'pending']);
                Route::get('/bonuses/export', [BonusController::class, 'export']);
                Route::get('/referrals', [UserReferralController::class, 'index']);
                Route::get('/referrals/rewards', [UserReferralController::class, 'rewards']);
            });

            // Wallet & Withdrawal - Financial operations rate limited
            Route::get('/wallet', [WalletController::class, 'show']);
            Route::get('/wallet/statement', [WalletController::class, 'downloadStatement']);
            Route::get('/wallet/withdrawals', [WalletController::class, 'withdrawals']);

            Route::middleware('throttle:financial')->group(function () {
                Route::post('/wallet/deposit/initiate', [WalletController::class, 'initiateDeposit']);
                Route::post('/wallet/withdraw', [WalletController::class, 'requestWithdrawal']);
            });

            Route::get('/withdrawals', [UserWithdrawalController::class, 'index']);
            Route::post('/withdrawals/{withdrawal}/cancel', [UserWithdrawalController::class, 'cancel']);

            // Activity
            Route::get('/activity', [ActivityController::class, 'index']);

            // Support
            Route::apiResource('/support-tickets', UserSupportTicketController::class)->only(['index', 'store', 'show'])->names('user.support-tickets');
            Route::post('/support-tickets/{supportTicket}/reply', [UserSupportTicketController::class, 'reply']);
            Route::post('/support-tickets/{supportTicket}/close', [UserSupportTicketController::class, 'close']);
            Route::post('/support-tickets/{supportTicket}/rate', [UserSupportTicketController::class, 'rate']);
            
            // Bonus Modules
            Route::get('/lucky-draws', [UserLuckyDrawController::class, 'index']);
            Route::get('/profit-sharing', [UserProfitShareController::class, 'index']);
            
            // Legal Document Acceptance (Authenticated)
            Route::get('/legal/documents/{type}/acceptance-status', [LegalDocumentController::class, 'acceptanceStatus']);
            Route::post('/legal/documents/{type}/accept', [LegalDocumentController::class, 'accept']);
        });

        // === ADMIN ROUTES ===
        // V-SECURITY-FIX: IP whitelist MUST be checked BEFORE role check
        Route::prefix('admin')->middleware(['admin.ip', 'role:admin|super-admin'])->group(function () {
            
            Route::get('/dashboard', [AdminDashboardController::class, 'index']);

            // -------------------------------------------------------------
            // ADMIN REPORTS & ANALYTICS
            // -------------------------------------------------------------
            // We removed 'throttle:reports' so the page loads immediately (Bypass Fix)
            Route::prefix('reports')->group(function () {
                
                // Financials
                Route::get('financial-summary', [ReportController::class, 'financialSummary'])
                    ->middleware('permission:reports.view_financial');

                // Analytics Sub-group
                Route::prefix('analytics')->group(function () {
                    Route::get('users', [ReportController::class, 'analyticsUsers'])
                        ->middleware('permission:reports.view_user');
                    Route::get('products', [ReportController::class, 'analyticsProducts'])
                        ->middleware('permission:products.view');
                });

                // Export (Merged)
                Route::get('download', [ReportController::class, 'exportReport'])
                    ->middleware('permission:reports.export');
            });

            // Inventory Summary
            Route::get('inventory/summary', [ReportController::class, 'getInventorySummary'])
                ->middleware('permission:products.view');

            // System Backup
            Route::get('system/backup/db', [BackupController::class, 'downloadDbDump'])
                ->middleware('permission:system.manage_backups');

            // System Health
            Route::get('/system/health', [SystemHealthController::class, 'index'])->middleware('permission:system.view_health');
            Route::get('/system/activity-logs', [AdminActivityController::class, 'index'])->middleware('permission:system.view_logs');

            // -------------------------------------------------------------
            // ADMIN REFERRAL MANAGEMENT (Corrected with Alias)
            // -------------------------------------------------------------
            Route::prefix('referral-campaigns')->group(function () {
                // Fixes 500 Error by using the correct alias
                Route::get('stats', [AdminReferralController::class, 'stats']); 
                Route::get('/', [AdminReferralController::class, 'index']);
                Route::post('/', [AdminReferralController::class, 'store']);
                Route::put('{id}', [AdminReferralController::class, 'update']);
                Route::delete('{id}', [AdminReferralController::class, 'destroy']);
            });

            // Individual Referrals List
            Route::get('referrals', [AdminReferralController::class, 'listReferrals']);

            // -------------------------------------------------------------
            // ADMIN NOTIFICATIONS
            // -------------------------------------------------------------
            Route::prefix('notifications')->group(function () {
                // Dashboard & System
                Route::get('/', [AdminNotificationController::class, 'index']);
                Route::get('unread-count', [AdminNotificationController::class, 'unreadCount']);
                Route::get('system', [AdminNotificationController::class, 'system']); // Fixes 404
                
                // Actions
                Route::post('mark-all-read', [AdminNotificationController::class, 'markAllAsRead']);
                Route::patch('{id}/read', [AdminNotificationController::class, 'markAsRead']);
                Route::delete('{id}', [AdminNotificationController::class, 'destroy']);

                // Push Campaigns
                Route::get('push', [AdminNotificationController::class, 'pushIndex']);       
                Route::get('push/stats', [AdminNotificationController::class, 'pushStats']); 
                Route::get('templates', [AdminNotificationController::class, 'templates']);  
                Route::post('push/send', [AdminNotificationController::class, 'sendPush']);
                Route::post('push/schedule', [AdminNotificationController::class, 'schedulePush']);
                
                // SMS Testing
                Route::post('test-sms', [AdminNotificationController::class, 'sendTestSms']);
            });

            // -------------------------------------------------------------
            // ADMIN PAYMENTS & USERS
            // -------------------------------------------------------------
            Route::get('payments', [AdminPaymentController::class, 'index']);
            Route::get('payments/{payment}/invoice', [InvoiceController::class, 'download'])->middleware('permission:payments.view');

            // Performance Monitoring
            Route::prefix('performance')->group(function () {
                Route::get('/overview', [PerformanceMonitoringController::class, 'overview']);
                Route::get('/database', [PerformanceMonitoringController::class, 'databaseMetrics']);
                Route::get('/realtime', [PerformanceMonitoringController::class, 'realtime']);
            });

            // User Management
            Route::apiResource('/users', AdminUserController::class)->except(['store', 'update']);
            Route::get('/users/export/csv', [AdminUserController::class, 'export'])->middleware('permission:users.view');

            // Critical admin actions - Rate limited
            Route::middleware('throttle:admin-actions')->group(function () {
                Route::post('/users', [AdminUserController::class, 'store'])->middleware('permission:users.create');
                Route::put('/users/{user}', [AdminUserController::class, 'update'])->middleware('permission:users.edit');
                Route::post('/users/bulk-action', [AdminUserController::class, 'bulkAction'])->middleware('permission:users.edit');
                Route::post('/users/import', [AdminUserController::class, 'import'])->middleware('permission:users.create');
                Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->middleware('permission:users.suspend');
                Route::post('/users/{user}/adjust-balance', [AdminUserController::class, 'adjustBalance'])->middleware('permission:users.adjust_wallet');
            });

            Route::apiResource('/roles', RoleController::class)->middleware('permission:users.manage_roles');

            // KYC Management
            Route::get('/kyc-queue', [KycQueueController::class, 'index'])->middleware('permission:kyc.view_queue');
            Route::get('/kyc-queue/{id}', [KycQueueController::class, 'show'])->middleware('permission:kyc.view_queue');

            // KYC approval/rejection - Rate limited
            Route::middleware('throttle:admin-actions')->group(function () {
                Route::post('/kyc-queue/{id}/approve', [KycQueueController::class, 'approve'])->middleware('permission:kyc.approve');
                Route::post('/kyc-queue/{id}/reject', [KycQueueController::class, 'reject'])->middleware('permission:kyc.reject');
            });

            // Business Management
            Route::apiResource('/plans', PlanController::class)->middleware('permission:plans.edit');
            Route::apiResource('/products', ProductController::class)->middleware('permission:products.edit');
            Route::apiResource('/bulk-purchases', BulkPurchaseController::class)->middleware('permission:products.edit');
            
            // CMS & Settings
            Route::apiResource('/pages', PageController::class)->middleware('permission:settings.manage_cms');
            Route::get('/pages/{page}/analyze', [PageController::class, 'analyze'])->middleware('permission:settings.manage_cms');
            Route::apiResource('/email-templates', EmailTemplateController::class)->middleware('permission:settings.manage_notifications');
            Route::apiResource('/faqs', AdminFaqController::class)->middleware('permission:settings.manage_cms');
            Route::apiResource('/blog-posts', AdminBlogController::class)->middleware('permission:settings.manage_cms');
            // Route::apiResource('/referral-campaigns', AdminReferralCampaignController::class); // DEPRECATED -> Use AdminReferralController group above
            Route::apiResource('/kb-categories', KbCategoryController::class)->middleware('permission:settings.manage_cms');
            Route::apiResource('/kb-articles', KbArticleController::class)->middleware('permission:settings.manage_cms');

            // Compliance Manager - Legal Agreements
            Route::prefix('compliance')->middleware('permission:settings.manage_cms')->group(function () {
                
                // 1. Dashboard & List (Fixes 404 on /documents)
                // Maps /api/v1/admin/compliance/documents
                Route::get('/documents', [ComplianceController::class, 'index']);
                // Maps /api/v1/admin/compliance/stats (Fixes 404 on /stats if frontend calls it directly)
                Route::get('/stats', [ComplianceController::class, 'stats']);
                // Maps /api/v1/admin/compliance/documents/stats (If frontend calls nested)
                Route::get('/documents/stats', [ComplianceController::class, 'stats']);
                
                // 2. CRUD Operations
                Route::post('/documents', [ComplianceController::class, 'store']);
                Route::get('/documents/{id}', [ComplianceController::class, 'show']);
                Route::put('/documents/{id}', [ComplianceController::class, 'update']);
                Route::delete('/documents/{id}', [ComplianceController::class, 'destroy']);
                
                // 3. Actions (Publish/Archive)
                Route::post('/documents/{id}/publish', [ComplianceController::class, 'publish']);
                Route::post('/documents/{id}/archive', [ComplianceController::class, 'archive']);
                
                // 4. Versioning & Stats
                Route::get('/documents/{id}/versions', [ComplianceController::class, 'versions']);
                Route::get('/documents/{id}/acceptance-stats', [ComplianceController::class, 'acceptanceStats']);
                
                // 5. Audit Trail
                Route::get('/documents/{id}/audit-trail', [ComplianceController::class, 'auditTrail']);
                Route::get('/documents/{id}/audit-trail/stats', [ComplianceController::class, 'auditTrailStats']);
                Route::get('/documents/{id}/audit-trail/export', [ComplianceController::class, 'exportAuditTrail']);
            });

            Route::get('/settings', [SettingsController::class, 'index'])->middleware('permission:settings.view_system');

            // Critical settings changes - Rate limited
            Route::middleware('throttle:admin-actions')->group(function () {
                Route::put('/settings', [SettingsController::class, 'update'])->middleware('permission:settings.edit_system');
                Route::post('/notifications/test-sms', [AdminNotificationController::class, 'sendTestSms'])->middleware('permission:settings.manage_notifications');
            });
            
            // Marketing CMS
            Route::get('/menus', [CmsController::class, 'getMenus'])->middleware('permission:settings.manage_cms');
            Route::put('/menus/{menu}', [CmsController::class, 'updateMenu'])->middleware('permission:settings.manage_cms');
            Route::get('/banners', [CmsController::class, 'getBanners'])->middleware('permission:settings.manage_cms');
            Route::post('/banners', [CmsController::class, 'storeBanner'])->middleware('permission:settings.manage_cms');
            Route::put('/banners/{banner}', [CmsController::class, 'updateBanner'])->middleware('permission:settings.manage_cms');
            Route::delete('/banners/{banner}', [CmsController::class, 'destroyBanner'])->middleware('permission:settings.manage_cms');
            Route::get('/redirects', [CmsController::class, 'getRedirects'])->middleware('permission:settings.manage_cms');
            Route::post('/redirects', [CmsController::class, 'storeRedirect'])->middleware('permission:settings.manage_cms');
            Route::delete('/redirects/{redirect}', [CmsController::class, 'destroyRedirect'])->middleware('permission:settings.manage_cms');
            Route::post('/theme/update', [ThemeSeoController::class, 'updateTheme'])->middleware('permission:settings.manage_theme');
            Route::post('/seo/update', [ThemeSeoController::class, 'updateSeo'])->middleware('permission:settings.manage_theme');

            // IP Whitelist
            Route::apiResource('/ip-whitelist', IpWhitelistController::class)->middleware('permission:system.manage_backups');

            // Critical payment actions
            Route::middleware('throttle:admin-actions')->group(function () {
                Route::post('/payments/offline', [AdminPaymentController::class, 'storeOffline'])->middleware('permission:payments.offline_entry');
                Route::post('/payments/{payment}/refund', [AdminPaymentController::class, 'refund'])->middleware('permission:payments.refund');
                Route::post('/payments/{payment}/approve', [AdminPaymentController::class, 'approveManual'])->middleware('permission:payments.approve');
                Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'rejectManual'])->middleware('permission:payments.approve');
            });

            // Withdrawals
            Route::get('/withdrawal-queue', [WithdrawalController::class, 'index'])->middleware('permission:withdrawals.view_queue');

            Route::middleware('throttle:admin-actions')->group(function () {
                Route::post('/withdrawal-queue/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->middleware('permission:withdrawals.approve');
                Route::post('/withdrawal-queue/{withdrawal}/complete', [WithdrawalController::class, 'complete'])->middleware('permission:withdrawals.complete');
                Route::post('/withdrawal-queue/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->middleware('permission:withdrawals.reject');
            });
            
            // Bonus Modules
            Route::apiResource('/lucky-draws', AdminLuckyDrawController::class)->middleware('permission:bonuses.manage_config');
            Route::post('/lucky-draws/{luckyDraw}/execute', [AdminLuckyDrawController::class, 'executeDraw'])->middleware('permission:bonuses.manage_config');
            
            Route::apiResource('/profit-sharing', AdminProfitShareController::class)->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/calculate', [AdminProfitShareController::class, 'calculate'])->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/distribute', [AdminProfitShareController::class, 'distribute'])->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/adjust', [AdminProfitShareController::class, 'adjust'])->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/reverse', [AdminProfitShareController::class, 'reverse'])->middleware('permission:bonuses.manage_config');
            
            // Support
            Route::apiResource('/support-tickets', AdminSupportTicketController::class)->names('admin.support-tickets')->middleware('permission:users.view');
            Route::post('/support-tickets/{supportTicket}/reply', [AdminSupportTicketController::class, 'reply'])->middleware('permission:users.edit');
            Route::put('/support-tickets/{supportTicket}/status', [AdminSupportTicketController::class, 'updateStatus'])->middleware('permission:users.edit');

            // -------------------------------------------------------------
            // CONTENT MANAGEMENT SYSTEM
            // -------------------------------------------------------------
            // Deals Management (Live Deals, Upcoming Deals)
            Route::prefix('deals')->group(function () {
                Route::get('/', [DealController::class, 'index'])->middleware('permission:products.view');
                Route::post('/', [DealController::class, 'store'])->middleware('permission:products.create');
                Route::get('/statistics', [DealController::class, 'statistics'])->middleware('permission:products.view');
                Route::get('/{id}', [DealController::class, 'show'])->middleware('permission:products.view');
                Route::put('/{id}', [DealController::class, 'update'])->middleware('permission:products.edit');
                Route::delete('/{id}', [DealController::class, 'destroy'])->middleware('permission:products.delete');
            });

            // Companies Directory
            Route::apiResource('/companies', CompanyController::class)->middleware('permission:products.view');

            // Tutorials Management
            Route::apiResource('/tutorials', TutorialController::class)->middleware('permission:settings.manage_cms');

            // Reports Management (Market Analysis, Research Reports)
            Route::prefix('content-reports')->group(function () {
                Route::get('/', [ContentReportController::class, 'index'])->middleware('permission:settings.manage_cms');
                Route::post('/', [ContentReportController::class, 'store'])->middleware('permission:settings.manage_cms');
                Route::get('/{id}', [ContentReportController::class, 'show'])->middleware('permission:settings.manage_cms');
                Route::put('/{id}', [ContentReportController::class, 'update'])->middleware('permission:settings.manage_cms');
                Route::delete('/{id}', [ContentReportController::class, 'destroy'])->middleware('permission:settings.manage_cms');
            });

            // -------------------------------------------------------------
            // COMPANY USER MANAGEMENT (Admin)
            // -------------------------------------------------------------
            Route::prefix('company-users')->middleware('permission:users.view')->group(function () {
                Route::get('/', [CompanyUserController::class, 'index']);
                Route::get('/statistics', [CompanyUserController::class, 'statistics']);
                Route::get('/{id}', [CompanyUserController::class, 'show']);
                Route::post('/{id}/approve', [CompanyUserController::class, 'approve'])->middleware('permission:users.edit');
                Route::post('/{id}/reject', [CompanyUserController::class, 'reject'])->middleware('permission:users.edit');
                Route::post('/{id}/suspend', [CompanyUserController::class, 'suspend'])->middleware('permission:users.suspend');
                Route::post('/{id}/reactivate', [CompanyUserController::class, 'reactivate'])->middleware('permission:users.edit');
                Route::delete('/{id}', [CompanyUserController::class, 'destroy'])->middleware('permission:users.delete');
            });
        });

        // ========================================================================
        // COMPANY USER ROUTES
        // ========================================================================
        // Public Company Registration & Login
        Route::prefix('company')->group(function () {
            Route::middleware('throttle:login')->group(function () {
                Route::post('/register', [CompanyAuthController::class, 'register']);
                Route::post('/login', [CompanyAuthController::class, 'login']);
            });

            // Authenticated Company User Routes
            Route::middleware('auth:sanctum')->group(function () {
                // Authentication
                Route::post('/logout', [CompanyAuthController::class, 'logout']);
                Route::get('/profile', [CompanyAuthController::class, 'profile']);
                Route::put('/profile', [CompanyAuthController::class, 'updateProfile']);
                Route::post('/change-password', [CompanyAuthController::class, 'changePassword']);

                // Company Profile Management
                Route::prefix('company-profile')->group(function () {
                    Route::put('/update', [CompanyProfileController::class, 'update']);
                    Route::post('/upload-logo', [CompanyProfileController::class, 'uploadLogo']);
                    Route::get('/dashboard', [CompanyProfileController::class, 'dashboard']);
                });

                // Financial Reports
                Route::prefix('financial-reports')->group(function () {
                    Route::get('/', [FinancialReportController::class, 'index']);
                    Route::post('/', [FinancialReportController::class, 'store']);
                    Route::get('/{id}', [FinancialReportController::class, 'show']);
                    Route::put('/{id}', [FinancialReportController::class, 'update']);
                    Route::delete('/{id}', [FinancialReportController::class, 'destroy']);
                    Route::get('/{id}/download', [FinancialReportController::class, 'download']);
                });

                // Documents Management
                Route::prefix('documents')->group(function () {
                    Route::get('/', [CompanyDocumentController::class, 'index']);
                    Route::post('/', [CompanyDocumentController::class, 'store']);
                    Route::get('/{id}', [CompanyDocumentController::class, 'show']);
                    Route::put('/{id}', [CompanyDocumentController::class, 'update']);
                    Route::delete('/{id}', [CompanyDocumentController::class, 'destroy']);
                    Route::get('/{id}/download', [CompanyDocumentController::class, 'download']);
                });

                // Team Members
                Route::prefix('team-members')->group(function () {
                    Route::get('/', [TeamMemberController::class, 'index']);
                    Route::post('/', [TeamMemberController::class, 'store']);
                    Route::put('/{id}', [TeamMemberController::class, 'update']);
                    Route::delete('/{id}', [TeamMemberController::class, 'destroy']);
                });

                // Funding Rounds
                Route::prefix('funding-rounds')->group(function () {
                    Route::get('/', [FundingRoundController::class, 'index']);
                    Route::post('/', [FundingRoundController::class, 'store']);
                    Route::put('/{id}', [FundingRoundController::class, 'update']);
                    Route::delete('/{id}', [FundingRoundController::class, 'destroy']);
                });

                // Company Updates/News
                Route::prefix('updates')->group(function () {
                    Route::get('/', [CompanyUpdateController::class, 'index']);
                    Route::post('/', [CompanyUpdateController::class, 'store']);
                    Route::get('/{id}', [CompanyUpdateController::class, 'show']);
                    Route::put('/{id}', [CompanyUpdateController::class, 'update']);
                    Route::delete('/{id}', [CompanyUpdateController::class, 'destroy']);
                });

                // Company Deal Listings (Share Offerings)
                Route::prefix('deals')->group(function () {
                    Route::get('/', [CompanyDealController::class, 'index']);
                    Route::post('/', [CompanyDealController::class, 'store']);
                    Route::get('/statistics', [CompanyDealController::class, 'statistics']);
                    Route::get('/{id}', [CompanyDealController::class, 'show']);
                    Route::put('/{id}', [CompanyDealController::class, 'update']);
                    Route::delete('/{id}', [CompanyDealController::class, 'destroy']);
                });

                // Analytics Dashboard
                Route::prefix('analytics')->group(function () {
                    Route::get('/dashboard', [CompanyAnalyticsController::class, 'dashboard']);
                    Route::get('/trends', [CompanyAnalyticsController::class, 'trends']);
                    Route::get('/export', [CompanyAnalyticsController::class, 'export']);
                });

                // Investor Interest Management
                Route::prefix('investor-interests')->group(function () {
                    Route::get('/', [InvestorInterestController::class, 'index']);
                    Route::get('/statistics', [InvestorInterestController::class, 'statistics']);
                    Route::get('/{id}', [InvestorInterestController::class, 'show']);
                    Route::put('/{id}/status', [InvestorInterestController::class, 'updateStatus']);
                });

                // Q&A Management
                Route::prefix('qna')->group(function () {
                    Route::get('/', [CompanyQnaController::class, 'index']);
                    Route::get('/statistics', [CompanyQnaController::class, 'statistics']);
                    Route::post('/{id}/answer', [CompanyQnaController::class, 'answer']);
                    Route::put('/{id}', [CompanyQnaController::class, 'update']);
                    Route::delete('/{id}', [CompanyQnaController::class, 'destroy']);
                });

                // Webinar Scheduling & Management
                Route::prefix('webinars')->group(function () {
                    Route::get('/', [CompanyWebinarController::class, 'index']);
                    Route::post('/', [CompanyWebinarController::class, 'store']);
                    Route::get('/statistics', [CompanyWebinarController::class, 'statistics']);
                    Route::get('/{id}', [CompanyWebinarController::class, 'show']);
                    Route::put('/{id}', [CompanyWebinarController::class, 'update']);
                    Route::delete('/{id}', [CompanyWebinarController::class, 'destroy']);
                    Route::get('/{id}/registrations', [CompanyWebinarController::class, 'registrations']);
                    Route::post('/{id}/recording', [CompanyWebinarController::class, 'uploadRecording']);
                });

                // Onboarding Wizard
                Route::prefix('onboarding')->group(function () {
                    Route::get('/progress', [OnboardingWizardController::class, 'getProgress']);
                    Route::post('/complete-step', [OnboardingWizardController::class, 'completeStep']);
                    Route::post('/skip', [OnboardingWizardController::class, 'skipOnboarding']);
                    Route::get('/recommendations', [OnboardingWizardController::class, 'getRecommendations']);
                });
            });
        });
    });
});