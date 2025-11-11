# V-FINAL-1730-141
<#
.SYNOPSIS
    Scaffolds the ENTIRE project, including all directories AND all 139
    empty files with their correct names and extensions.
.DESCRIPTION
    This script makes your life easier. It creates the full folder
    structure and then creates all the empty files (e.g., `User.php`,
    `KycVerificationModal.tsx`) in the right places.

    Your only job is to open each file, copy the code from our chat,
    paste it, and save.
.EXAMPLE
    # Just run it once in your empty project folder.
    .\scaffold-project-v2.ps1
#>

Write-Host "Starting to build the full project scaffold..." -ForegroundColor Cyan
Write-Host "This will create all 100+ directories AND all 139 empty files."

# Get the directory where this script is running
$RootPath = $PSScriptRoot

# Define every single file path we generated
$filePaths = @(
    # --- Step 6 Files ---
    "push-to-github.ps1",
    "MASTER_README.md",
    "backend/.env.example",
    "frontend/.env.local.example",
    "MASTER_DEPLOYMENT_GUIDE.md",
    "MASTER_API_REFERENCE.md",
    "MASTER_TEST_PLAN.md",
    "REQUIREMENTS_MAPPING.md",
    
    # --- Phase 1 Files ---
    "backend/database/migrations/2014_10_12_000000_create_users_table.php",
    "backend/database/migrations/2025_11_11_000101_create_user_profiles_table.php",
    "backend/database/migrations/2025_11_11_000102_create_user_kyc_table.php",
    "backend/database/migrations/2025_11_11_000103_create_kyc_documents_table.php",
    "backend/database/migrations/2025_11_11_000105_create_activity_logs_table.php",
    "backend/database/migrations/2025_11_11_000106_create_otps_table.php",
    "backend/app/Models/User.php",
    "backend/app/Models/UserProfile.php",
    "backend/app/Models/UserKyc.php",
    "backend/app/Models/KycDocument.php",
    "backend/app/Models/ActivityLog.php",
    "backend/app/Models/Otp.php",
    "backend/routes/api.php", # This file gets updated, but we list it here
    "backend/app/Http/Controllers/Api/AuthController.php",
    "backend/app/Http/Controllers/Api/PasswordResetController.php",
    "backend/app/Http/Controllers/Api/User/ProfileController.php",
    "backend/app/Http/Controllers/Api/User/KycController.php",
    "backend/app/Http/Requests/RegisterRequest.php",
    "backend/app/Http/Requests/LoginRequest.php",
    "backend/app/Http/Requests/KycSubmitRequest.php",
    "backend/app/Jobs/SendOtpJob.php",
    "backend/database/seeders/RolesAndPermissionsSeeder.php",

    # --- Phase 2 Files ---
    "backend/database/migrations/2025_11_11_000200_create_settings_table.php",
    "backend/database/migrations/2025_11_11_000201_create_plans_table.php",
    "backend/database/migrations/2025_11_11_000202_create_plan_configs_table.php",
    "backend/database/migrations/2025_11_11_000203_create_plan_features_table.php",
    "backend/database/migrations/2025_11_11_000204_create_products_table.php",
    "backend/database/migrations/2025_11_11_000205_create_bulk_purchases_table.php",
    "backend/database/migrations/2025_11_11_000206_create_pages_table.php",
    "backend/database/migrations/2025_11_11_000207_create_menus_table.php",
    "backend/database/migrations/2025_11_11_000208_create_menu_items_table.php",
    "backend/database/migrations/2025_11_11_000209_create_email_templates_table.php",
    "backend/database/migrations/2025_11_11_000210_create_sms_templates_table.php",
    "backend/database/migrations/2025_11_11_000211_create_feature_flags_table.php",
    "backend/app/Models/Setting.php",
    "backend/app/Models/Plan.php",
    "backend/app/Models/PlanConfig.php",
    "backend/app/Models/PlanFeature.php",
    "backend/app/Models/Product.php",
    "backend/app/Models/BulkPurchase.php",
    "backend/app/Models/Page.php",
    "backend/app/Models/Menu.php",
    "backend/app/Models/MenuItem.php",
    "backend/app/Models/EmailTemplate.php",
    "backend/app/Models/SmsTemplate.php",
    "backend/app/Models/FeatureFlag.php",
    "backend/app/Helpers/SettingsHelper.php",
    "backend/app/Http/Controllers/Api/Public/PlanController.php",
    "backend/app/Http/Controllers/Api/Public/PageController.php",
    "backend/app/Http/Controllers/Api/Admin/AdminDashboardController.php",
    "backend/app/Http/Controllers/Api/Admin/AdminUserController.php",
    "backend/app/Http/Controllers/Api/Admin/KycQueueController.php",
    "backend/app/Http/Controllers/Api/Admin/PlanController.php",
    "backend/app/Http/Controllers/Api/Admin/ProductController.php",
    "backend/app/Http/Controllers/Api/Admin/BulkPurchaseController.php",
    "backend/app/Http/Controllers/Api/Admin/SettingsController.php",
    "backend/app/Http/Controllers/Api/Admin/PageController.php",
    "backend/app/Http/Controllers/Api/Admin/EmailTemplateController.php",

    # --- Phase 3 Files ---
    "backend/database/migrations/2025_11_11_000300_create_subscriptions_table.php",
    "backend/database/migrations/2025_11_11_000301_create_payments_table.php",
    "backend/database/migrations/2025_11_11_000302_create_user_investments_table.php",
    "backend/database/migrations/2025_11_11_000303_create_wallets_table.php",
    "backend/database/migrations/2025_11_11_000304_create_transactions_table.php",
    "backend/database/migrations/2025_11_11_000305_create_withdrawals_table.php",
    "backend/database/migrations/2025_11_11_000306_create_bonus_transactions_table.php",
    "backend/database/migrations/2025_11_11_000307_create_referrals_table.php",
    "backend/database/migrations/2025_11_11_000308_create_lucky_draws_table.php",
    "backend/database/migrations/2025_11_11_000309_create_lucky_draw_entries_table.php",
    "backend/database/migrations/2025_11_11_000310_create_profit_shares_table.php",
    "backend/database/migrations/2025_11_11_000311_create_user_profit_shares_table.php",
    "backend/app/Models/Subscription.php",
    "backend/app/Models/Payment.php",
    "backend/app/Models/UserInvestment.php",
    "backend/app/Models/Wallet.php",
    "backend/app/Models/Transaction.php",
    "backend/app/Models/Withdrawal.php",
    "backend/app/Models/BonusTransaction.php",
    "backend/app/Models/Referral.php",
    "backend/app/Services/PaymentWebhookService.php",
    "backend/app/Jobs/ProcessSuccessfulPaymentJob.php",
    "backend/app/Services/BonusCalculatorService.php",
    "backend/app/Services/AllocationService.php",
    "backend/app/Jobs/ProcessReferralJob.php",
    "backend/app/Jobs/GenerateLuckyDrawEntryJob.php",
    "backend/app/Http/Controllers/Api/WebhookController.php",
    "backend/app/Http/Controllers/Api/User/SubscriptionController.php",
    "backend/app/Http/Controllers/Api/User/PaymentController.php",
    "backend/app/Http/Controllers/Api/User/PortfolioController.php",
    "backend/app/Http/Controllers/Api/User/BonusController.php",
    "backend/app/Http/Controllers/Api/User/ReferralController.php",
    "backend/app/Http/Controllers/Api/User/WalletController.php",
    "backend/app/Http/Controllers/Api/Admin/WithdrawalController.php",
    "backend/app/Http/Controllers/Api/Admin/LuckyDrawController.php",
    "backend/app/Http/Controllers/Api/Admin/ProfitShareController.php",

    # --- Phase 4 Files ---
    "frontend/lib/api.ts",
    "frontend/lib/hooks.ts",
    "frontend/types.ts",
    "frontend/app/layout.tsx",
    "frontend/components/shared/Providers.tsx",
    "frontend/components/shared/Navbar.tsx",
    "frontend/components/shared/Footer.tsx",
    "frontend/app/(public)/page.tsx",
    "frontend/components/features/HeroSection.tsx",
    "frontend/components/features/ValueProps.tsx",
    "frontend/components/features/HowItWorks.tsx",
    "frontend/components/features/PlanOverview.tsx",
    "frontend/app/(public)/plans/page.tsx",
    "frontend/app/(public)/login/page.tsx",
    "frontend/app/(public)/signup/page.tsx",
    "frontend/app/(public)/verify/page.tsx",

    # --- Phase 5 Files ---
    "frontend/components/shared/DashboardNav.tsx",
    "frontend/app/(user)/layout.tsx",
    "frontend/app/(user)/dashboard/page.tsx",
    "frontend/app/(user)/kyc/page.tsx",
    "frontend/app/(user)/subscription/page.tsx",
    "frontend/app/(user)/portfolio/page.tsx",
    "frontend/app/(user)/bonuses/page.tsx",
    "frontend/app/(user)/referrals/page.tsx",
    "frontend/app/(user)/wallet/page.tsx",

    # --- Phase 6 Files ---
    "frontend/components/shared/AdminNav.tsx",
    "frontend/app/(admin)/layout.tsx",
    "frontend/app/(admin)/dashboard/page.tsx",
    "frontend/app/(admin)/users/page.tsx",
    "frontend/app/(admin)/kyc-queue/page.tsx",
    "frontend/components/admin/KycVerificationModal.tsx",
    "frontend/app/(admin)/withdrawal-queue/page.tsx",
    "frontend/components/admin/WithdrawalProcessModal.tsx",
    "frontend/app/(admin)/settings/plans/page.tsx",
    "frontend/app/(admin)/settings/system/page.tsx",
    
    # --- Placeholders for Git ---
    "backend/storage/app/kyc/.gitkeep",
    "backend/storage/app/public/.gitkeep",
    "backend/storage/framework/cache/.gitkeep",
    "backend/storage/framework/sessions/.gitkeep",
    "backend/storage/framework/views/.gitkeep",
    "backend/storage/logs/.gitkeep"
)

$createdDirCount = 0
$createdFileCount = 0

foreach ($filePath in $filePaths) {
    # Combine the root path with the file's relative path
    $fullPath = Join-Path $RootPath $filePath
    
    # Get the directory part of the file path
    $directory = Split-Path -Path $fullPath -Parent
    
    # Check if the directory exists. If not, create it.
    if (-not (Test-Path -Path $directory)) {
        New-Item -ItemType Directory -Path $directory -Force | Out-Null
        Write-Host "Created directory: $directory" -ForegroundColor Gray
        $createdDirCount++
    }
    
    # Check if the file exists. If not, create it.
    if (-not (Test-Path -Path $fullPath)) {
        New-Item -ItemType File -Path $fullPath -Force | Out-Null
        Write-Host "Created file: $fullPath" -ForegroundColor Green
        $createdFileCount++
    }
}

Write-Host "---"
Write-Host "Scaffolding complete." -ForegroundColor Cyan
Write-Host "Created $createdDirCount new directories."
Write-Host "Created $createdFileCount new empty files."
Write-Host "---"
Write-Host "Your new workflow:"
Write-Host "1. Open the project in your code editor (like VS Code)."
Write-Host "2. Find an empty file (e.g., 'backend/app/Models/User.php')."
Write-Host "3. Copy the code from our chat."
Write-Host "4. Paste it. Save."
Write-Host "5. Repeat. No more creating files or folders!"