// V-PHASE6-1730-137
# PreIPO SIP - API Reference v1.0

This document lists the key API endpoints for the application.

### Auth (Public)

* `POST /api/v1/register`
    * Registers a new user.
* `POST /api/v1/login`
    * Logs in a user and returns a Sanctum token.
* `POST /api/v1/verify-otp`
    * Verifies email or mobile OTP.

### User (Auth Required)

* `GET /api/v1/user/profile`
    * Gets the logged-in user's profile and KYC status.
* `POST /api/v1/user/kyc`
    * Submits the user's KYC form data and documents.
* `POST /api/v1/user/subscription`
    * Subscribes the user to a specific plan ID.
* `POST /api/v1/user/payment/initiate`
    * Initiates a Razorpay payment for a pending payment.
* `GET /api/v1/user/portfolio`
    * Returns the user's investment holdings and summary.
* `GET /api/v1/user/wallet`
    * Returns wallet balance and transaction history.
* `POST /api/v1/user/wallet/withdraw`
    * Creates a new withdrawal request for admin approval.

### Webhooks (From 3rd Parties)

* `POST /api/v1/webhooks/razorpay`
    * Receives payment status from Razorpay. Triggers the core bonus/allocation logic.

### Admin (Admin Auth Required)

* `GET /api/v1/admin/dashboard`
    * Gets high-level metrics for the admin dashboard.
* `GET /api/v1/admin/kyc-queue`
    * Gets the list of pending KYC submissions.
* `POST /api/v1/admin/kyc-queue/{id}/approve`
    * Approves a KYC submission.
* `POST /api/v1/admin/kyc-queue/{id}/reject`
    * Rejects a KYC submission with a reason.
* `GET /api/v1/admin/withdrawal-queue`
    * Gets the list of pending withdrawals.
* `POST /api/v1/admin/withdrawal-queue/{id}/approve`
    * Approves a withdrawal, moving it to "processing".
* `POST /api/v1/admin/withdrawal-queue/{id}/complete`
    * Completes a withdrawal after bank transfer. Requires a `utr_number`.
* `GET /api/v1/admin/settings`
    * Gets all system settings from the `settings` table.
* `PUT /api/v1/admin/settings`
    * Batch updates system settings (used by the toggles).
* `GET, POST, PUT /api/v1/admin/plans`
    * Full CRUD for managing investment plans.