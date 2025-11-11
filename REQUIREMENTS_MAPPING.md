// V-PHASE6-1730-139
# Requirements-to-Code Mapping Checklist

This table maps the project's key features directly to the primary files responsible for their implementation.

| Feature / Requirement | Frontend (Next.js) | API Route (Laravel) | Backend (Laravel) | Database Tables |
| :--- | :--- | :--- | :--- | :--- |
| **User Registration** | `app/(public)/signup/page.tsx` | `POST /api/v1/register` | `app/Http/Controllers/Api/AuthController.php` | `users`, `wallets` |
| **KYC Submission** | `app/(user)/kyc/page.tsx` | `POST /api/v1/user/kyc` | `app/Http/Controllers/Api/User/KycController.php` | `user_kyc`, `kyc_documents` |
| **Admin Approves KYC** | `app/(admin)/kyc-queue/page.tsx` | `POST /api/v1/admin/kyc/approve/{id}` | `app/Http/Controllers/Api/Admin/KycQueueController.php` | `user_kyc`, `users` |
| **Subscribe to Plan** | `app/(user)/subscription/page.tsx` | `POST /api/v1/user/subscription` | `app/Http/Controllers/Api/User/SubscriptionController.php` | `subscriptions`, `payments` |
| **Make SIP Payment** | `app/(user)/subscription/page.tsx` | `POST /api/v1/user/payment/initiate` | `app/Http/Controllers/Api/User/PaymentController.php` | `payments`, `transactions` |
| **Payment Webhook** | (N/A) | `POST /api/v1/webhooks/razorpay` | `app/Http/Controllers/Api/WebhookController.php` | `payments`, `bonus_transactions` |
| **Bonus Calculation** | (N/A) | (N/A - Triggered by Webhook) | `app/Jobs/ProcessSuccessfulPaymentJob.php` | `bonus_transactions` |
| **Share Allocation** | (N/A) | (N/A - Triggered by Webhook) | `app/Jobs/ProcessSuccessfulPaymentJob.php` | `user_investments`, `bulk_purchases` |
| **Request Withdrawal** | `app/(user)/wallet/page.tsx` | `POST /api/v1/user/wallet/withdraw` | `app/Http/Controllers/Api/User/WalletController.php` | `withdrawals`, `wallets`, `transactions` |
| **Admin Completes Withdrawal** | `app/(admin)/withdrawal-queue/page.tsx` | `POST /api/v1/admin/withdrawal/complete/{id}` | `app/Http/Controllers/Api/Admin/WithdrawalController.php` | `withdrawals`, `transactions` |
| **Admin Configures Plan** | `app/(admin)/settings/plans/page.tsx` | `PUT /api/v1/admin/plans/{id}` | `app/Http/Controllers/Api/Admin/PlanController.php` | `plans`, `plan_configs` |
| **Admin Toggles Site** | `app/(admin)/settings/system/page.tsx` | `PUT /api/v1/admin/settings` | `app/Http/Controllers/Api/Admin/SettingsController.php` | `settings` |