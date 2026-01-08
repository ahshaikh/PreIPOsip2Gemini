# State Machine Pattern Usage Example

## FIX 17 (P3): State Machine Pattern Implementation

This document demonstrates how to integrate the `HasStateMachine` trait into your models for clean state transition management.

## Example: Withdrawal Model

### 1. Add the trait and configuration to your model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasStateMachine;

class Withdrawal extends Model
{
    use HasStateMachine;

    /**
     * State machine configuration
     */
    protected static $stateConfig = [
        'field' => 'status',

        'states' => [
            'pending',
            'approved',
            'completed',
            'rejected',
            'cancelled',
        ],

        'transitions' => [
            'approve' => [
                'from' => ['pending'],
                'to' => 'approved',
                'label' => 'Approve Withdrawal',
            ],
            'complete' => [
                'from' => ['approved'],
                'to' => 'completed',
                'label' => 'Mark as Completed',
            ],
            'reject' => [
                'from' => ['pending'],
                'to' => 'rejected',
                'label' => 'Reject Withdrawal',
            ],
            'cancel' => [
                'from' => ['pending', 'approved'],
                'to' => 'cancelled',
                'label' => 'Cancel Withdrawal',
            ],
        ],
    ];

    // ... rest of model code
}
```

### 2. Add transition hooks (optional)

```php
/**
 * Called before approve transition
 */
protected function beforeApprove()
{
    // Validate user has sufficient balance
    if ($this->user->wallet_balance < $this->amount) {
        throw new \RuntimeException('Insufficient balance');
    }

    // Check withdrawal limits
    $this->validateWithdrawalLimits();
}

/**
 * Called after approve transition
 */
protected function afterApprove()
{
    // Deduct from wallet
    $this->user->decrement('wallet_balance', $this->amount);

    // Send notification
    \App\Jobs\SendEmailNotification::withdrawalApproved(
        $this->user->email,
        $this->user->name,
        $this->amount,
        $this->transaction_id,
        $this->user_id
    );

    // Create audit log entry
    \Log::info("Withdrawal approved", [
        'withdrawal_id' => $this->id,
        'user_id' => $this->user_id,
        'amount' => $this->amount,
    ]);
}

/**
 * Called after complete transition
 */
protected function afterComplete()
{
    // Mark as paid
    $this->update(['paid_at' => now()]);

    // Notify user
    \App\Jobs\SendEmailNotification::dispatch(
        $this->user->email,
        $this->user->name,
        'withdrawal-completed',
        'Withdrawal Completed',
        ['amount' => $this->amount],
        $this->user_id
    );
}

/**
 * Called after reject transition
 */
protected function afterReject()
{
    // Notify user
    \App\Jobs\SendEmailNotification::withdrawalRejected(
        $this->user->email,
        $this->user->name,
        $this->amount,
        $this->rejection_reason ?? 'Not specified',
        $this->user_id
    );
}
```

### 3. Usage in Controller

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Withdrawal;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    /**
     * Approve withdrawal
     */
    public function approve(Request $request, $id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        // Check if transition is allowed
        if (!$withdrawal->can('approve')) {
            return response()->json([
                'error' => 'Withdrawal cannot be approved from current state',
                'current_state' => $withdrawal->status,
            ], 422);
        }

        try {
            // Perform transition (includes before/after hooks)
            $withdrawal->transitionTo('approved', [
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal approved successfully',
                'data' => $withdrawal->fresh(),
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);

        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get available actions for withdrawal
     */
    public function actions($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        return response()->json([
            'current_state' => $withdrawal->status,
            'available_transitions' => $withdrawal->availableTransitions(),
        ]);
    }

    /**
     * Get state history
     */
    public function history($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        return response()->json([
            'withdrawal_id' => $id,
            'current_state' => $withdrawal->status,
            'history' => $withdrawal->stateHistory(),
        ]);
    }
}
```

### 4. Query Examples

```php
// Get all pending withdrawals
$pending = Withdrawal::inState('pending')->get();

// Get withdrawals that need action (pending or approved)
$needsAction = Withdrawal::inState(['pending', 'approved'])->get();

// Check if withdrawal is in specific state
if ($withdrawal->isInState('pending')) {
    // Do something
}

// Check if withdrawal can be approved
if ($withdrawal->can('approve')) {
    // Show approve button
}

// Get all available transitions
$actions = $withdrawal->availableTransitions();
// Returns:
// [
//     ['name' => 'approve', 'to' => 'approved', 'label' => 'Approve Withdrawal'],
//     ['name' => 'reject', 'to' => 'rejected', 'label' => 'Reject Withdrawal'],
// ]
```

## Benefits

1. **Centralized State Logic**: All state transitions are defined in one place
2. **Validation**: Invalid transitions are prevented automatically
3. **Audit Trail**: All state changes are logged automatically
4. **Hooks**: Before/after hooks allow custom logic at transition points
5. **Type Safety**: Reduces errors from typos in state strings
6. **Queryable**: Easy to query by state or check permissions
7. **Self-Documenting**: State diagram is clear from configuration

## Other Models That Can Use This Pattern

- **KycVerification**: pending → approved/rejected/resubmit_required
- **Deal**: draft → approved/rejected → active/inactive
- **CompanyShareListing**: pending → under_review → approved/rejected
- **Campaign**: draft → approved → active/paused/expired
- **Payment**: pending → processing → success/failed
- **Subscription**: active → paused → cancelled → expired

## State Diagram Example (Withdrawal)

```
┌─────────┐
│ pending │
└────┬────┘
     │
     ├──approve──► approved ──complete──► completed
     │
     └──reject──► rejected

pending/approved ──cancel──► cancelled
```

## Testing

```php
public function test_withdrawal_state_transitions()
{
    $withdrawal = Withdrawal::factory()->create(['status' => 'pending']);

    // Test valid transition
    $this->assertTrue($withdrawal->can('approve'));
    $withdrawal->transitionTo('approved');
    $this->assertEquals('approved', $withdrawal->status);

    // Test invalid transition
    $this->assertFalse($withdrawal->can('reject')); // Can't reject after approval

    // Test exception on invalid transition
    $this->expectException(\InvalidArgumentException::class);
    $withdrawal->transitionTo('rejected');
}
```
