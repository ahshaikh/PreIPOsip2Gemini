<?php
// V-PHASE3-1730-087

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth & Public Controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\Public\PlanController as PublicPlanController;
use App\Http\Controllers\Api\Public\PageController as PublicPageController;

// User Controllers
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\KycController;
use App\Http\Controllers\Api\User\SubscriptionController;
use App\Http\Controllers\Api\User\PaymentController;
use App\Http\Controllers\Api\User\PortfolioController;
use App\Http\Controllers\Api\User\BonusController;
use App\Http\Controllers\Api\User\ReferralController;
use App\Http\Controllers\Api\User\WalletController;

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
use App\Http\Controllers\Api\Admin\LuckyDrawController;
use App\Http\Controllers\Api\Admin\ProfitShareController;

// Webhook Controller
use App\Http\Controllers\Api\WebhookController;


Route::prefix('v1')->group(function () {

    // --- Public Authentication Routes ---
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    // ... (other auth routes)

    // --- Public Data Routes ---
    Route::get('/plans', [PublicPlanController::class, 'index']);
    // ... (other public routes)

    // --- Webhook Routes ---
    Route::post('/webhooks/razorpay', [WebhookController::class, 'handleRazorpay']);

    // --- Authenticated User Routes ---
    Route::middleware('auth:sanctum')->group(function () {
        
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('user')->group(function () {
            Route::get('/profile', [ProfileController::class, 'show']);
            Route::put('/profile', [ProfileController::class, 'update']);
            
            Route::get('/kyc', [KycController::class, 'show']);
            Route::post('/kyc', [KycController::class, 'store']);
            
            // --- NEW IN PHASE 3 ---
            Route::get('/subscription', [SubscriptionController::class, 'show']);
            Route::post('/subscription', [SubscriptionController::class, 'store']);
            
            Route::post('/payment/initiate', [PaymentController::class, 'initiate']);
            Route::post('/payment/verify', [PaymentController::class, 'verify']);

            Route::get('/portfolio', [PortfolioController::class, 'index']);
            Route::get('/bonuses', [BonusController::class, 'index']);
            Route::get('/referrals', [ReferralController::class, 'index']);
            
            Route::get('/wallet', [WalletController::class, 'show']);
            Route::post('/wallet/deposit/initiate', [WalletController::class, 'initiateDeposit']);
            Route::post('/wallet/withdraw', [WalletController::class, 'requestWithdrawal']);
            // --- END NEW ---
        });

        // --- Admin Routes ---
        Route::prefix('admin')->middleware(['role:admin|super-admin'])->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index']);
            
            Route::apiResource('/users', AdminUserController::class);
            Route::apiResource('/kyc-queue', KycQueueController::class)->only(['index', 'show']);
            Route::post('/kyc-queue/{id}/approve', [KycQueueController::class, 'approve']);
            Route::post('/kyc-queue/{id}/reject', [KycQueueController::class, 'reject']);

            Route::apiResource('/plans', PlanController::class);
            Route::apiResource('/products', ProductController::class);
            Route::apiResource('/bulk-purchases', BulkPurchaseController::class);
            Route::apiResource('/pages', PageController::class);
            Route::apiResource('/email-templates', EmailTemplateController::class);
            Route::get('/settings', [SettingsController::class, 'index']);
            Route::put('/settings', [SettingsController::class, 'update']);

            // --- NEW IN PHASE 3 ---
            Route::get('/withdrawal-queue', [WithdrawalController::class, 'index']);
            Route::post('/withdrawal-queue/{withdrawal}/approve', [WithdrawalController::class, 'approve']);
            Route::post('/withdrawal-queue/{withdrawal}/complete', [WithdrawalController::class, 'complete']);
            Route::post('/withdrawal-queue/{withdrawal}/reject', [WithdrawalController::class, 'reject']);
            
            Route::apiResource('/lucky-draws', LuckyDrawController::class);
            Route::apiResource('/profit-sharing', ProfitShareController::class);
            // --- END NEW ---
        });
    });
});