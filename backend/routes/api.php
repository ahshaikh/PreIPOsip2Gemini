<?php
// V-FINAL-1730-277

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth & Public Controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\Public\PlanController as PublicPlanController;
use App\Http\Controllers\Api\Public\PageController as PublicPageController;
use App\Http\Controllers\Api\Admin\FaqController as PublicFaqController;
use App\Http\Controllers\Api\Admin\BlogPostController as PublicBlogController;
use App\Http\Controllers\Api\Public\GlobalSettingsController;

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
use App\Http\Controllers\Api\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Api\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Api\Admin\BlogPostController as AdminBlogController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\CmsController;
use App\Http\Controllers\Api\Admin\ThemeSeoController;
use App\Http\Controllers\Api\Admin\ReferralCampaignController; // <-- IMPORTED

// Invoice Controller
use App\Http\Controllers\Api\InvoiceController;

// Webhook Controller
use App\Http\Controllers\Api\WebhookController;


Route::prefix('v1')->group(function () {

    // --- Public Authentication Routes ---
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLink']);
    Route::post('/password/reset', [PasswordResetController::class, 'reset']);

    // --- Public Data Routes ---
    Route::get('/plans', [PublicPlanController::class, 'index']);
    Route::get('/plans/{slug}', [PublicPlanController::class, 'show']);
    Route::get('/page/{slug}', [PublicPageController::class, 'show']);
    Route::get('/public/faqs', [PublicFaqController::class, 'index']);
    Route::get('/public/blog', [PublicBlogController::class, 'publicIndex']);
    Route::get('/public/blog/{slug}', [PublicBlogController::class, 'publicShow']);
    Route::get('/global-settings', [GlobalSettingsController::class, 'index']);
    Route::get('/products/{slug}/history', [\App\Http\Controllers\Api\Public\ProductDataController::class, 'getPriceHistory']);

    // --- Webhook Routes ---
    Route::post('/webhooks/razorpay', [WebhookController::class, 'handleRazorpay']);

    // --- Authenticated User Routes ---
    Route::middleware('auth:sanctum')->group(function () {
        
        Route::post('/logout', [AuthController::class, 'logout']);

        // === USER ROUTES ===
        Route::prefix('user')->group(function () {
            Route::get('/profile', [ProfileController::class, 'show']);
            Route::put('/profile', [ProfileController::class, 'update']);
            Route::post('/security/password', [SecurityController::class, 'updatePassword']);
            Route::get('/security/export-data', [PrivacyController::class, 'export']);
            Route::post('/security/delete-account', [PrivacyController::class, 'deleteAccount']);
            
            Route::get('/kyc', [KycController::class, 'show']);
            Route::post('/kyc', [KycController::class, 'store']);
            Route::post('/kyc/verify-pan', [KycController::class, 'verifyPan']);
            Route::post('/kyc/verify-bank', [KycController::class, 'verifyBank']);
            
            Route::get('/subscription', [SubscriptionController::class, 'show']);
            Route::post('/subscription', [SubscriptionController::class, 'store']);
            Route::post('/subscription/change-plan', [SubscriptionController::class, 'changePlan']);
            Route::post('/subscription/pause', [SubscriptionController::class, 'pause']);
            Route::post('/subscription/resume', [SubscriptionController::class, 'resume']);
            Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
            
            Route::post('/payment/initiate', [PaymentController::class, 'initiate']);
            Route::post('/payment/verify', [PaymentController::class, 'verify']);
            Route::post('/payment/manual', [PaymentController::class, 'submitManual']);
            Route::get('/payments/{payment}/invoice', [InvoiceController::class, 'download']);

            Route::get('/portfolio', [PortfolioController::class, 'index']);
            Route::get('/bonuses', [BonusController::class, 'index']);
            Route::get('/referrals', [ReferralController::class, 'index']);
            
            Route::get('/wallet', [WalletController::class, 'show']);
            Route::post('/wallet/deposit/initiate', [WalletController::class, 'initiateDeposit']);
            Route::post('/wallet/withdraw', [WalletController::class, 'requestWithdrawal']);
            
            Route::apiResource('/support-tickets', UserSupportTicketController::class)->only(['index', 'store', 'show']);
            Route::post('/support-tickets/{supportTicket}/reply', [UserSupportTicketController::class, 'reply']);
            
            Route::get('/lucky-draws', [UserLuckyDrawController::class, 'index']);
            Route::get('/profit-sharing', [UserProfitShareController::class, 'index']);
            
            Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
            Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        });

        // === ADMIN ROUTES ===
        Route::prefix('admin')->middleware(['role:admin|super-admin'])->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index']);
            
            // Reports & Health
            Route::get('/reports/financial-summary', [ReportController::class, 'getFinancialSummary']);
            Route::get('/reports/analytics/users', [App\Http\Controllers\Api\Admin\AdvancedReportController::class, 'getUserAnalytics']);
            Route::get('/reports/analytics/products', [App\Http\Controllers\Api\Admin\AdvancedReportController::class, 'getProductPerformance']);
            Route::get('/reports/download', [App\Http\Controllers\Api\Admin\AdvancedReportController::class, 'exportReport']);
            Route::get('/system/health', [App\Http\Controllers\Api\Admin\SystemHealthController::class, 'index']);
            Route::get('/system/activity-logs', [App\Http\Controllers\Api\Admin\AdminActivityController::class, 'index']);
            Route::get('/system/backup/db', [App\Http\Controllers\Api\Admin\BackupController::class, 'downloadDbDump']);
            
            // User Management
            Route::apiResource('/users', AdminUserController::class);
            Route::post('/users/bulk-action', [AdminUserController::class, 'bulkAction']);
            Route::post('/users/import', [AdminUserController::class, 'import']);
            Route::get('/users/export/csv', [AdminUserController::class, 'export']);
            Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend']);
            Route::post('/users/{user}/adjust-balance', [AdminUserController::class, 'adjustBalance']);
            Route::apiResource('/roles', RoleController::class);
            
            // KYC Management
            Route::apiResource('/kyc-queue', KycQueueController::class)->only(['index', 'show']);
            Route::post('/kyc-queue/{id}/approve', [KycQueueController::class, 'approve']);
            Route::post('/kyc-queue/{id}/reject', [KycQueueController::class, 'reject']);

            // Business Management
            Route::apiResource('/plans', PlanController::class);
            Route::apiResource('/products', ProductController::class);
            Route::apiResource('/bulk-purchases', BulkPurchaseController::class);
            
            // CMS & Settings
            Route::apiResource('/pages', PageController::class);
            Route::apiResource('/email-templates', EmailTemplateController::class);
            Route::apiResource('/faqs', AdminFaqController::class);
            Route::apiResource('/blog-posts', AdminBlogController::class);
            Route::apiResource('/referral-campaigns', ReferralCampaignController::class); // <-- NEW ROUTE
            
            Route::get('/settings', [SettingsController::class, 'index']);
            Route::put('/settings', [SettingsController::class, 'update']);
            
            // Marketing CMS
            Route::get('/menus', [CmsController::class, 'getMenus']);
            Route::put('/menus/{menu}', [CmsController::class, 'updateMenu']);
            Route::get('/banners', [CmsController::class, 'getBanners']);
            Route::post('/banners', [CmsController::class, 'storeBanner']);
            Route::put('/banners/{banner}', [CmsController::class, 'updateBanner']);
            Route::delete('/banners/{banner}', [CmsController::class, 'destroyBanner']);
            Route::post('/theme/update', [ThemeSeoController::class, 'updateTheme']);
            Route::post('/seo/update', [ThemeSeoController::class, 'updateSeo']);

            // Payment Management
            Route::get('/payments', [AdminPaymentController::class, 'index']);
            Route::post('/payments/offline', [AdminPaymentController::class, 'storeOffline']);
            Route::post('/payments/{payment}/refund', [AdminPaymentController::class, 'refund']);
            Route::post('/payments/{payment}/approve', [AdminPaymentController::class, 'approveManual']);
            Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'rejectManual']);
            Route::get('/payments/{payment}/invoice', [InvoiceController::class, 'download']);

            // Withdrawals
            Route::get('/withdrawal-queue', [WithdrawalController::class, 'index']);
            Route::post('/withdrawal-queue/{withdrawal}/approve', [WithdrawalController::class, 'approve']);
            Route::post('/withdrawal-queue/{withdrawal}/complete', [WithdrawalController::class, 'complete']);
            Route::post('/withdrawal-queue/{withdrawal}/reject', [WithdrawalController::class, 'reject']);
            
            // Bonus Modules
            Route::apiResource('/lucky-draws', AdminLuckyDrawController::class)->only(['index', 'store', 'show']);
            Route::post('/lucky-draws/{luckyDraw}/execute', [AdminLuckyDrawController::class, 'executeDraw']);
            
            Route::apiResource('/profit-sharing', AdminProfitShareController::class)->only(['index', 'store', 'show']);
            Route::post('/profit-sharing/{profitShare}/calculate', [AdminProfitShareController::class, 'calculate']);
            Route::post('/profit-sharing/{profitShare}/distribute', [AdminProfitShareController::class, 'distribute']);
            
            // Support
            Route::apiResource('/support-tickets', AdminSupportTicketController::class)->only(['index', 'show']);
            Route::post('/support-tickets/{supportTicket}/reply', [AdminSupportTicketController::class, 'reply']);
            Route::put('/support-tickets/{supportTicket}/status', [AdminSupportTicketController::class, 'updateStatus']);
        });
    });
});