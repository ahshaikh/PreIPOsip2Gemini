# Subscription Features - Location Guide

This document shows where all subscription management features are located and how to access them.

## 🎯 Quick Navigation

### For Existing Subscribers

1. **Browse Investment Deals**: Visible on `/subscription` page as CTA card OR use sidebar link "Available Deals"
2. **Track Investments**: Visible on `/subscription` page as CTA card OR use sidebar link "My Investments"
3. **Change Plan (Upgrade/Downgrade)**: Click "Manage" button on `/subscription` page
4. **Pause/Resume/Cancel**: Click "Manage" button on `/subscription` page
5. **Alternative Plan Change**: Navigate to `/plan` page from top nav

---

## 📍 Feature Locations

### 1. CTA Cards (Browse Deals & My Investments)

**Location**: `/frontend/app/(user)/subscription/page.tsx:231-268`

**Condition**: Shows for all subscriptions except cancelled
**Code**:
```tsx
{sub.status !== 'cancelled' && (
  <div className="grid md:grid-cols-2 gap-4">
    <Link href="/deals">
      <Card>Browse Available Deals</Card>
    </Link>
    <Link href="/investments">
      <Card>My Investments</Card>
    </Link>
  </div>
)}
```

**How to Access**:
- Go to `/subscription` page
- Look at the top of the page after your subscription details
- Two large clickable cards should be visible (unless subscription is cancelled)

**Troubleshooting**:
- If not visible, check subscription status: `sub.status !== 'cancelled'`
- Hard refresh browser (Ctrl+F5 or Cmd+Shift+R)
- Check browser console for React errors

---

### 2. Upgrade/Downgrade Plan

#### Method 1: Via Manage Button (ManageSubscriptionModal)

**Location**: `/frontend/components/features/ManageSubscriptionModal.tsx:30-38`

**How to Access**:
1. Go to `/subscription` page
2. Click the **"Manage"** button (top right, next to subscription title)
3. A modal will open with tabs
4. Go to **"Change Plan"** tab
5. Select new plan from dropdown
6. Click "Change Plan" button

**Backend Endpoint**: `POST /user/subscription/change-plan`
**Controller**: `/backend/app/Http/Controllers/Api/User/SubscriptionController.php:107-148`

**Logic**:
- If new plan > current plan: **Upgrade** with pro-rated charge
- If new plan < current plan: **Downgrade** effective next cycle

---

#### Method 2: Via Plans Page

**Location**: `/frontend/app/(user)/plan/page.tsx:56-85`

**How to Access**:
1. Click **"Plans"** link in top navigation bar
2. You'll see all 4 plans displayed
3. Your current plan is marked with a badge
4. Other plans show **"Change to [Plan Name]"** button
5. Click the button to change plan

**Code** (frontend/app/(user)/plan/page.tsx:56-67):
```tsx
const changePlanMutation = useMutation({
  mutationFn: (planId: number) => api.post('/user/subscription/change-plan', { new_plan_id: planId }),
  onSuccess: (response) => {
    const message = response.data?.message || "Plan changed successfully";
    toast.success("Plan Changed!", { description: message });
    queryClient.invalidateQueries({ queryKey: ['subscription'] });
    router.push('/subscription');
  }
});
```

---

### 3. Pause Subscription

**Location**: `/frontend/components/features/ManageSubscriptionModal.tsx:40-48`

**How to Access**:
1. Go to `/subscription` page
2. Click **"Manage"** button
3. Go to **"Pause"** tab in the modal
4. Select pause duration (1-3 months)
5. Click "Pause Subscription" button

**Backend Endpoint**: `POST /user/subscription/pause`
**Controller**: `/backend/app/Http/Controllers/Api/User/SubscriptionController.php:150-199`

**What Happens**:
- Subscription status changes to `paused`
- Payments are skipped during pause period
- Automatically resumes after pause duration ends

---

### 4. Resume Subscription

**Location**: `/frontend/components/features/ManageSubscriptionModal.tsx:50-58`

**How to Access**:
1. Go to `/subscription` page
2. If subscription is paused, you'll see a "Resume" button prominently displayed
3. Click **"Resume Subscription"** button

**Alternative**:
1. Click **"Manage"** button
2. If subscription is paused, you'll see a "Resume" button in the modal

**Backend Endpoint**: `POST /user/subscription/resume`
**Controller**: `/backend/app/Http/Controllers/Api/User/SubscriptionController.php:201-226`

---

### 5. Cancel Subscription

**Location**: `/frontend/components/features/ManageSubscriptionModal.tsx:60-68`

**How to Access**:
1. Go to `/subscription` page
2. Click **"Manage"** button
3. Go to **"Cancel"** tab in the modal
4. Enter cancellation reason (optional)
5. Click "Cancel Subscription" button

**Backend Endpoint**: `POST /user/subscription/cancel`
**Controller**: `/backend/app/Http/Controllers/Api/User/SubscriptionController.php:228-269`

**What Happens**:
- Subscription status changes to `cancelled`
- Pro-rata refund calculated if cancelled mid-cycle
- Refund credited to wallet
- User retains access until current period ends

---

### 6. Banner Announcements

**Location**: `/frontend/components/shared/UserTopNav.tsx:313-332`

**API Endpoint**: `GET /announcements/latest`
**Controller**: `/backend/app/Http/Controllers/Api/User/UserDashboardController.php:116-143`

**How It Works**:
- Queries `banners` table for active banner with type='top_bar'
- Filters by `is_active=true` and current date within `start_at` and `end_at`
- Orders by `display_order` DESC, then `created_at` DESC
- Falls back to default message if no banner exists

**Admin Panel**:
- Create banners in admin panel
- Set type to "top_bar"
- Make sure `is_active` is checked
- Set `start_at` and `end_at` dates appropriately

---

## 🔍 Verification Steps

### Test CTA Cards Visibility:

1. Login as a subscriber
2. Navigate to `/subscription`
3. Check subscription status in the card header
4. If status is NOT "cancelled", CTA cards should be visible
5. Look for two large cards: "Browse Available Deals" and "My Investments"

**Expected Behavior**:
- ✅ Shows for: active, paused, pending subscriptions
- ❌ Hidden for: cancelled subscriptions

---

### Test Plan Change (Upgrade):

1. Login with an active subscriber account
2. Method A: Click "Manage" → "Change Plan" tab
3. Method B: Click "Plans" in top nav
4. Select a higher-tier plan
5. Click "Change Plan"
6. **Expected**: Success toast with pro-rata charge message
7. Verify new plan is reflected on `/subscription` page

---

### Test Plan Change (Downgrade):

1. Follow same steps as upgrade
2. Select a lower-tier plan
3. **Expected**: Success toast saying "Changes effective next cycle"
4. Verify plan change is scheduled

---

### Test Pause:

1. Click "Manage" → "Pause" tab
2. Select pause duration (e.g., 2 months)
3. Click "Pause Subscription"
4. **Expected**: Status changes to "paused"
5. Payment schedule should reflect paused months

---

### Test Resume:

1. With a paused subscription
2. Click "Resume Subscription" button (prominently displayed)
3. **Expected**: Status changes back to "active"
4. Next payment date recalculated

---

### Test Cancel:

1. Click "Manage" → "Cancel" tab
2. Enter reason (optional)
3. Click "Cancel Subscription"
4. **Expected**: Success message with refund amount
5. Status changes to "cancelled"
6. Refund credited to wallet

---

## 🐛 Troubleshooting

### CTA Cards Not Visible

**Possible Causes**:
1. Subscription status is "cancelled"
2. `sub` object not loaded (check React DevTools)
3. Frontend cache needs clearing (hard refresh)
4. React hydration error (check browser console)

**Debug**:
```tsx
// Check in browser console
console.log('Subscription:', sub);
console.log('Status:', sub?.status);
console.log('Should show cards:', sub?.status !== 'cancelled');
```

---

### Plan Change Button Does Nothing

**Fixed in**: commit `e8c27f0`

**Previous Issue**: Backend query only looked for `status='active'`
**Fix**: Now includes `'active', 'paused', 'pending'` subscriptions

**Location**: `/backend/app/Http/Controllers/Api/User/SubscriptionController.php:119`

---

### Banner Disappearing

**Fixed in**: commit `e8c27f0`

**Previous Issue**: API returned wrong format
**Fix**: Now queries Banner model from database, returns `{text, link}` format

**Location**: `/backend/app/Http/Controllers/Api/User/UserDashboardController.php:116-143`

---

### Manage Button Not Visible

**Check**: Subscription status must NOT be "cancelled"

**Code** (frontend/app/(user)/subscription/page.tsx:223-227):
```tsx
{sub.status !== 'cancelled' && (
  <Button onClick={() => setIsManageOpen(true)}>
    <Settings className="mr-2 h-4 w-4" /> Manage
  </Button>
)}
```

---

## 📊 Feature Summary Table

| Feature | Location | Access Method | Status Required |
|---------|----------|---------------|-----------------|
| Browse Deals CTA | `/subscription` | Visible as card | Not cancelled |
| My Investments CTA | `/subscription` | Visible as card | Not cancelled |
| Change Plan (Modal) | `/subscription` | Click "Manage" → Change Plan tab | Not cancelled |
| Change Plan (Page) | `/plan` | Top nav → Plans → Click plan button | Not cancelled |
| Pause | `/subscription` | Click "Manage" → Pause tab | Active |
| Resume | `/subscription` | Click "Resume" button or Manage modal | Paused |
| Cancel | `/subscription` | Click "Manage" → Cancel tab | Active/Paused |
| Sidebar Links | All pages | Left sidebar | Always visible |

---

## 🚀 Recent Fixes (Session)

### Commit: `e8c27f0` - CTA cards and plan change fixes

1. **Fixed Plan Change Error**
   - Error: "No query results for model [App\Models\Subscription]"
   - Cause: Query only looked for `status='active'`
   - Fix: Now includes 'active', 'paused', 'pending' statuses
   - File: `SubscriptionController.php:119`

2. **Fixed Banner Not Showing Admin Content**
   - Issue: Hardcoded fallback always displayed
   - Cause: API returned hardcoded data instead of querying database
   - Fix: Now queries `Banner` model with `active()` scope
   - File: `UserDashboardController.php:116-143`

### Commit: `9a7f33a` - Navigation bar fix

3. **Fixed Public Navbar on User Pages**
   - Issue: Public navbar showing on /deals and /plan pages
   - Cause: Root layout didn't exclude these paths
   - Fix: Added /deals and /plan to exclusion regex
   - File: `app/layout.tsx:23`

---

## 📝 Notes

- All subscription mutations invalidate the `['subscription']` query to refresh UI
- Toast notifications provide user feedback for all actions
- Pro-rata calculations handled on backend
- Refunds automatically credited to wallet
- CTA cards use Next.js `<Link>` for client-side navigation
