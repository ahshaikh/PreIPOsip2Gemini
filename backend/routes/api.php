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

// User Controllers
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\KycController;
use App\Http\Controllers\Api\User\SubscriptionController;
use App\Http\Controllers\Api\User\PaymentController;
use App\Http\Controllers\Api\User\PortfolioController;
use App\Http\Controllers\Api\User\BonusController;
use App\Http\Controllers\Api\User\ReferralController;
use App\Http\Controllers\Api\User\WalletController;
use App\Http\Controllers\Api\User\SupportTicketController as UserSupportTicketController;
use App\Http\Controllers\Api\User\LuckyDrawController as UserLuckyDrawController;
use App\Http\Controllers\Api\User\ProfitShareController as UserProfitShareController;
use App\Http\Controllers\Api\User\SecurityController;
use App\Http\Controllers\Api\User\PrivacyController;
use App\Http\Controllers\Api\User\TwoFactorAuthController;
use App\Http\Controllers\Api\User\WithdrawalController as UserWithdrawalController;
use App\Http\Controllers\Api\User\ActivityController;

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
use App\Http\Controllers\Api\Admin\AdvancedReportController;
use App\Http\Controllers\Api\Admin\PerformanceMonitoringController;
use App\Http\Controllers\Api\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Api\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Api\Admin\BlogPostController as AdminBlogController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\CmsController;
use App\Http\Controllers\Api\Admin\ThemeSeoController;
use App\Http\Controllers\Api\Admin\ReferralCampaignController;
use App\Http\Controllers\Api\Admin\SystemHealthController;
use App\Http\Controllers\Api\Admin\AdminActivityController;
use App\Http\Controllers\Api\Admin\BackupController;
use App\Http\Controllers\Api\Admin\IpWhitelistController;
use App\Http\Controllers\Api\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\Admin\KbArticleController;
use App\Http\Controllers\Api\Admin\KbCategoryController;

// Invoice & Webhook
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\NotificationController; // <-- IMPORT

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
                Route::get('/bonuses', [BonusController::class, 'index']);
                Route::get('/referrals', [ReferralController::class, 'index']);
            });

            // Wallet & Withdrawal - Financial operations rate limited
            Route::get('/wallet', [WalletController::class, 'show']);
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
	    Route::post('/support-tickets/{supportTicket}/close', [UserSupportTicketController::class, 'close']); // <-- NEW
            Route::post('/support-tickets/{supportTicket}/rate', [UserSupportTicketController::class, 'rate']); // <-- NEW
            
            // Bonus Modules
            Route::get('/lucky-draws', [UserLuckyDrawController::class, 'index']);
            Route::get('/profit-sharing', [UserProfitShareController::class, 'index']);
            
            // --- UPDATED: Notifications ---
            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']); // <-- NEW
            Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']); // <-- NEW
        });

        // === ADMIN ROUTES ===
        // V-SECURITY-FIX: IP whitelist MUST be checked BEFORE role check
        Route::prefix('admin')->middleware(['admin.ip', 'role:admin|super-admin'])->group(function () {
            
            Route::get('/dashboard', [AdminDashboardController::class, 'index']);

            // Reports & Health - Resource-intensive operations rate limited
            Route::middleware('throttle:reports')->group(function () {
                Route::get('/reports/financial-summary', [ReportController::class, 'getFinancialSummary'])->middleware('permission:reports.view_financial');
                Route::get('/reports/analytics/users', [AdvancedReportController::class, 'getUserAnalytics'])->middleware('permission:reports.view_user');
                Route::get('/reports/analytics/products', [AdvancedReportController::class, 'getProductPerformance'])->middleware('permission:products.view');
                Route::get('/reports/download', [AdvancedReportController::class, 'exportReport'])->middleware('permission:reports.export');
                Route::get('/inventory/summary', [ReportController::class, 'getInventorySummary'])->middleware('permission:products.view');
                Route::get('/system/backup/db', [BackupController::class, 'downloadDbDump'])->middleware('permission:system.manage_backups');
            });

            Route::get('/system/health', [SystemHealthController::class, 'index'])->middleware('permission:system.view_health');
            Route::get('/system/activity-logs', [AdminActivityController::class, 'index'])->middleware('permission:system.view_logs');

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
            Route::apiResource('/referral-campaigns', ReferralCampaignController::class)->middleware('permission:bonuses.manage_campaigns');
            Route::apiResource('/kb-categories', KbCategoryController::class)->middleware('permission:settings.manage_cms');
            Route::apiResource('/kb-articles', KbArticleController::class)->middleware('permission:settings.manage_cms');
            
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

            // Payment Management
            Route::get('/payments', [AdminPaymentController::class, 'index'])->middleware('permission:payments.view');
            Route::get('/payments/{payment}/invoice', [InvoiceController::class, 'download'])->middleware('permission:payments.view');

            // Critical payment actions - Rate limited
            Route::middleware('throttle:admin-actions')->group(function () {
                Route::post('/payments/offline', [AdminPaymentController::class, 'storeOffline'])->middleware('permission:payments.offline_entry');
                Route::post('/payments/{payment}/refund', [AdminPaymentController::class, 'refund'])->middleware('permission:payments.refund');
                Route::post('/payments/{payment}/approve', [AdminPaymentController::class, 'approveManual'])->middleware('permission:payments.approve');
                Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'rejectManual'])->middleware('permission:payments.approve');
            });

            // Withdrawals
            Route::get('/withdrawal-queue', [WithdrawalController::class, 'index'])->middleware('permission:withdrawals.view_queue');

            // Critical withdrawal actions - Rate limited
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
        });
    });
});