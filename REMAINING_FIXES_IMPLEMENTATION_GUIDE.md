# Remaining Fixes - Implementation Guide

## Phase 1: ✅ COMPLETED
- Issue #23: Avatar upload column migration
- Issue #24: Bank details routes and controller
- Issue #16: Subscription upgrade/downgrade logic
- Issue #17: Resume endpoint (already existed)
- Issue #22: Support ticket categories expanded
- Issue #7: Settings routes (backend ready)

---

## Phase 2: Frontend Navigation & UI Fixes (PENDING)

### Issue #1: Plans Loading on Navbar
**File**: `/frontend/components/shared/UserTopNav.tsx`
**Fix**: Add conditional check to only show "Loading your plan" for authenticated users
```tsx
{user && isLoadingPlan && <span>Loading your plan...</span>}
```

### Issue #2: Avatar Icon Missing
**File**: `/frontend/components/shared/UserTopNav.tsx`
**Fix**: Add User icon from lucide-react
```tsx
import { User } from 'lucide-react'
// In dropdown trigger:
<Avatar>
  {user.profile?.avatar_url ? (
    <AvatarImage src={user.profile.avatar_url} />
  ) : (
    <AvatarFallback><User className="h-4 w-4" /></AvatarFallback>
  )}
</Avatar>
```

### Issue #3: Bank Details 404 Error
**File**: `/frontend/components/shared/UserTopNav.tsx` or dropdown menu
**Fix**: Change link from `/bank-details` to `/user/profile?tab=bank-details`
```tsx
<Link href="/user/profile?tab=bank-details">Bank Details</Link>
```

### Issue #4: Learn Page Leaves User Area
**File**: `/frontend/components/shared/UserTopNav.tsx`
**Fix**: Either remove from dropdown or link to `/user/learn` page
```tsx
// Option 1: Remove the link
// Option 2: Create user-side learn page
<Link href="/user/learn">Learn</Link>
```

### Issue #5: Referral Link Shows Undefined
**File**: `/frontend/app/(user)/referrals/page.tsx` or invite component
**Fix**: Use actual referral_code from user object
```tsx
const referralLink = `${window.location.origin}/signup?ref=${user.referral_code}`
```

### Issue #6: Download Page Not Responding
**File**: `/frontend/app/(user)/materials/page.tsx` or downloads page
**Fix**: Implement actual download functionality
```tsx
const handleDownload = async (category) => {
  const response = await api.get(`/api/v1/user/materials/${category}`)
  // Download file logic
}
```

### Issue #8: Offers Dropdown - "Offer Not Found"
**Files**:
- `/frontend/app/(user)/offers/page.tsx`
- `/frontend/app/(user)/offers/[id]/page.tsx`
**Fix**: Connect to backend offers API
```tsx
// Fetch offers from API
const { data: offers } = await api.get('/api/v1/user/offers')
```

### Issue #9: Wallet Balance Hardcoded
**File**: `/frontend/components/shared/UserTopNav.tsx`
**Fix**: Fetch from API
```tsx
const { data: wallet } = useQuery({
  queryKey: ['wallet'],
  queryFn: () => api.get('/api/v1/user/wallet')
})
// Display wallet.data.balance
```

### Issue #10: Payment Method Page Too Big
**File**: `/frontend/app/(user)/wallet/page.tsx` or payment modal
**Fix**: Add scrollable container
```tsx
<div className="max-h-[70vh] overflow-y-auto">
  {/* Payment methods */}
</div>
```

### Issue #11: Wallet Download Icon Not Responding
**File**: `/frontend/app/(user)/wallet/page.tsx`
**Fix**: Implement download functionality
```tsx
const downloadStatement = async () => {
  const response = await api.get('/api/v1/user/wallet/statement', {
    responseType: 'blob'
  })
  // Download PDF logic
}
```

---

## Phase 3: Dashboard & UX Improvements (PENDING)

### Issue #12: Missing First Name in Welcome
**File**: `/frontend/app/(user)/dashboard/page.tsx`
**Fix**: Add first name from user profile
```tsx
<h1>Welcome Back, {user.profile?.first_name}!</h1>
```

### Issue #13: KYC Verified Banner Too Persistent
**File**: `/frontend/app/(user)/dashboard/page.tsx`
**Fix**:
1. Track dismissals in localStorage
2. Show only 2 times
3. Add checkmark icon in sidebar

```tsx
const [bannerDismissed, setBannerDismissed] = useState(() => {
  const dismissed = localStorage.getItem('kyc_banner_dismissed')
  return dismissed ? parseInt(dismissed) >= 2 : false
})

const handleDismiss = () => {
  const count = parseInt(localStorage.getItem('kyc_banner_dismissed') || '0')
  localStorage.setItem('kyc_banner_dismissed', (count + 1).toString())
  setBannerDismissed(count + 1 >= 2)
}

// In sidebar user box:
{user.kyc?.status === 'verified' && (
  <Check className="h-4 w-4 text-green-500" />
)}
```

### Issue #14: Subscribe to Plan Just Loading
**File**: `/frontend/app/(user)/dashboard/page.tsx`
**Fix**: Use proper user-side route
```tsx
<Link href="/user/subscribe">Subscribe to Plan</Link>
```

---

## Phase 4: Additional Features (PENDING)

### Issue #15: Manual Aadhaar Verification
**Files**:
- `/frontend/app/(user)/kyc/page.tsx`
- `/backend/app/Http/Controllers/Api/User/KycController.php` (already supports file uploads)
**Fix**: Add manual upload form alongside DigiLocker
```tsx
<Tabs>
  <TabsList>
    <TabsTrigger value="digilocker">DigiLocker</TabsTrigger>
    <TabsTrigger value="manual">Manual Upload</TabsTrigger>
  </TabsList>
  <TabsContent value="manual">
    <input type="file" accept=".pdf,.jpg,.jpeg,.png" />
    <button onClick={handleManualUpload}>Upload Aadhaar</button>
  </TabsContent>
</Tabs>
```

### Issue #18: Receipt Download 403 Error
**Files**:
- `/frontend/app/(user)/subscription/page.tsx`
- Backend: Check InvoiceController permissions
**Fix**: Ensure proper authorization
```tsx
const downloadReceipt = async (paymentId) => {
  const response = await api.get(`/api/v1/user/payments/${paymentId}/invoice`, {
    responseType: 'blob'
  })
  // Download logic
}
```

### Issue #19: Portfolio Statement Download
**Files**:
- `/frontend/app/(user)/portfolio/page.tsx`
- `/backend/app/Http/Controllers/Api/User/PortfolioController.php`
**Backend**: Add new method
```php
public function downloadStatement(Request $request) {
    $user = $request->user();
    // Generate PDF with portfolio data
    $pdf = PDF::loadView('portfolio.statement', compact('user'));
    return $pdf->download('portfolio-statement.pdf');
}
```
**Frontend**:
```tsx
const downloadStatement = async () => {
  const response = await api.get('/api/v1/user/portfolio/statement', {
    responseType: 'blob'
  })
}
```

### Issue #20: Bonus Export Not Working
**Files**:
- `/frontend/app/(user)/bonuses/page.tsx`
- `/backend/app/Http/Controllers/Api/User/BonusController.php`
**Backend**: Add export method
```php
public function export(Request $request) {
    return Excel::download(new BonusesExport($request->user()), 'bonuses.xlsx');
}
```

### Issue #21: Bonus Search Not Working
**File**: `/frontend/app/(user)/bonuses/page.tsx`
**Fix**: Implement search filter
```tsx
const [searchTerm, setSearchTerm] = useState('')
const filteredBonuses = bonuses.filter(bonus =>
  bonus.type.toLowerCase().includes(searchTerm.toLowerCase()) ||
  bonus.description.toLowerCase().includes(searchTerm.toLowerCase())
)
```

### Issue #25: Download My Data (GDPR)
**File**: `/backend/app/Http/Controllers/Api/User/PrivacyController.php`
**Status**: Route already exists at `/api/v1/user/security/export-data`
**Frontend**: `/frontend/app/(user)/Profile/page.tsx`
```tsx
const downloadMyData = async () => {
  const response = await api.get('/api/v1/user/security/export-data', {
    responseType: 'blob'
  })
  const url = window.URL.createObjectURL(new Blob([response.data]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', 'my-data.zip')
  document.body.appendChild(link)
  link.click()
  link.remove()
}
```

---

## Backend Routes to Add

Add to `/backend/routes/api.php`:

```php
// Portfolio
Route::get('/portfolio/statement', [PortfolioController::class, 'downloadStatement']);

// Bonuses
Route::get('/bonuses/export', [BonusController::class, 'export']);
Route::get('/bonuses/search', [BonusController::class, 'search']);

// Wallet
Route::get('/wallet/statement', [WalletController::class, 'downloadStatement']);

// Materials/Downloads
Route::get('/materials', [MaterialController::class, 'index']);
Route::get('/materials/{category}', [MaterialController::class, 'download']);

// Offers
Route::get('/offers', [OfferController::class, 'index']);
Route::get('/offers/{id}', [OfferController::class, 'show']);
```

---

## Quick Reference: Files by Priority

### HIGH PRIORITY (Phase 2):
1. `/frontend/components/shared/UserTopNav.tsx` - Issues #1, #2, #3, #4, #5, #9
2. `/frontend/app/(user)/wallet/page.tsx` - Issues #10, #11
3. `/frontend/app/(user)/offers/page.tsx` - Issue #8

### MEDIUM PRIORITY (Phase 3):
4. `/frontend/app/(user)/dashboard/page.tsx` - Issues #12, #13, #14
5. `/frontend/app/(user)/Profile/page.tsx` - Issue #25
6. `/frontend/app/(user)/kyc/page.tsx` - Issue #15

### LOW PRIORITY (Phase 4):
7. `/frontend/app/(user)/subscription/page.tsx` - Issue #18
8. `/frontend/app/(user)/portfolio/page.tsx` - Issue #19
9. `/frontend/app/(user)/bonuses/page.tsx` - Issues #20, #21

---

## Testing Checklist After Implementation

- [ ] Avatar upload and display
- [ ] Bank details update
- [ ] Subscription upgrade/downgrade
- [ ] Support ticket creation
- [ ] All navbar links work
- [ ] Wallet balance shows correctly
- [ ] Payment modal scrolls
- [ ] Dashboard welcome with name
- [ ] KYC banner dismisses
- [ ] Subscribe button stays in user area
- [ ] Manual Aadhaar upload works
- [ ] Receipt downloads work
- [ ] Portfolio statement downloads
- [ ] Bonus export works
- [ ] Bonus search works
- [ ] Data export works

---

## Estimated Time Remaining

- **Phase 2** (Navigation/UI): 2-3 hours
- **Phase 3** (Dashboard/UX): 1-2 hours
- **Phase 4** (Additional Features): 3-4 hours
- **Total**: 6-9 hours

---

## Status

✅ Phase 1: COMPLETED (6 issues fixed)
🔄 Phase 2-4: Implementation guide ready
📝 Next: Begin frontend fixes in Phase 2
