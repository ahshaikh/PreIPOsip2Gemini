<?php
// V-REMEDIATE-1730-087 (Created) | V-FINAL-1730-421 (Granular Perms) | V-FINAL-1730-435 (Security Hardened) | V-FINAL-1730-443 (SEC-8 Applied) | V-FINAL-1730-471 (2FA Routes Added)

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
use App\Http\Controllers\Api\Admin\KbArticleController;
use App\Http\Controllers\Api\Admin\KbCategoryController;

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
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\WithdrawalController as UserWithdrawalController;

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
use App\Http\Controllers\Api\Admin\AdvancedReportController; // <-- IMPORT
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
use App\Http\Controllers\Api\Admin\IpWhitelistController; // <-- IMPORT

// Invoice & Webhook
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\NotificationController;


Route::prefix('v1')->group(function () {

    // --- Public Authentication Routes (SEC-1 & SEC-8 Throttled) ---
    Route::middleware('throttle:login')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']); // Step 1
        Route::post('/login/2fa', [AuthController::class, 'verifyTwoFactor']); // Step 2
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLink']);
        Route::post('/password/reset', [PasswordResetController::class, 'reset']);
    });

    // --- NEW: Social Login Routes ---
    Route::get('/auth/google/redirect', [SocialLoginController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [SocialLoginController::class, 'handleGoogleCallback']);
    // ---------------------------------

    // --- Public Data Routes (Default 'api' throttle) ---
    Route::get('/plans', [PublicPlanController::class, 'index']);
    Route::get('/plans/{slug}', [PublicPlanController::class, 'show']);
    Route::get('/page/{slug}', [PublicPageController::class, 'show']);
    Route::get('/public/faqs', [PublicFaqController::class, 'index']);
    Route::get('/public/blog', [PublicBlogController::class, 'publicIndex']);
    Route::get('/public/blog/{slug}', [PublicBlogController::class, 'publicShow']);
    Route::get('/global-settings', [GlobalSettingsController::class, 'index']);
    Route::get('/products/{slug}/history', [ProductDataController::class, 'getPriceHistory']);

    // --- Webhook Routes (No throttle) ---
    Route::post('/webhooks/razorpay', [WebhookController::class, 'handleRazorpay']);

    // --- KYC CALLBACK (Public, as it's from DigiLocker) ---
    Route::get('/kyc/digilocker/callback', [KycController::class, 'handleDigiLockerCallback']);

    // --- Authenticated User Routes ---
    Route::middleware('auth:sanctum')->group(function () {
        
        Route::post('/logout', [AuthController::class, 'logout']);

        // === USER ROUTES ===
        Route::prefix('user')->group(function () {
            // Profile & Security
            Route::get('/profile', [ProfileController::class, 'show']);
            Route::put('/profile', [ProfileController::class, 'update']);
            Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']); // <-- NEW
            Route::post('/security/password', [SecurityController::class, 'updatePassword']);
            Route::get('/security/export-data', [PrivacyController::class, 'export']);
            Route::post('/security/delete-account', [PrivacyController::class, 'deleteAccount']);
            
            // 2FA Management
            Route::get('/2fa/status', [TwoFactorAuthController::class, 'status']);
            Route::post('/2fa/enable', [TwoFactorAuthController::class, 'enable']);
            Route::post('/2fa/confirm', [TwoFactorAuthController::class, 'confirm']);
            Route::post('/2fa/disable', [TwoFactorAuthController::class, 'disable']);
            
            // KYC
            Route::get('/kyc', [KycController::class, 'show']);
            Route::post('/kyc', [KycController::class, 'store']);
            Route::post('/kyc/verify-pan', [KycController::class, 'verifyPan']);
            Route::post('/kyc/verify-bank', [KycController::class, 'verifyBank']);
            Route::get('/kyc-documents/{id}/view', [KycController::class, 'viewDocument']);

	    // --- NEW: DigiLocker Flow ---
            Route::get('/kyc/digilocker/redirect', [KycController::class, 'redirectToDigiLocker']);

            // Subscriptions
            Route::get('/subscription', [SubscriptionController::class, 'show']);
            Route::post('/subscription', [SubscriptionController::class, 'store']);
            Route::post('/subscription/change-plan', [SubscriptionController::class, 'changePlan']);
            Route::post('/subscription/pause', [SubscriptionController::class, 'pause']);
            Route::post('/subscription/resume', [SubscriptionController::class, 'resume']);
            Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
            
            // FSD-LEGAL-004: Users MUST accept 'risk-disclosure' before paying
            Route::post('/payment/initiate', [PaymentController::class, 'initiate'])->middleware('legal.accept:risk-disclosure'); // THE GUARD

            // Payments
            Route::post('/payment/initiate', [PaymentController::class, 'initiate']);
            Route::post('/payment/verify', [PaymentController::class, 'verify']);
            Route::post('/payment/manual', [PaymentController::class, 'submitManual']);
            Route::get('/payments/{payment}/invoice', [InvoiceController::class, 'download']);

            // Portfolio & Bonuses
            Route::get('/portfolio', [PortfolioController::class, 'index']);
            Route::get('/bonuses', [BonusController::class, 'index']);
            Route::get('/referrals', [ReferralController::class, 'index']);
            
            // Wallet
            Route::get('/wallet', [WalletController::class, 'show']);
            Route::post('/wallet/deposit/initiate', [WalletController::class, 'initiateDeposit']);
            Route::post('/wallet/withdraw', [WalletController::class, 'requestWithdrawal']);

            // Test: testWithdrawalRespectsRateLimiting (5 attempts / 1 min)
            Route::post('/wallet/withdraw', [WalletController::class, 'requestWithdrawal'])->middleware('throttle:5,1'); // <-- GAP 2 FIX
            
            // Test: testUserCanViewWithdrawalHistory
            Route::get('/withdrawals', [UserWithdrawalController::class, 'index']);
            
            // Test: testUserCanCancelPendingWithdrawal
            Route::post('/withdrawals/{withdrawal}/cancel', [UserWithdrawalController::class, 'cancel']);
            
            // Support
            Route::apiResource('/support-tickets', UserSupportTicketController::class)->only(['index', 'store', 'show']);
            Route::post('/support-tickets/{supportTicket}/reply', [UserSupportTicketController::class, 'reply']);
            
            // Bonus Modules
            Route::get('/lucky-draws', [UserLuckyDrawController::class, 'index']);
            Route::get('/profit-sharing', [UserProfitShareController::class, 'index']);
            
            // Notifications
            Route::get('/notifications', [NotificationController::class, 'index']);
	    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']); // <-- NEW
            Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        });

        // === ADMIN ROUTES ===
        // 1. Must be authenticated
        // 2. Must have 'admin' or 'super-admin' role
        // 3. Must pass IP Whitelist
        Route::prefix('admin')->middleware(['role:admin|super-admin', 'admin.ip'])->group(function () {
            
            Route::get('/dashboard', [AdminDashboardController::class, 'index']);
            
            // Reports & Health
            Route::get('/reports/financial-summary', [ReportController::class, 'getFinancialSummary'])->middleware('permission:reports.view_financial');
            Route::get('/reports/analytics/users', [AdvancedReportController::class, 'getUserAnalytics'])->middleware('permission:reports.view_user');
            Route::get('/reports/analytics/products', [AdvancedReportController::class, 'getProductPerformance'])->middleware('permission:products.view');
            Route::get('/reports/download', [AdvancedReportController::class, 'exportReport'])->middleware('permission:reports.export');
            Route::get('/system/health', [SystemHealthController::class, 'index'])->middleware('permission:system.view_health');
            Route::get('/system/activity-logs', [AdminActivityController::class, 'index'])->middleware('permission:system.view_logs');
            Route::get('/system/backup/db', [BackupController::class, 'downloadDbDump'])->middleware('permission:system.manage_backups');
            Route::get('/inventory/summary', [ReportController::class, 'getInventorySummary'])->middleware('permission:products.view');
            
            // User Management
            Route::apiResource('/users', AdminUserController::class)->except(['store', 'update']);
            Route::post('/users', [AdminUserController::class, 'store'])->middleware('permission:users.create');
            Route::put('/users/{user}', [AdminUserController::class, 'update'])->middleware('permission:users.edit');
            Route::post('/users/bulk-action', [AdminUserController::class, 'bulkAction'])->middleware('permission:users.edit');
            Route::post('/users/import', [AdminUserController::class, 'import'])->middleware('permission:users.create');
            Route::get('/users/export/csv', [AdminUserController::class, 'export'])->middleware('permission:users.view');
            Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->middleware('permission:users.suspend');
	    Route::post('/users/{user}/adjust-balance', [AdminUserController::class, 'adjustBalance'])->middleware('permission:users.adjust_wallet');
            Route::apiResource('/roles', RoleController::class)->middleware('permission:users.manage_roles');
            
            // KYC Management
            Route::get('/kyc-queue', [KycQueueController::class, 'index'])->middleware('permission:kyc.view_queue');
            Route::get('/kyc-queue/{id}', [KycQueueController::class, 'show'])->middleware('permission:kyc.view_queue');
            Route::post('/kyc-queue/{id}/approve', [KycQueueController::class, 'approve'])->middleware('permission:kyc.approve');
            Route::post('/kyc-queue/{id}/reject', [KycQueueController::class, 'reject'])->middleware('permission:kyc.reject');

            // Business Management
            Route::apiResource('/plans', PlanController::class)->middleware('permission:plans.edit');
            Route::apiResource('/products', ProductController::class)->middleware('permission:products.edit');
            Route::apiResource('/bulk-purchases', BulkPurchaseController::class)->middleware('permission:products.edit');

	    // Knowledge Base Routes
            Route::apiResource('/kb-categories', KbCategoryController::class)->middleware('permission:settings.manage_cms');
            Route::apiResource('/kb-articles', KbArticleController::class)->middleware('permission:settings.manage_cms');
            
            // CMS & Settings
            Route::apiResource('/pages', PageController::class)->middleware('permission:settings.manage_cms');
            Route::apiResource('/email-templates', EmailTemplateController::class)->middleware('permission:settings.manage_notifications');
            Route::apiResource('/faqs', AdminFaqController::class)->middleware('permission:settings.manage_cms');
            Route::apiResource('/blog-posts', AdminBlogController::class)->middleware('permission:settings.manage_cms');
            Route::apiResource('/referral-campaigns', ReferralCampaignController::class)->middleware('permission:bonuses.manage_campaigns');
            Route::get('/pages/{page}/analyze', [PageController::class, 'analyze'])->middleware('permission:settings.manage_cms'); // <-- NEW            
            Route::get('/settings', [SettingsController::class, 'index'])->middleware('permission:settings.view_system');
            Route::put('/settings', [SettingsController::class, 'update'])->middleware('permission:settings.edit_system');
            
            // Marketing CMS
            Route::get('/menus', [CmsController::class, 'getMenus'])->middleware('permission:settings.manage_cms');
            Route::put('/menus/{menu}', [CmsController::class, 'updateMenu'])->middleware('permission:settings.manage_cms');
            Route::get('/banners', [CmsController::class, 'getBanners'])->middleware('permission:settings.manage_cms');
            Route::post('/banners', [CmsController::class, 'storeBanner'])->middleware('permission:settings.manage_cms');
            Route::put('/banners/{banner}', [CmsController::class, 'updateBanner'])->middleware('permission:settings.manage_cms');
            Route::delete('/banners/{banner}', [CmsController::class, 'destroyBanner'])->middleware('permission:settings.manage_cms');
            Route::post('/theme/update', [ThemeSeoController::class, 'updateTheme'])->middleware('permission:settings.manage_theme');
            Route::post('/seo/update', [ThemeSeoController::class, 'updateSeo'])->middleware('permission:settings.manage_theme');

            // Payment Management
            Route::get('/payments', [AdminPaymentController::class, 'index'])->middleware('permission:payments.view');
            Route::post('/payments/offline', [AdminPaymentController::class, 'storeOffline'])->middleware('permission:payments.offline_entry');
            Route::post('/payments/{payment}/refund', [AdminPaymentController::class, 'refund'])->middleware('permission:payments.refund');
            Route::post('/payments/{payment}/approve', [AdminPaymentController::class, 'approveManual'])->middleware('permission:payments.approve');
            Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'rejectManual'])->middleware('permission:payments.approve');
            Route::get('/payments/{payment}/invoice', [InvoiceController::class, 'download'])->middleware('permission:payments.view');

            // Withdrawals
            Route::get('/withdrawal-queue', [WithdrawalController::class, 'index'])->middleware('permission:withdrawals.view_queue');
            Route::post('/withdrawal-queue/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->middleware('permission:withdrawals.approve');
            Route::post('/withdrawal-queue/{withdrawal}/complete', [WithdrawalController::class, 'complete'])->middleware('permission:withdrawals.complete');
            Route::post('/withdrawal-queue/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->middleware('permission:withdrawals.reject');
            
            // Bonus Modules
            Route::apiResource('/lucky-draws', AdminLuckyDrawController::class)->middleware('permission:bonuses.manage_config');
            Route::post('/lucky-draws/{luckyDraw}/execute', [AdminLuckyDrawController::class, 'executeDraw'])->middleware('permission:bonuses.manage_config');

	    // UPDATED: Profit Share 
            Route::apiResource('/profit-sharing', AdminProfitShareController::class)->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/calculate', [AdminProfitShareController::class, 'calculate'])->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/distribute', [AdminProfitShareController::class, 'distribute'])->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/adjust', [AdminProfitShareController::class, 'adjust'])->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/reverse', [AdminProfitShareController::class, 'reverse'])->middleware('permission:bonuses.manage_config');

	    // IP Whitelist Routes 
            Route::apiResource('/ip-whitelist', IpWhitelistController::class)->middleware('permission:system.manage_backups'); // Re-using permission

            // --- NEW: Notification Test Route ---
            Route::post('/notifications/test-sms', [AdminNotificationController::class, 'sendTestSms'])->middleware('permission:settings.manage_notifications');
            Route::apiResource('/profit-sharing', AdminProfitShareController::class)->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/calculate', [AdminProfitShareController::class, 'calculate'])->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/distribute', [AdminProfitShareController::class, 'distribute'])->middleware('permission:bonuses.manage_config');
            
            // Support
            Route::apiResource('/support-tickets', AdminSupportTicketController::class)->middleware('permission:users.view');
            Route::post('/support-tickets/{supportTicket}/reply', [AdminSupportTicketController::class, 'reply'])->middleware('permission:users.edit');
            Route::put('/support-tickets/{supportTicket}/status', [AdminSupportTicketController::class, 'updateStatus'])->middleware('permission:users.edit');
        });
    });
});