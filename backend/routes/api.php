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
use App\Http\Controllers\Api\User\LiveChatController as UserLiveChatController;
use App\Http\Controllers\Api\User\KnowledgeBaseController as UserKnowledgeBaseController;
use App\Http\Controllers\Api\SupportAIController;


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
use App\Http\Controllers\Api\Admin\BlogCategoryController;
use App\Http\Controllers\Api\Admin\PageBlockController;
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
use App\Http\Controllers\Api\Admin\KnowledgeBaseArticleController;
use App\Http\Controllers\Api\Admin\KnowledgeBaseCategoryController;
use App\Http\Controllers\Api\Admin\CannedResponseController;
use App\Http\Controllers\Api\Admin\LiveChatController as AdminLiveChatController;
use App\Http\Controllers\Api\Admin\HelpCenterDashboardController;
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

    // Help Center Public Access
    Route::get('/help-center/menu', [App\Http\Controllers\Api\Public\HelpCenterController::class, 'menu']);
    Route::get('/help-center/articles/{slug}', [App\Http\Controllers\Api\Public\HelpCenterController::class, 'show']);
    Route::post('/help-center/feedback', [App\Http\Controllers\Api\Public\HelpCenterController::class, 'storeFeedback']);
    // V-AUDIT-MODULE15-HIGH: Server-side search endpoint for scalability
    Route::get('/help-center/search', [App\Http\Controllers\Api\Public\HelpCenterController::class, 'search']);

    // --- Dashboard Widgets ---
    Route::get('/announcements/latest', [UserDashboardController::class, 'announcements']);

    // --- Help Center Feedback ---
    Route::post('/help-center/feedback', [App\Http\Controllers\Api\HelpCenterController::class, 'storeFeedback']);

    // --- Offers ---
    Route::get('/offers/active', [App\Http\Controllers\Api\User\OfferController::class, 'index']);
    Route::get('/offers/{id}', [App\Http\Controllers\Api\User\OfferController::class, 'show']);
    Route::post('/offers/validate', [App\Http\Controllers\Api\User\OfferController::class, 'validateCode']);

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

            // Promotional Materials
            Route::get('/promotional-materials', [App\Http\Controllers\Api\User\PromotionalMaterialController::class, 'index']);
            Route::get('/promotional-materials/stats', [App\Http\Controllers\Api\User\PromotionalMaterialController::class, 'stats']);
            Route::post('/promotional-materials/{material}/download', [App\Http\Controllers\Api\User\PromotionalMaterialController::class, 'trackDownload']);

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

            // ============================================
            // AI-POWERED SUPPORT FEATURES
            // ============================================
            Route::prefix('support/ai')->group(function () {
                // Article suggestions based on ticket content
                Route::post('/suggest-articles', [SupportAIController::class, 'suggestArticles']);

                // Auto-classify ticket category
                Route::post('/classify', [SupportAIController::class, 'classifyTicket']);

                // Detect duplicate tickets
                Route::post('/detect-duplicates', [SupportAIController::class, 'detectDuplicates']);

                // Analyze sentiment and urgency
                Route::post('/analyze-sentiment', [SupportAIController::class, 'analyzeSentiment']);

                // Comprehensive analysis (all features)
                Route::post('/analyze', [SupportAIController::class, 'comprehensiveAnalysis']);
            });

            // ============================================
            // LIVE CHAT - User Side
            // ============================================
            Route::prefix('live-chat')->group(function () {
                // Session Management
                Route::post('/availability', [UserLiveChatController::class, 'checkAvailability']);
                Route::get('/sessions', [UserLiveChatController::class, 'sessions']);
                Route::get('/active-session', [UserLiveChatController::class, 'activeSession']);
                Route::post('/sessions', [UserLiveChatController::class, 'startSession']);

                // Messaging
                Route::get('/sessions/{code}/messages', [UserLiveChatController::class, 'messages']);
                Route::post('/sessions/{code}/messages', [UserLiveChatController::class, 'sendMessage']);
                Route::post('/sessions/{code}/typing', [UserLiveChatController::class, 'sendTypingIndicator']);

                // Session Actions
                Route::post('/sessions/{code}/close', [UserLiveChatController::class, 'closeSession']);
                Route::post('/sessions/{code}/rate', [UserLiveChatController::class, 'rateSession']);
                Route::get('/sessions/{code}/transcript', [UserLiveChatController::class, 'downloadTranscript']);
            });

            // ============================================
            // KNOWLEDGE BASE - User Side
            // ============================================
            Route::prefix('knowledge-base')->group(function () {
                // Browse
                Route::get('/categories', [UserKnowledgeBaseController::class, 'categories']);
                Route::get('/categories/{slug}/articles', [UserKnowledgeBaseController::class, 'articlesByCategory']);
                Route::get('/articles/{slug}', [UserKnowledgeBaseController::class, 'article']);

                // Search
                Route::get('/search', [UserKnowledgeBaseController::class, 'search']);
                Route::post('/search-click', [UserKnowledgeBaseController::class, 'trackSearchClick']);

                // Popular & Recent
                Route::get('/popular', [UserKnowledgeBaseController::class, 'popular']);
                Route::get('/recent', [UserKnowledgeBaseController::class, 'recent']);
                Route::get('/featured', [UserKnowledgeBaseController::class, 'featured']);

                // User Feedback
                Route::post('/articles/{slug}/rate', [UserKnowledgeBaseController::class, 'rateArticle']);
                Route::post('/articles/{slug}/helpful', [UserKnowledgeBaseController::class, 'markHelpful']);
            });

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

                // Advanced Reports
                Route::get('revenue', [AdvancedReportController::class, 'revenueReport'])->middleware('permission:reports.view_financial');
                Route::get('bonus-distribution', [AdvancedReportController::class, 'bonusDistributionReport'])->middleware('permission:reports.view_financial');
                Route::get('investment-analysis', [AdvancedReportController::class, 'investmentAnalysisReport'])->middleware('permission:reports.view_financial');
                Route::get('cash-flow', [AdvancedReportController::class, 'cashFlowStatement'])->middleware('permission:reports.view_financial');
                Route::get('transactions', [AdvancedReportController::class, 'transactionReport'])->middleware('permission:reports.view_financial');
                Route::get('kyc-completion', [AdvancedReportController::class, 'kycCompletionReport'])->middleware('permission:reports.view_user');
                Route::get('user-demographics', [AdvancedReportController::class, 'userDemographicsReport'])->middleware('permission:reports.view_user');
                Route::get('subscription-performance', [AdvancedReportController::class, 'subscriptionPerformanceReport'])->middleware('permission:reports.view_financial');
                Route::get('payment-collection', [AdvancedReportController::class, 'paymentCollectionReport'])->middleware('permission:reports.view_financial');
                Route::get('referral-performance', [AdvancedReportController::class, 'referralPerformanceReport'])->middleware('permission:reports.view_user');
                Route::get('portfolio-performance', [AdvancedReportController::class, 'portfolioPerformanceReport'])->middleware('permission:reports.view_financial');
                Route::get('sebi-compliance', [AdvancedReportController::class, 'sebiComplianceReport'])->middleware('permission:reports.view_compliance');

                // Custom Report Builder
                Route::get('custom/metrics', [AdvancedReportController::class, 'getAvailableMetrics'])->middleware('permission:reports.view_financial');
                Route::post('custom/generate', [AdvancedReportController::class, 'generateCustomReport'])->middleware('permission:reports.view_financial');
            });

            // Scheduled Reports Management
            Route::prefix('scheduled-reports')->middleware('permission:reports.manage_scheduled')->group(function () {
                Route::get('/', [AdvancedReportController::class, 'listScheduledReports']);
                Route::post('/', [AdvancedReportController::class, 'createScheduledReport']);
                Route::put('/{report}', [AdvancedReportController::class, 'updateScheduledReport']);
                Route::delete('/{report}', [AdvancedReportController::class, 'deleteScheduledReport']);
                Route::get('/{report}/runs', [AdvancedReportController::class, 'getReportRuns']);
                Route::post('/{report}/run', [AdvancedReportController::class, 'runScheduledReport']);
            });

            // Inventory Summary
            Route::get('inventory/summary', [ReportController::class, 'getInventorySummary'])
                ->middleware('permission:products.view');

            // System Backup Management
            Route::prefix('system/backup')->middleware('permission:system.manage_backups')->group(function () {
                Route::get('/config', [BackupController::class, 'getConfig']);
                Route::put('/config', [BackupController::class, 'updateConfig']);
                Route::get('/history', [BackupController::class, 'getHistory']);
                Route::post('/create', [BackupController::class, 'createBackup']);
                Route::get('/download/{filename}', [BackupController::class, 'downloadBackup']);
                Route::delete('/{filename}', [BackupController::class, 'deleteBackup']);
                Route::get('/db', [BackupController::class, 'downloadDbDump']); // Legacy endpoint
            });

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

            // User Search and Segmentation
            Route::post('/users/advanced-search', [AdminUserController::class, 'advancedSearch'])->middleware('permission:users.view');
            Route::get('/users/segments', [AdminUserController::class, 'segments'])->middleware('permission:users.view');
            Route::get('/users/segment/{segment}', [AdminUserController::class, 'getUsersBySegment'])->middleware('permission:users.view');

            // Critical admin actions - Rate limited
            Route::middleware('throttle:admin-actions')->group(function () {
                Route::post('/users', [AdminUserController::class, 'store'])->middleware('permission:users.create');
                Route::put('/users/{user}', [AdminUserController::class, 'update'])->middleware('permission:users.edit');
                Route::post('/users/bulk-action', [AdminUserController::class, 'bulkAction'])->middleware('permission:users.edit');
                Route::post('/users/import', [AdminUserController::class, 'import'])->middleware('permission:users.create');
                Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->middleware('permission:users.suspend');
                Route::post('/users/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])->middleware('permission:users.suspend');
                Route::post('/users/{user}/block', [AdminUserController::class, 'block'])->middleware('permission:users.suspend');
                Route::post('/users/{user}/unblock', [AdminUserController::class, 'unblock'])->middleware('permission:users.suspend');
                Route::post('/users/{user}/adjust-balance', [AdminUserController::class, 'adjustBalance'])->middleware('permission:users.adjust_wallet');
                Route::post('/users/{user}/override-allocation', [AdminUserController::class, 'overrideAllocation'])->middleware('permission:users.edit');
                Route::post('/users/{user}/force-payment', [AdminUserController::class, 'forcePayment'])->middleware('permission:users.edit');
                Route::post('/users/{user}/send-email', [AdminUserController::class, 'sendEmail'])->middleware('permission:users.edit');
                Route::post('/users/{user}/send-sms', [AdminUserController::class, 'sendSms'])->middleware('permission:users.edit');
                Route::post('/users/{user}/send-notification', [AdminUserController::class, 'sendNotification'])->middleware('permission:users.edit');
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

            // Bulk Purchase Management (V-BULK-PURCHASE-ENHANCEMENT-005)
            Route::apiResource('/bulk-purchases', BulkPurchaseController::class)->middleware('permission:products.edit');
            Route::post('/bulk-purchases/{bulkPurchase}/manual-allocate', [BulkPurchaseController::class, 'manualAllocate'])->middleware('permission:products.edit');
            Route::get('/bulk-purchases/inventory/summary', [BulkPurchaseController::class, 'inventorySummary'])->middleware('permission:products.view');
            Route::get('/bulk-purchases/allocation/trends', [BulkPurchaseController::class, 'allocationTrends'])->middleware('permission:products.view');
            Route::get('/bulk-purchases/allocation/history', [BulkPurchaseController::class, 'allocationHistory'])->middleware('permission:products.view');
            Route::get('/bulk-purchases/allocation/history/export', [BulkPurchaseController::class, 'exportAllocationHistory'])->middleware('permission:products.view');
            Route::get('/bulk-purchases/config/low-stock', [BulkPurchaseController::class, 'getLowStockConfigEndpoint'])->middleware('permission:products.view');
            Route::put('/bulk-purchases/config/low-stock', [BulkPurchaseController::class, 'updateLowStockConfig'])->middleware('permission:products.edit');
            Route::get('/bulk-purchases/reorder/suggestions', [BulkPurchaseController::class, 'reorderSuggestions'])->middleware('permission:products.view');

            // CMS & Settings
            Route::apiResource('/pages', PageController::class)->middleware('permission:settings.manage_cms');
            Route::get('/pages/{page}/analyze', [PageController::class, 'analyze'])->middleware('permission:settings.manage_cms');
            Route::apiResource('/email-templates', EmailTemplateController::class)->middleware('permission:settings.manage_notifications');
            Route::apiResource('/faqs', AdminFaqController::class)->middleware('permission:settings.manage_cms');

            // Blog Management (V-CMS-ENHANCEMENT-007)
            Route::apiResource('/blog-posts', AdminBlogController::class)->middleware('permission:settings.manage_cms');
            Route::apiResource('/blog-categories', BlogCategoryController::class)->middleware('permission:settings.manage_cms');
            Route::get('/blog-categories-active', [BlogCategoryController::class, 'active'])->middleware('permission:settings.manage_cms'); // Lightweight endpoint for dropdowns
            Route::post('/blog-categories/reorder', [BlogCategoryController::class, 'reorder'])->middleware('permission:settings.manage_cms');
            Route::get('/blog-posts/stats/overview', [AdminBlogController::class, 'stats'])->middleware('permission:settings.manage_cms');
            Route::get('/blog-categories/stats/overview', [BlogCategoryController::class, 'stats'])->middleware('permission:settings.manage_cms');

            // Page Blocks - Block-based Page Builder (V-CMS-ENHANCEMENT-012)
            Route::get('/page-blocks/types', [PageBlockController::class, 'getBlockTypes'])->middleware('permission:settings.manage_cms');
            Route::apiResource('/page-blocks', PageBlockController::class)->except(['index', 'store'])->middleware('permission:settings.manage_cms');
            Route::post('/page-blocks/{pageBlock}/duplicate', [PageBlockController::class, 'duplicate'])->middleware('permission:settings.manage_cms');
            Route::post('/page-blocks/{pageBlock}/toggle', [PageBlockController::class, 'toggle'])->middleware('permission:settings.manage_cms');
            Route::get('/page-blocks/{pageBlock}/analytics', [PageBlockController::class, 'analytics'])->middleware('permission:settings.manage_cms');
            Route::get('/pages/{page}/blocks', [PageBlockController::class, 'index'])->middleware('permission:settings.manage_cms');
            Route::post('/pages/{page}/blocks', [PageBlockController::class, 'store'])->middleware('permission:settings.manage_cms');
            Route::post('/pages/{page}/blocks/reorder', [PageBlockController::class, 'reorder'])->middleware('permission:settings.manage_cms');

            // Route::apiResource('/referral-campaigns', AdminReferralCampaignController::class); // DEPRECATED -> Use AdminReferralController group above

            Route::apiResource('/kb-categories', KbCategoryController::class)->middleware('permission:settings.manage_cms');
            Route::apiResource('/kb-articles', KbArticleController::class)->middleware('permission:settings.manage_cms');

            // Help Center Analytics (NEW)
            Route::prefix('help-center-analytics')->group(function () {
                Route::get('/stats', [HelpCenterDashboardController::class, 'stats']);
                Route::get('/feedback', [HelpCenterDashboardController::class, 'feedback']);
                Route::get('/visits', [HelpCenterDashboardController::class, 'visits']);
                Route::get('/needs-attention', [HelpCenterDashboardController::class, 'needsAttention']);
            });

            // ============================================
            // CANNED RESPONSES (Support Quick Replies)
            // ============================================
            Route::prefix('canned-responses')->middleware('permission:settings.manage_cms')->group(function () {
                Route::get('/', [CannedResponseController::class, 'index']);
                Route::post('/', [CannedResponseController::class, 'store']);
                Route::get('/{id}', [CannedResponseController::class, 'show']);
                Route::put('/{id}', [CannedResponseController::class, 'update']);
                Route::delete('/{id}', [CannedResponseController::class, 'destroy']);
                Route::get('/category/{category}', [CannedResponseController::class, 'byCategory']);
            });

            // ============================================
            // LIVE CHAT - Admin Management
            // ============================================
            Route::prefix('live-chat')->middleware('permission:users.view')->group(function () {
                // Session Management
                Route::get('/sessions', [AdminLiveChatController::class, 'index']);
                Route::get('/sessions/{code}', [AdminLiveChatController::class, 'show']);
                Route::get('/waiting', [AdminLiveChatController::class, 'waitingQueue']);

                // Chat Actions
                Route::post('/sessions/{code}/accept', [AdminLiveChatController::class, 'acceptSession']);
                Route::post('/sessions/{code}/messages', [AdminLiveChatController::class, 'sendMessage']);
                Route::post('/sessions/{code}/close', [AdminLiveChatController::class, 'closeSession']);
                Route::post('/sessions/{code}/transfer', [AdminLiveChatController::class, 'transferSession']);

                // Agent Management
                Route::get('/agent/status', [AdminLiveChatController::class, 'getAgentStatus']);
                Route::put('/agent/status', [AdminLiveChatController::class, 'updateAgentStatus']);
                Route::get('/agents', [AdminLiveChatController::class, 'listAgents']);

                // Analytics
                Route::get('/stats', [AdminLiveChatController::class, 'stats']);
                Route::get('/transcripts', [AdminLiveChatController::class, 'transcripts']);
            });

            // ============================================
            // ADVANCED KNOWLEDGE BASE (New Implementation)
            // ============================================
            Route::prefix('knowledge-base')->middleware('permission:settings.manage_cms')->group(function () {
                // Categories
                Route::get('/categories', [KnowledgeBaseCategoryController::class, 'index']);
                Route::get('/categories/tree', [KnowledgeBaseCategoryController::class, 'tree']);
                Route::post('/categories', [KnowledgeBaseCategoryController::class, 'store']);
                Route::get('/categories/{category}', [KnowledgeBaseCategoryController::class, 'show']);
                Route::put('/categories/{category}', [KnowledgeBaseCategoryController::class, 'update']);
                Route::delete('/categories/{category}', [KnowledgeBaseCategoryController::class, 'destroy']);
                Route::post('/categories/reorder', [KnowledgeBaseCategoryController::class, 'reorder']);

                // Articles
                Route::get('/articles', [KnowledgeBaseArticleController::class, 'index']);
                Route::post('/articles', [KnowledgeBaseArticleController::class, 'store']);
                Route::get('/articles/{article}', [KnowledgeBaseArticleController::class, 'show']);
                Route::put('/articles/{article}', [KnowledgeBaseArticleController::class, 'update']);
                Route::delete('/articles/{article}', [KnowledgeBaseArticleController::class, 'destroy']);

                // Article Actions
                Route::post('/articles/{article}/publish', [KnowledgeBaseArticleController::class, 'togglePublish']);
                Route::post('/articles/{article}/feature', [KnowledgeBaseArticleController::class, 'toggleFeature']);
                Route::post('/articles/{article}/duplicate', [KnowledgeBaseArticleController::class, 'duplicate']);

                // Analytics
                Route::get('/articles/{article}/analytics', [KnowledgeBaseArticleController::class, 'analytics']);
                Route::get('/search-analytics', [KnowledgeBaseArticleController::class, 'searchAnalytics']);
            });

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

            // Payment Gateway & Method Configuration
            Route::get('/payment-gateways', [AdminPaymentController::class, 'getGatewaySettings'])->middleware('permission:payments.manage_config');
            Route::put('/payment-gateways', [AdminPaymentController::class, 'updateGatewaySettings'])->middleware('permission:payments.manage_config');
            Route::get('/payment-methods', [AdminPaymentController::class, 'getMethodSettings'])->middleware('permission:payments.manage_config');
            Route::put('/payment-methods', [AdminPaymentController::class, 'updateMethodSettings'])->middleware('permission:payments.manage_config');

            // Auto-Debit Configuration
            Route::get('/auto-debit-config', [AdminPaymentController::class, 'getAutoDebitConfig'])->middleware('permission:payments.manage_config');
            Route::put('/auto-debit-config', [AdminPaymentController::class, 'updateAutoDebitConfig'])->middleware('permission:payments.manage_config');

            // Payment Details & Analytics
            Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->middleware('permission:payments.view');
            Route::get('/payments/failed', [AdminPaymentController::class, 'failedPayments'])->middleware('permission:payments.view');
            Route::get('/payments/analytics', [AdminPaymentController::class, 'analytics'])->middleware('permission:payments.view');
            Route::get('/payments/export', [AdminPaymentController::class, 'export'])->middleware('permission:payments.view');

            // Critical payment actions
            Route::middleware('throttle:admin-actions')->group(function () {
                Route::post('/payments/offline', [AdminPaymentController::class, 'storeOffline'])->middleware('permission:payments.offline_entry');
                Route::post('/payments/{payment}/refund', [AdminPaymentController::class, 'refund'])->middleware('permission:payments.refund');
                Route::post('/payments/{payment}/approve', [AdminPaymentController::class, 'approveManual'])->middleware('permission:payments.approve');
                Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'rejectManual'])->middleware('permission:payments.approve');
                Route::post('/payments/{payment}/retry', [AdminPaymentController::class, 'retryPayment'])->middleware('permission:payments.retry');
            });

            // Withdrawal Settings & Configuration
            Route::get('/withdrawal-settings', [WithdrawalController::class, 'getSettings'])->middleware('permission:withdrawals.manage_config');
            Route::put('/withdrawal-settings', [WithdrawalController::class, 'updateSettings'])->middleware('permission:withdrawals.manage_config');
            Route::get('/withdrawal-fee-tiers', [WithdrawalController::class, 'getFeeTiers'])->middleware('permission:withdrawals.manage_config');
            Route::put('/withdrawal-fee-tiers/{tier}', [WithdrawalController::class, 'updateFeeTier'])->middleware('permission:withdrawals.manage_config');

            // Withdrawal Queue & Analytics
            Route::get('/withdrawal-queue', [WithdrawalController::class, 'index'])->middleware('permission:withdrawals.view_queue');
            Route::get('/withdrawal-queue/{withdrawal}', [WithdrawalController::class, 'show'])->middleware('permission:withdrawals.view_queue');
            Route::get('/withdrawal-analytics', [WithdrawalController::class, 'analytics'])->middleware('permission:withdrawals.view_queue');
            Route::get('/withdrawal-queue/export', [WithdrawalController::class, 'export'])->middleware('permission:withdrawals.view_queue');

            Route::middleware('throttle:admin-actions')->group(function () {
                Route::post('/withdrawal-queue/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->middleware('permission:withdrawals.approve');
                Route::post('/withdrawal-queue/{withdrawal}/complete', [WithdrawalController::class, 'complete'])->middleware('permission:withdrawals.complete');
                Route::post('/withdrawal-queue/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->middleware('permission:withdrawals.reject');
                Route::post('/withdrawal-queue/bulk-approve', [WithdrawalController::class, 'bulkApprove'])->middleware('permission:withdrawals.approve');
                Route::post('/withdrawal-queue/bulk-reject', [WithdrawalController::class, 'bulkReject'])->middleware('permission:withdrawals.reject');
                Route::post('/withdrawal-queue/bulk-complete', [WithdrawalController::class, 'bulkComplete'])->middleware('permission:withdrawals.complete');
            });
            
            // Lucky Draw Management
            Route::apiResource('/lucky-draws', AdminLuckyDrawController::class)->middleware('permission:bonuses.manage_config');
            Route::get('/lucky-draws-settings', [AdminLuckyDrawController::class, 'getSettings'])->middleware('permission:bonuses.manage_config');
            Route::put('/lucky-draws-settings', [AdminLuckyDrawController::class, 'updateSettings'])->middleware('permission:bonuses.manage_config');
            Route::post('/lucky-draws/{id}/execute', [AdminLuckyDrawController::class, 'executeDraw'])->middleware('permission:bonuses.manage_config');
            Route::post('/lucky-draws/{id}/cancel', [AdminLuckyDrawController::class, 'cancel'])->middleware('permission:bonuses.manage_config');
            Route::get('/lucky-draws/{id}/winners', [AdminLuckyDrawController::class, 'getWinners'])->middleware('permission:bonuses.manage_config');
            Route::post('/lucky-draws/{drawId}/winners/{entryId}/disqualify', [AdminLuckyDrawController::class, 'disqualifyWinner'])->middleware('permission:bonuses.manage_config');
            Route::post('/lucky-draws/{id}/upload-video', [AdminLuckyDrawController::class, 'uploadVideo'])->middleware('permission:bonuses.manage_config');
            Route::get('/lucky-draws/{drawId}/winners/{entryId}/certificate', [AdminLuckyDrawController::class, 'generateCertificate'])->middleware('permission:bonuses.manage_config');
            Route::get('/lucky-draws/{id}/analytics', [AdminLuckyDrawController::class, 'getAnalytics'])->middleware('permission:bonuses.manage_config');
            
            Route::apiResource('/profit-sharing', AdminProfitShareController::class)->middleware('permission:bonuses.manage_config');
            Route::get('/profit-sharing-settings', [AdminProfitShareController::class, 'getSettings'])->middleware('permission:bonuses.manage_config');
            Route::put('/profit-sharing-settings', [AdminProfitShareController::class, 'updateSettings'])->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/calculate', [AdminProfitShareController::class, 'calculate'])->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/preview', [AdminProfitShareController::class, 'preview'])->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/distribute', [AdminProfitShareController::class, 'distribute'])->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/adjust', [AdminProfitShareController::class, 'adjust'])->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/reverse', [AdminProfitShareController::class, 'reverse'])->middleware('permission:bonuses.manage_config');
            Route::post('/profit-sharing/{profitShare}/publish-report', [AdminProfitShareController::class, 'publishReport'])->middleware('permission:bonuses.manage_config');
            Route::get('/profit-sharing/{profitShare}/report', [AdminProfitShareController::class, 'getReport'])->middleware('permission:bonuses.manage_config');

            // Bonus Management
            Route::get('/bonuses', [App\Http\Controllers\Api\Admin\AdminBonusController::class, 'index'])->middleware('permission:bonuses.manage_config');
            Route::get('/bonuses/settings', [App\Http\Controllers\Api\Admin\AdminBonusController::class, 'getSettings'])->middleware('permission:bonuses.manage_config');
            Route::put('/bonuses/settings', [App\Http\Controllers\Api\Admin\AdminBonusController::class, 'updateSettings'])->middleware('permission:bonuses.manage_config');
            Route::post('/bonuses/{id}/reverse', [App\Http\Controllers\Api\Admin\AdminBonusController::class, 'reverseBonus'])->middleware('permission:bonuses.manage_config');
            Route::post('/bonuses/calculate-test', [App\Http\Controllers\Api\Admin\AdminBonusController::class, 'calculateTest'])->middleware('permission:bonuses.manage_config');
            Route::post('/bonuses/award-special', [App\Http\Controllers\Api\Admin\AdminBonusController::class, 'awardSpecialBonus'])->middleware('permission:bonuses.manage_config');
            Route::post('/bonuses/award-bulk', [App\Http\Controllers\Api\Admin\AdminBonusController::class, 'awardBulkBonus'])->middleware('permission:bonuses.manage_config');
            Route::post('/bonuses/upload-csv', [App\Http\Controllers\Api\Admin\AdminBonusController::class, 'uploadCsv'])->middleware('permission:bonuses.manage_config');

            // Support
            // V-AUDIT-MODULE14-RECOMMENDATIONS-D: Analytics endpoint must come before resource routes to avoid conflicts
            Route::get('/support-tickets/analytics', [AdminSupportTicketController::class, 'analytics'])->middleware('permission:users.view');

            Route::apiResource('/support-tickets', AdminSupportTicketController::class)->names('admin.support-tickets')->middleware('permission:users.view');
            Route::post('/support-tickets/{supportTicket}/reply', [AdminSupportTicketController::class, 'reply'])->middleware('permission:users.edit');
            Route::put('/support-tickets/{supportTicket}/status', [AdminSupportTicketController::class, 'updateStatus'])->middleware('permission:users.edit');

            // V-AUDIT-MODULE14-RECOMMENDATIONS-C: Export ticket transcript as PDF
            Route::get('/support-tickets/{id}/export-transcript', [AdminSupportTicketController::class, 'exportTranscript'])->middleware('permission:users.view');

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

            // -------------------------------------------------------------
            // ADVANCED ADMIN FEATURES
            // -------------------------------------------------------------

            // Dashboard Customization
            Route::prefix('dashboard')->group(function () {
                Route::get('/widgets', [App\Http\Controllers\Api\Admin\DashboardCustomizationController::class, 'getWidgets']);
                Route::post('/widgets', [App\Http\Controllers\Api\Admin\DashboardCustomizationController::class, 'saveWidgets']);
                Route::post('/reset', [App\Http\Controllers\Api\Admin\DashboardCustomizationController::class, 'resetDashboard']);
                Route::get('/widget-types', [App\Http\Controllers\Api\Admin\DashboardCustomizationController::class, 'getWidgetTypes']);
            });

            // Admin Preferences
            Route::get('/preferences', [App\Http\Controllers\Api\Admin\DashboardCustomizationController::class, 'getPreferences']);
            Route::put('/preferences', [App\Http\Controllers\Api\Admin\DashboardCustomizationController::class, 'updatePreference']);
            Route::post('/preferences/bulk', [App\Http\Controllers\Api\Admin\DashboardCustomizationController::class, 'bulkUpdatePreferences']);

            // System Monitoring
            Route::prefix('system')->group(function () {
                // Health Dashboard
                Route::get('/health', [App\Http\Controllers\Api\Admin\SystemMonitorController::class, 'healthDashboard'])->middleware('permission:system.view_health');

                // Error Tracking
                Route::get('/errors', [App\Http\Controllers\Api\Admin\SystemMonitorController::class, 'getErrors'])->middleware('permission:system.view_health');
                Route::put('/errors/{error}/resolve', [App\Http\Controllers\Api\Admin\SystemMonitorController::class, 'resolveError'])->middleware('permission:system.view_health');

                // Queue Monitor
                Route::get('/queue', [App\Http\Controllers\Api\Admin\SystemMonitorController::class, 'getQueueStats'])->middleware('permission:system.view_health');
                Route::post('/queue/retry/{id}', [App\Http\Controllers\Api\Admin\SystemMonitorController::class, 'retryFailedJob'])->middleware('permission:system.view_health');
                Route::delete('/queue/failed/{id}', [App\Http\Controllers\Api\Admin\SystemMonitorController::class, 'deleteFailedJob'])->middleware('permission:system.view_health');
                Route::post('/queue/flush', [App\Http\Controllers\Api\Admin\SystemMonitorController::class, 'flushFailedJobs'])->middleware('permission:system.view_health');

                // Performance Metrics
                Route::get('/performance', [App\Http\Controllers\Api\Admin\SystemMonitorController::class, 'getPerformanceMetrics'])->middleware('permission:system.view_health');
                Route::post('/performance', [App\Http\Controllers\Api\Admin\SystemMonitorController::class, 'recordMetric'])->middleware('permission:system.view_health');
            });

            // Developer Tools
            Route::prefix('developer')->middleware('permission:system.developer_tools')->group(function () {
                // SQL Query Tool
                Route::post('/sql', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'executeSql']);
                Route::get('/schema', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'getSchema']);

                // API Testing
                Route::get('/api-tests', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'listApiTests']);
                Route::post('/api-tests', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'createApiTest']);
                Route::put('/api-tests/{test}', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'updateApiTest']);
                Route::post('/api-tests/{test}/execute', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'executeApiTest']);
                Route::post('/api-tests/run-all', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'runAllApiTests']);
                Route::delete('/api-tests/{test}', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'deleteApiTest']);

                // Task Scheduler
                Route::get('/system-cron-jobs', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'getSystemCronJobs']);
                Route::get('/tasks', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'listTasks']);
                Route::post('/tasks', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'createTask']);
                Route::put('/tasks/{task}', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'updateTask']);
                Route::post('/tasks/{task}/run', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'runTask']);
                Route::delete('/tasks/{task}', [App\Http\Controllers\Api\Admin\DeveloperToolsController::class, 'deleteTask']);
            });

            // Bulk Operations
            Route::prefix('bulk')->middleware('throttle:admin-actions')->group(function () {
                // Bulk User Updates
                Route::post('/users/update', [App\Http\Controllers\Api\Admin\BulkOperationsController::class, 'bulkUpdateUsers'])->middleware('permission:users.edit');

                // Bulk Imports
                Route::post('/investments/import', [App\Http\Controllers\Api\Admin\BulkOperationsController::class, 'bulkImportInvestments'])->middleware('permission:products.create');
                Route::get('/imports', [App\Http\Controllers\Api\Admin\BulkOperationsController::class, 'getImportHistory'])->middleware('permission:users.view');
            });

            // Data Export Wizard
            Route::prefix('export')->group(function () {
                Route::get('/types', [App\Http\Controllers\Api\Admin\BulkOperationsController::class, 'getExportTypes'])->middleware('permission:users.view');
                Route::post('/', [App\Http\Controllers\Api\Admin\BulkOperationsController::class, 'createExport'])->middleware('permission:users.view');
                Route::get('/history', [App\Http\Controllers\Api\Admin\BulkOperationsController::class, 'getExportHistory'])->middleware('permission:users.view');
                Route::get('/{job}/download', [App\Http\Controllers\Api\Admin\BulkOperationsController::class, 'downloadExport'])->middleware('permission:users.view');
                Route::delete('/{job}', [App\Http\Controllers\Api\Admin\BulkOperationsController::class, 'deleteExport'])->middleware('permission:users.view');
            });

            // Audit Logs & Change History
            Route::prefix('audit-logs')->middleware('permission:system.view_logs')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\Admin\AuditLogController::class, 'index']);
                Route::get('/{log}', [App\Http\Controllers\Api\Admin\AuditLogController::class, 'show']);
                Route::get('/history/{type}/{id}', [App\Http\Controllers\Api\Admin\AuditLogController::class, 'getHistory']);
                Route::get('/timeline', [App\Http\Controllers\Api\Admin\AuditLogController::class, 'getTimeline']);
                Route::get('/stats', [App\Http\Controllers\Api\Admin\AuditLogController::class, 'getStats']);
                Route::get('/{log}/compare', [App\Http\Controllers\Api\Admin\AuditLogController::class, 'compareChanges']);
                Route::get('/export', [App\Http\Controllers\Api\Admin\AuditLogController::class, 'export']);
            });

            // Feature Flags
            Route::prefix('feature-flags')->middleware('permission:system.manage_features')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\Admin\FeatureFlagController::class, 'index']);
                Route::post('/', [App\Http\Controllers\Api\Admin\FeatureFlagController::class, 'store']);
                Route::get('/{flag}', [App\Http\Controllers\Api\Admin\FeatureFlagController::class, 'show']);
                Route::put('/{flag}', [App\Http\Controllers\Api\Admin\FeatureFlagController::class, 'update']);
                Route::delete('/{flag}', [App\Http\Controllers\Api\Admin\FeatureFlagController::class, 'destroy']);
                Route::post('/{flag}/toggle', [App\Http\Controllers\Api\Admin\FeatureFlagController::class, 'toggle']);
                Route::post('/{flag}/rollout', [App\Http\Controllers\Api\Admin\FeatureFlagController::class, 'updateRollout']);
                Route::get('/{flag}/check/{user}', [App\Http\Controllers\Api\Admin\FeatureFlagController::class, 'checkForUser']);
                Route::get('/{flag}/affected-users', [App\Http\Controllers\Api\Admin\FeatureFlagController::class, 'getAffectedUsers']);
                Route::post('/seed', [App\Http\Controllers\Api\Admin\FeatureFlagController::class, 'seedDefaultFlags']);
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

                // Help-Center Articles Management
                Route::get('/articles', [ArticleController::class, 'index']);
                Route::put('/articles/{id}', [ArticleController::class, 'update']);
                Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);
                Route::post('/categories', [CategoryController::class, 'store']);

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