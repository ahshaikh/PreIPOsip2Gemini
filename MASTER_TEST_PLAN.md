// V-PHASE6-1730-138
# Master Test & UAT Plan

This plan covers all testing phases required before launch.

## 1. Unit Tests (Backend - PHPUnit)

These tests validate small, isolated pieces of logic.

* **File:** `backend/tests/Unit/BonusCalculatorTest.php`
    * `test_calculates_10_percent_bonus`: Asserts that a 5000 payment returns a 500 bonus.
    * `test_referral_multiplier_applies_to_bonuses`: (Future) Test that a user with a 2x multiplier gets a 1000 bonus.
    * `test_no_bonus_if_module_disabled`: Mocks the `setting()` helper to return `false` and asserts bonus is 0.

* **File:** `backend/tests/Unit/AllocationServiceTest.php`
    * `test_shares_are_allocated_from_inventory`: Simulates a payment and asserts a `UserInvestment` record is created.
    * `test_bulk_purchase_value_is_decremented`: Asserts that `value_remaining` on the `BulkPurchase` table is reduced.
    * `test_allocation_fails_if_no_inventory`: Asserts that the job fails/queues if no `BulkPurchase` has enough `value_remaining`.

## 2. Integration Tests (Backend - API)

These tests simulate a user's entire journey by calling the API.

* **File:** `backend/tests/Feature/AuthTest.php`
    * `test_user_can_register_and_login`:
        1.  `POST /api/v1/register` with valid data.
        2.  Assert HTTP 201.
        3.  Assert user exists in `users` table.
        4.  Assert `UserProfile` and `UserKyc` (pending) were created.
        5.  `POST /api/v1/login` with same credentials.
        6.  Assert HTTP 200 and a token is returned.

* **File:** `backend/tests/Feature/FullPaymentLifecycleTest.php`
    * `test_payment_triggers_bonus_and_allocation`:
        1.  Create Admin, User (KYC verified), Plan, Product, and BulkPurchase (with 10,000 `value_remaining`).
        2.  (User) `POST /api/v1/user/subscription` to subscribe.
        3.  (User) `POST /api/v1/user/payment/initiate` to get a (mock) order ID.
        4.  (Webhook) `POST /api/v1/webhooks/razorpay` with a mock success payload.
        5.  Assert HTTP 200.
        6.  Assert `payments` table shows `status=paid`.
        7.  Assert `bonus_transactions` table has a new 10% bonus record.
        8.  Assert `user_investments` table has a new allocation.
        9.  Assert `bulk_purchases` table `value_remaining` has decreased.

## 3. User Acceptance Testing (UAT) Plan

These are manual scenarios for business stakeholders.

### Scenario 1: The Admin Configures the Site

* **Actor:** Admin
* [cite_start]**Goal:** Verify the "100% Configurable" engine works [cite: 240-241, 439].

| Step | Action | Expected Result |
| :--- | :--- | :--- |
| 1 | Log in as admin, go to `/admin/settings/system`. | All toggles are visible. |
| 2 | Toggle **"Enable User Registration"** to **OFF**. | The switch stays off. |
| 3 | Click **"Save All Changes"**. | "Settings Saved!" toast appears. |
| 4 | Open a new private browser, go to `/signup`. | The registration form is gone, replaced by a "Registrations are currently closed" message. |
| 5 | Go back to admin, toggle **"Enable User Registration"** to **ON**. Save. | |
| 6 | Refresh the `/signup` page. | The registration form reappears. |
| 7 | Go to `/admin/settings/plans`. Create a new plan "UAT Test Plan" for ₹50. | The plan is created. |
| 8 | Go to the public `/plans` page. | The "UAT Test Plan" is visible. |

### Scenario 2: The New User "Happy Path"

* **Actor:** New User
* **Goal:** Verify a user can sign up, pay, and see their investment.

| Step | Action | Expected Result |
| :--- | :--- | :--- |
| 1 | Go to `/signup`. Register for an account. | Redirected to `/verify`. |
| 2 | (Check logs for OTPs). Enter the email and mobile OTPs. | Account is activated. Redirected to `/login`. |
| 3 | Log in. | Redirected to `/dashboard`. An alert shows "Complete Your Verification". |
| 4 | Go to `/kyc`. Fill out all 5 form fields and upload 5 (test) PDF files. | "KYC Submitted!" toast. Page shows "Status: Submitted". |
| 5 | (Wait for Admin to see `/admin/kyc-queue` and **Approve**). | |
| 6 | Refresh `/dashboard`. | The alert is now green: "KYC Verified!". |
| 7 | Go to `/subscription`. Select a plan and click **"Subscribe"**. | The page updates to show a "Pay Now" button for the first payment. |
| 8 | Click **"Pay Now"**. | Razorpay checkout modal opens. |
| 9 | Complete the (test) payment. | Modal closes. "Payment Successful!" toast. |
| 10 | Go to `/portfolio`. | The "Total Invested" card shows the plan amount. The "Holdings" table shows the allocated shares. |
| 11 | Go to `/bonuses`. | The "Bonus Transactions" table shows a 10% bonus record. |

### Scenario 3: The Withdrawal "Full Cycle"

* **Actor:** Existing User (Tester) & Admin
* **Goal:** Verify the end-to-end withdrawal process.

| Step | Action | Expected Result |
| :--- | :--- | :--- |
| 1 | (Admin) Manually credit the user's wallet with ₹5000 (for testing). | |
| 2 | (User) Log in, go to `/wallet`. | Available Balance shows ₹5000. |
| 3 | (User) Click "Withdraw". Request ₹2000. | "Withdrawal Request Submitted!" toast. Available Balance drops to ₹3000. Locked Balance shows ₹2000. |
| 4 | (Admin) Go to `/admin/withdrawal-queue`. | The ₹2000 request is in the "Pending" list. |
| 5 | (Admin) Click "Review". Click **"Approve"**. | "Withdrawal Approved!" toast. |
| 6 | (Admin) (Simulate bank transfer). Click "Review" again. Enter a fake UTR: `HDFC12345`. | |
| 7 | (Admin) Click **"Mark as Completed"**. | "Withdrawal Completed!" toast. The request disappears from the queue. |
| 8 | (User) Refresh `/wallet`. | Available Balance is still ₹3000. Locked Balance is now ₹0. The transaction history shows "Withdrawal completed. UTR: HDFC12345". |