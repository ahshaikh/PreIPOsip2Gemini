# PreIPO SIP Platform - Bug Fixes Tracker

**Date**: December 2, 2025
**Total Issues**: 23
**Priority**: High

---

## 🔴 CRITICAL ISSUES (Backend + Database)

### Issue #23: Avatar Upload Error - Missing Column
**Error**: `Column not found: 'avatar_url' in 'field list'`
**Location**: Profile → Personal Info → Choose Photo
**Root Cause**: Database migration missing `avatar_url` column in `user_profiles` table
**Fix Required**:
1. ✅ Add migration for `avatar_url` column to `user_profiles` table
2. ✅ Update ProfileController to handle avatar uploads correctly
3. ✅ Update frontend to display avatar properly

### Issue #24: Bank Details Route Missing
**Error**: `The route api/v1/user/bank-details could not be found`
**Location**: Profile → Bank Details
**Root Cause**: Route not registered in `api.php`
**Fix Required**:
1. ✅ Add bank details routes to `api.php`
2. ✅ Create/update ProfileController methods for bank details
3. ✅ Update frontend to call correct endpoint

### Issue #7: Settings Update Always Failing
**Error**: "Failed to update settings"
**Location**: User Settings page
**Root Cause**: API endpoint not working or validation errors
**Fix Required**:
1. ✅ Check and fix user settings controller
2. ✅ Verify settings table structure
3. ✅ Update frontend error handling

### Issue #22: Support Ticket Creation Error
**Error**: "The selected category is invalid"
**Location**: Support → Create Ticket
**Root Cause**: Category validation mismatch between frontend and backend
**Fix Required**:
1. ✅ Fix category validation in backend
2. ✅ Update frontend with correct category values
3. ✅ Add proper error messages

---

## 🟡 HIGH PRIORITY ISSUES (Backend)

### Issue #16: Subscription Upgrade Error
**Error**: "New plan amount must be lower"
**Location**: My Subscription → Manage → Upgrade
**Root Cause**: Upgrade logic inverted in controller
**Fix Required**: backend/app/Http/Controllers/Api/User/SubscriptionController.php:changePlan()

### Issue #18: Receipt Download 403 Error
**Error**: "Request failed with status code 403"
**Location**: My Subscription → Receipt Downloads
**Root Cause**: Missing permission or wrong endpoint
**Fix Required**: Check invoice/receipt generation and download permissions

### Issue #17: No Unpause Button After Pause
**Location**: My Subscription → Pause/Resume
**Root Cause**: Resume functionality not implemented
**Fix Required**: Add resume endpoint and button in frontend

### Issue #15: Manual Aadhaar Verification Module
**Location**: KYC Verification
**Requirement**: Add manual Aadhaar upload in addition to DigiLocker
**Fix Required**:
1. ✅ Update KYC controller to accept manual uploads
2. ✅ Add manual upload form in frontend
3. ✅ Store uploaded documents properly

---

## 🟢 MEDIUM PRIORITY ISSUES (Frontend)

### Issue #1: Plans Loading on Navbar
**Location**: Top navbar → Plans
**Problem**: Shows "Loading your plan" on public navbar too
**Fix Required**: frontend/components/shared/UserTopNav.tsx - Fix conditional rendering

### Issue #2: Avatar Icon Missing
**Location**: Top navbar → Extreme right corner
**Problem**: No avatar icon displayed
**Fix Required**: Add avatar icon (User/UserCircle icon) from lucide-react

### Issue #3: Bank Details 404 Error
**Location**: Top navbar dropdown → Bank Details
**Problem**: Shows 404 outside user area
**Fix Required**: Fix link to `/user/profile?tab=bank-details` instead of separate page

### Issue #4: Learn Page Leaves User Area
**Location**: Top navbar → More → Learn
**Problem**: Goes to public side instead of staying in user area
**Fix Required**: Change link to user-side learn page or remove from dropdown

### Issue #5: Referral Link Shows Undefined
**Location**: Top navbar → More → Invite Friends
**Problem**: ref=undefined in referral link
**Fix Required**: Use user's actual referral_code from database

### Issue #6: Download Page Not Responding
**Location**: Top navbar → More → Download
**Problem**: Categories don't respond on click
**Fix Required**: Implement actual download functionality

### Issue #8: Offers Dropdown - "Offer Not Found"
**Location**: Top navbar → Offers
**Problem**: Both dropdowns show "offer not found"
**Fix Required**:
1. Create offers pages
2. Connect with admin CRUD backend
3. Display actual offers

### Issue #9: Wallet Balance Hardcoded
**Location**: Top navbar - wallet balance
**Problem**: Shows hardcoded value instead of actual balance
**Fix Required**: Fetch from API: `/api/v1/user/wallet`

### Issue #10: Payment Method Page Too Big
**Location**: Wallet → Add Money → Payment Method Selection
**Problem**: Vertically huge, impossible to see
**Fix Required**: Add scrollable container with max-height and overflow-y-auto

### Issue #11: Wallet Download Icon Not Responding
**Location**: Wallet page → Download icon
**Problem**: No response on click
**Fix Required**: Implement wallet statement download

---

## 🔵 LOW PRIORITY ISSUES (UX Improvements)

### Issue #12: Missing First Name in Welcome
**Location**: Dashboard
**Problem**: "Welcome Back!" without first name
**Fix Required**: Add: "Welcome Back, {firstName}!"

### Issue #13: KYC Verified Banner Too Persistent
**Location**: Dashboard
**Problem**: Banner shows every time, looks like hindrance
**Fix Required**:
1. Show banner only 2 times
2. Add small checkmark icon in left sidebar user box
3. Store "banner_dismissed" in localStorage

### Issue #14: Subscribe to Plan Just Loading
**Location**: Dashboard → Subscribe to Plan
**Problem**: Just shows "loading..." and about to leave user area
**Fix Required**: Fix routing to stay within user area: `/user/subscribe`

### Issue #19: Portfolio Statement Download Not Working
**Location**: My Portfolio → Download Statement
**Problem**: No response
**Fix Required**: Implement PDF generation for portfolio statement

### Issue #20: Bonus Export Not Working
**Location**: My Bonuses → Export History
**Problem**: No response
**Fix Required**: Implement CSV/Excel export for bonus history

### Issue #21: Bonus Search Not Working
**Location**: My Bonuses → Search Box
**Problem**: Not working or no data
**Fix Required**: Implement search/filter functionality

### Issue #25: Download My Data Not Working
**Location**: Profile → Data and Privacy → Download My Data
**Problem**: Not implemented
**Fix Required**: Implement GDPR data export

---

## 📋 IMPLEMENTATION PLAN

### Phase 1: Critical Database & Backend Fixes (Priority 1)
- [ ] 1. Add avatar_url column migration
- [ ] 2. Fix bank details routes and controller
- [ ] 3. Fix settings update endpoint
- [ ] 4. Fix support ticket category validation
- [ ] 5. Fix subscription upgrade/downgrade logic
- [ ] 6. Add subscription resume functionality
- [ ] 7. Fix receipt download permissions
- [ ] 8. Add manual Aadhaar verification

**Estimated Time**: 2-3 hours

### Phase 2: Frontend Navigation & UI Fixes (Priority 2)
- [ ] 9. Fix plans loading on navbar
- [ ] 10. Add avatar icon to top right
- [ ] 11. Fix bank details link
- [ ] 12. Fix learn page routing
- [ ] 13. Fix referral link with actual code
- [ ] 14. Fix download page functionality
- [ ] 15. Fix offers dropdown
- [ ] 16. Connect wallet balance to API
- [ ] 17. Fix payment method page scroll
- [ ] 18. Fix wallet download icon

**Estimated Time**: 2-3 hours

### Phase 3: Dashboard & UX Improvements (Priority 3)
- [ ] 19. Add first name to welcome message
- [ ] 20. Fix KYC banner persistence
- [ ] 21. Fix subscribe to plan routing
- [ ] 22. Implement portfolio statement download
- [ ] 23. Implement bonus export
- [ ] 24. Implement bonus search
- [ ] 25. Implement data export

**Estimated Time**: 2-4 hours

---

## 🛠️ FILES TO MODIFY

### Backend Files
1. `/backend/database/migrations/YYYY_MM_DD_add_avatar_to_user_profiles.php` ✅ CREATE NEW
2. `/backend/app/Http/Controllers/Api/User/ProfileController.php` ✅ UPDATE
3. `/backend/app/Http/Controllers/Api/User/SubscriptionController.php` ✅ UPDATE
4. `/backend/app/Http/Controllers/Api/User/SupportTicketController.php` ✅ UPDATE
5. `/backend/app/Http/Controllers/Api/User/PortfolioController.php` ✅ UPDATE
6. `/backend/app/Http/Controllers/Api/User/BonusController.php` ✅ UPDATE
7. `/backend/app/Http/Controllers/Api/User/KycController.php` ✅ UPDATE
8. `/backend/routes/api.php` ✅ UPDATE

### Frontend Files
1. `/frontend/components/shared/UserTopNav.tsx` ✅ UPDATE
2. `/frontend/app/(user)/dashboard/page.tsx` ✅ UPDATE
3. `/frontend/app/(user)/subscription/page.tsx` ✅ UPDATE
4. `/frontend/app/(user)/Profile/page.tsx` ✅ UPDATE
5. `/frontend/app/(user)/bonuses/page.tsx` ✅ UPDATE
6. `/frontend/app/(user)/portfolio/page.tsx` ✅ UPDATE
7. `/frontend/app/(user)/support/page.tsx` ✅ UPDATE
8. `/frontend/app/(user)/wallet/page.tsx` ✅ UPDATE
9. `/frontend/app/(user)/settings/page.tsx` ✅ UPDATE
10. `/frontend/app/(user)/kyc/page.tsx` ✅ UPDATE

---

## ✅ TESTING CHECKLIST

After implementing fixes, test:
- [ ] Avatar upload and display
- [ ] Bank details CRUD
- [ ] Settings update
- [ ] Support ticket creation
- [ ] Subscription upgrade/downgrade
- [ ] Subscription pause/resume
- [ ] Receipt downloads
- [ ] Manual Aadhaar upload
- [ ] All navbar links and dropdowns
- [ ] Wallet balance display
- [ ] Payment method page scroll
- [ ] Portfolio statement download
- [ ] Bonus export and search
- [ ] Data export functionality

---

## 📝 NOTES

- All fixes should maintain backward compatibility
- Test each fix in local environment before committing
- Update API documentation after backend changes
- Add proper error messages for better UX
- Ensure mobile responsiveness for all fixes

---

**Status**: Ready for Implementation
**Next Step**: Begin Phase 1 - Critical Database & Backend Fixes
