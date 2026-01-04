# Domain Layer Architecture - Implementation Guide

## Overview

This document describes the **Domain-Level Compliance and Aggregation Layer** implemented for the PreIPOsip backend. This architecture provides ONE authoritative place to understand user state, centralizes cross-domain rules, and prevents illegal state combinations.

---

## What Was Implemented

### 1. Compliance State System (Task 1)

**Created Files:**
- `app/Enums/KycComplianceState.php` - KYC compliance states (derived from UserKyc.status)
- `app/Enums/WalletComplianceState.php` - Wallet compliance states (active/inactive)
- `app/Enums/SubscriptionComplianceState.php` - Subscription compliance states (derived from Subscription.status)
- `app/ValueObjects/ComplianceSnapshot.php` - Immutable snapshot of user's compliance across all domains
- `app/Services/Compliance/UserComplianceResolver.php` - Derives compliance state from User model

**Key Principles:**
- Compliance states are **DERIVED**, never persisted
- Handles null relationships safely
- Single source of truth for cross-domain compliance rules
- Explicit states only (no booleans)

**Example Usage:**
```php
use App\Services\Compliance\UserComplianceResolver;

$user = User::find($userId);
$compliance = UserComplianceResolver::from($user);

// Check if user can deposit
if ($compliance->canDeposit()) {
    // Proceed with deposit
}

// Get blockers if action is denied
$blockers = $compliance->getBlockers();
// Returns: ["KYC is Under Review", "User is blacklisted"]
```

---

### 2. User Aggregate Service (Task 2)

**Created Files:**
- `app/Exceptions/Domain/DomainException.php` - Base domain exception
- `app/Exceptions/Domain/IneligibleActionException.php` - Thrown when user cannot perform action
- `app/Exceptions/Domain/SubscriptionNotFoundException.php` - Thrown when subscription not found
- `app/ValueObjects/UserAggregate.php` - Immutable read model containing all user data
- `app/Contracts/UserAggregateServiceInterface.php` - Service contract
- `app/Services/UserAggregateServiceImpl.php` - Service implementation

**Modified Files:**
- `app/Providers/AppServiceProvider.php` - Added service binding

**Key Responsibilities:**
1. Hydrate all user-related data in one place
2. Attach ComplianceSnapshot to UserAggregate
3. Enforce eligibility checks via `assertCan()`
4. Delegate to existing services (SubscriptionService, WalletService)
5. Wrap state changes in transactions
6. Log operations for audit trails

**Example Usage:**
```php
use App\Contracts\UserAggregateServiceInterface;

public function __construct(
    private UserAggregateServiceInterface $userAggregateService
) {}

public function someMethod(Request $request)
{
    $user = $request->user();

    // Load complete user aggregate
    $aggregate = $this->userAggregateService->load($user->id);

    // Check compliance
    if (!$aggregate->isKycApproved()) {
        return response()->json(['message' => 'KYC not approved'], 403);
    }

    // Perform domain operation with automatic eligibility checks
    try {
        $updatedAggregate = $this->userAggregateService->pauseSubscription(
            $user->id,
            2 // months
        );

        return response()->json([
            'message' => 'Subscription paused',
            'subscription' => $updatedAggregate->subscription
        ]);

    } catch (IneligibleActionException $e) {
        return response()->json([
            'message' => $e->getMessage(),
            'context' => $e->getContext()
        ], 403);
    }
}
```

---

### 3. Refactored Controller (Task 3)

**Modified Files:**
- `app/Http/Controllers/Api/User/SubscriptionController.php` - Refactored 3 methods

**Refactored Methods:**
1. **changePlan()** - No longer checks subscription status directly
2. **pause()** - No longer validates subscription state manually
3. **cancel()** - No longer queries with `whereIn(['active','paused','pending'])`

**What Changed:**
- Removed all direct status checks (`whereIn(['active','paused'])`)
- Removed manual eligibility validation
- Controllers now use `UserAggregateService` exclusively
- Domain exceptions converted to HTTP responses
- Controllers don't know what "pending" means anymore

**What Stayed the Same:**
- Request validation still in controller
- Response formatting still in controller
- Existing SubscriptionService methods still used (via UserAggregateService)
- No API contract changes

---

## What Was Intentionally NOT Changed

### 1. Database Schema
- **NO tables added**
- **NO columns added**
- **NO existing migrations modified**
- All existing relationships preserved

### 2. Existing Services
- SubscriptionService **NOT modified** (used as-is)
- WalletService **NOT modified**
- BonusCalculatorService **NOT modified**
- PaymentInitiationService **NOT modified**

### 3. API Contracts
- All endpoints still accept same request parameters
- All endpoints still return similar response structures
- No breaking changes for frontend

### 4. Business Logic
- No changes to bonus calculation rules
- No changes to payment processing
- No changes to refund logic
- No changes to pro-rata calculations

### 5. Other Controllers
- Only SubscriptionController refactored as proof-of-concept
- All other controllers unchanged (waiting for migration)

---

## Why This Approach Avoids Rewrites

This architecture is **conservative by design**:

1. **Layered on Top**: Domain layer sits ABOVE existing code, doesn't replace it
2. **Non-Destructive**: Zero database changes, zero data migrations
3. **Incremental Adoption**: Can migrate controllers one at a time
4. **Backward Compatible**: Existing code continues to work
5. **Testable in Isolation**: New layer can be tested independently
6. **Reversible**: Can remove domain layer without breaking existing functionality

The domain layer acts as a **compliance gate** that existing services flow through, rather than replacing them entirely.

---

## How Future Controllers Must Integrate

### Migration Pattern

**BEFORE (Old Pattern):**
```php
public function someAction(Request $request)
{
    $user = $request->user();

    // ❌ Direct status checks scattered
    $subscription = Subscription::where('user_id', $user->id)
        ->whereIn('status', ['active', 'paused'])
        ->first();

    if (!$subscription) {
        return response()->json(['message' => 'No subscription'], 404);
    }

    // ❌ Manual eligibility checks
    if ($subscription->status === 'pending') {
        return response()->json(['message' => 'Complete payment first'], 400);
    }

    // ❌ Direct service calls
    $this->subscriptionService->doSomething($subscription);
}
```

**AFTER (New Pattern):**
```php
use App\Contracts\UserAggregateServiceInterface;
use App\Exceptions\Domain\IneligibleActionException;
use App\Exceptions\Domain\SubscriptionNotFoundException;

public function __construct(
    private UserAggregateServiceInterface $userAggregateService
) {}

public function someAction(Request $request)
{
    $user = $request->user();

    try {
        // ✅ Single call with automatic compliance checks
        $updatedAggregate = $this->userAggregateService->someAction($user->id);

        return response()->json([
            'message' => 'Success',
            'subscription' => $updatedAggregate->subscription
        ]);

    } catch (IneligibleActionException $e) {
        // ✅ Convert domain exception to HTTP response
        return response()->json([
            'message' => $e->getMessage(),
            'error_code' => $e->getErrorCode(),
            'context' => $e->getContext()
        ], 403);

    } catch (SubscriptionNotFoundException $e) {
        return response()->json([
            'message' => $e->getMessage(),
            'error_code' => $e->getErrorCode()
        ], 404);
    }
}
```

### Integration Checklist

When migrating a controller to use the domain layer:

- [ ] Inject `UserAggregateServiceInterface` in constructor
- [ ] Remove direct Eloquent queries for user relationships
- [ ] Remove manual status checks (`whereIn(['active','paused'])`)
- [ ] Replace service calls with UserAggregateService methods
- [ ] Catch and convert domain exceptions to HTTP responses
- [ ] Keep request validation in controller
- [ ] Keep response formatting in controller
- [ ] Test that API contract remains unchanged

---

## What Patterns Developers Must NOT Reintroduce

### ❌ Anti-Pattern 1: Direct Status Checks
```php
// ❌ NEVER DO THIS ANYMORE
if ($subscription->status === 'pending') {
    return response()->json(['error' => 'Not allowed'], 400);
}
```

**Why:** Controllers should not know about subscription states. Use UserAggregateService.

---

### ❌ Anti-Pattern 2: Direct Relationship Access
```php
// ❌ NEVER DO THIS ANYMORE
$user = $request->user();
if ($user->kyc->status !== 'verified') {
    return response()->json(['error' => 'KYC not verified'], 403);
}
```

**Why:** This creates N+1 queries and bypasses compliance layer. Use ComplianceSnapshot.

---

### ❌ Anti-Pattern 3: Scattered Eligibility Logic
```php
// ❌ NEVER DO THIS ANYMORE
if ($user->is_blacklisted || $user->status !== 'active' || !$user->kyc) {
    return response()->json(['error' => 'Not eligible'], 403);
}
```

**Why:** Eligibility rules are centralized in ComplianceSnapshot. Use `assertCan()`.

---

### ❌ Anti-Pattern 4: Magic String Statuses
```php
// ❌ NEVER DO THIS ANYMORE
$subscriptions = Subscription::whereIn('status', ['active', 'paused', 'pending'])->get();
```

**Why:** Domain layer uses enums. Use `SubscriptionComplianceState`.

---

### ❌ Anti-Pattern 5: Bypassing Aggregate Service
```php
// ❌ NEVER DO THIS ANYMORE
$this->subscriptionService->pauseSubscription($subscription, 2);
```

**Why:** Bypasses compliance checks. Use `UserAggregateService->pauseSubscription()`.

---

## Integration Roadmap

### Phase 1: Critical Controllers (Priority)
1. ✅ SubscriptionController (DONE - proof of concept)
2. ⏳ WalletController - Deposit/Withdraw operations
3. ⏳ KycController - Document submission
4. ⏳ InvestmentController - Product allocation

### Phase 2: Secondary Controllers
5. ⏳ PaymentController - Payment processing
6. ⏳ BonusController - Bonus awards
7. ⏳ ReferralController - Referral operations
8. ⏳ WithdrawalController - Withdrawal requests

### Phase 3: Admin Controllers
9. ⏳ Admin/UserController - User management
10. ⏳ Admin/KycController - KYC verification
11. ⏳ Admin/SubscriptionController - Subscription management

### Migration Strategy
- Migrate one controller at a time
- Run full test suite after each migration
- Monitor production logs for domain exceptions
- Keep rollback plan (revert controller changes if needed)

---

## Testing Strategy

### Unit Tests for Domain Layer
```php
use App\Services\Compliance\UserComplianceResolver;
use App\Models\User;

it('derives correct compliance state for unverified KYC', function () {
    $user = User::factory()->create();

    $compliance = UserComplianceResolver::from($user);

    expect($compliance->kycState)->toBe(KycComplianceState::UNVERIFIED);
    expect($compliance->canDeposit())->toBeFalse();
});

it('blocks subscription change for blacklisted user', function () {
    $user = User::factory()->create(['is_blacklisted' => true]);

    $compliance = UserComplianceResolver::from($user);

    expect($compliance->canChangeSubscriptionPlan())->toBeFalse();
    expect($compliance->getBlockers())->toContain('User is blacklisted');
});
```

### Integration Tests for Controllers
```php
use App\Models\User;
use App\Models\Subscription;

it('prevents plan change for pending subscription', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => 'pending'
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/user/subscription/change-plan', [
            'new_plan_id' => 2
        ]);

    $response->assertStatus(403);
    $response->assertJson([
        'error_code' => 'INELIGIBLE_ACTION'
    ]);
});
```

---

## Performance Considerations

### 1. N+1 Query Prevention
UserAggregateService uses eager loading:
```php
$user = User::with([
    'profile',
    'kyc',
    'wallet',
    'subscription',
])->findOrFail($userId);
```

### 2. Caching Strategy (Future Enhancement)
```php
// Cache compliance snapshot for 5 minutes
$compliance = Cache::remember(
    "user_compliance:{$userId}",
    300,
    fn() => UserComplianceResolver::from($user)
);
```

### 3. Lazy Loading Collections
UserAggregate limits collections to last 10 records:
```php
$recentPayments = $user->payments()
    ->latest()
    ->limit(10)
    ->get();
```

---

## Debugging Guide

### Enable Domain Layer Logging
Add to `config/logging.php`:
```php
'channels' => [
    'domain' => [
        'driver' => 'single',
        'path' => storage_path('logs/domain.log'),
        'level' => 'debug',
    ],
],
```

### Log Compliance Checks
```php
Log::channel('domain')->debug('Compliance check', [
    'user_id' => $userId,
    'action' => $action,
    'compliance' => $compliance->toArray(),
    'allowed' => $canPerform,
]);
```

### Monitor Domain Exceptions
```php
// In Handler.php
public function report(Throwable $exception)
{
    if ($exception instanceof IneligibleActionException) {
        Log::warning('User ineligible for action', [
            'action' => $exception->getAttemptedAction(),
            'context' => $exception->getContext(),
        ]);
    }

    parent::report($exception);
}
```

---

## Common Questions

### Q: Can I still use SubscriptionService directly?
**A:** No. All subscription operations should go through UserAggregateService to ensure compliance checks are enforced.

### Q: What if I need a custom action not in UserAggregateService?
**A:** Add the method to the interface and implementation. Follow the same pattern:
1. Check eligibility with `assertCan()`
2. Delegate to existing services
3. Wrap in DB::transaction()
4. Return updated UserAggregate

### Q: Can I persist ComplianceSnapshot to the database?
**A:** No. Compliance state is derived data and should never be persisted. It can change based on relationships.

### Q: How do I add a new compliance rule?
**A:** Add the rule to ComplianceSnapshot methods (e.g., `canDeposit()`). All controllers using UserAggregateService will automatically enforce it.

### Q: What about backward compatibility?
**A:** The domain layer doesn't break existing APIs. Existing controllers continue to work until migrated.

---

## Conclusion

This domain layer architecture:
- ✅ Provides single source of truth for user state
- ✅ Centralizes cross-domain compliance rules
- ✅ Prevents illegal state combinations
- ✅ Makes controllers thin and declarative
- ✅ Avoids database schema changes
- ✅ Allows incremental adoption
- ✅ Maintains backward compatibility
- ✅ Enforces domain rules consistently

**Next Steps:**
1. Review this document with the team
2. Write tests for existing refactored methods
3. Migrate WalletController as next proof-of-concept
4. Monitor production logs for domain exceptions
5. Continue incremental migration of remaining controllers

---

**Document Version:** 1.0
**Last Updated:** 2026-01-04
**Author:** Domain Architecture Team
