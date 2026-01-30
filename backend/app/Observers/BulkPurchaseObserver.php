<?php
/**
 * FIX 2 (P0): BulkPurchase Immutability Observer
 *
 * CRITICAL: Enforces immutability of financial fields after BulkPurchase creation
 * to prevent retroactive audit trail destruction and margin manipulation.
 *
 * Immutable Fields (CANNOT be changed after creation):
 * - face_value_purchased
 * - actual_cost_paid
 * - total_value_received
 * - discount_percentage
 * - extra_allocation_percentage
 * - company_id
 * - company_share_listing_id
 * - source_type
 * - purchase_date
 *
 * Mutable Fields (can be updated):
 * - value_remaining (decremented by AllocationService)
 * - notes (admin can add notes)
 * - admin_id (admin who last modified)
 * - platform_ledger_entry_id (set after creation to link ledger proof - GAP 1 FIX)
 *
 * Deletion Protection:
 * - CANNOT delete if has active allocations (allocated_amount > 0)
 */

namespace App\Observers;

use App\Models\BulkPurchase;
use RuntimeException;
use Illuminate\Support\Facades\Log;

class BulkPurchaseObserver
{
    /**
     * Handle the BulkPurchase "updating" event.
     * Prevents modification of immutable financial fields after creation.
     */
    public function updating(BulkPurchase $bulkPurchase): void
    {
        // Define immutable fields that cannot be changed after creation
        $immutableFields = [
            'face_value_purchased',
            'actual_cost_paid',
            'total_value_received',
            'discount_percentage',
            'extra_allocation_percentage',
            'company_id',
            'product_id',
            'company_share_listing_id',
            'source_type',
            'purchase_date',
            'approved_by_admin_id',
            'verified_at',
            'source_documentation',
            'seller_name',
        ];

        // Get dirty (changed) fields
        $dirty = $bulkPurchase->getDirty();

        // Check for violations
        $violations = array_intersect($immutableFields, array_keys($dirty));

        if (!empty($violations)) {
            $violationDetails = [];
            foreach ($violations as $field) {
                $violationDetails[$field] = [
                    'old' => $bulkPurchase->getOriginal($field),
                    'new' => $dirty[$field],
                ];
            }

            // Log critical security event
            Log::critical('BulkPurchase immutability violation attempted', [
                'bulk_purchase_id' => $bulkPurchase->id,
                'product_id' => $bulkPurchase->product_id,
                'company_id' => $bulkPurchase->company_id,
                'violations' => $violationDetails,
                'actor_id' => auth()->id(),
                'actor_type' => auth()->user() ? get_class(auth()->user()) : 'Unknown',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Create audit log entry
            \App\Models\AuditLog::create([
                'action' => 'bulk_purchase.immutability_violation_blocked',
                'actor_id' => auth()->id(),
                'actor_type' => auth()->user() ? get_class(auth()->user()) : null,
                'description' => 'Attempted to modify immutable BulkPurchase fields',
                'old_values' => collect($violations)->mapWithKeys(fn($field) => [
                    $field => $bulkPurchase->getOriginal($field)
                ])->toArray(),
                'new_values' => collect($violations)->mapWithKeys(fn($field) => [
                    $field => $dirty[$field]
                ])->toArray(),
                'metadata' => [
                    'bulk_purchase_id' => $bulkPurchase->id,
                    'violations' => $violations,
                    'risk_level' => 'critical',
                ],
            ]);

            throw new RuntimeException(
                'BulkPurchase financial fields are immutable after creation. ' .
                'Cannot modify: ' . implode(', ', $violations) . '. ' .
                'Only value_remaining, notes, and admin_id can be updated. ' .
                'This violation has been logged for audit.'
            );
        }

        // Additional validation: value_remaining cannot exceed total_value_received
        if (isset($dirty['value_remaining'])) {
            if ($dirty['value_remaining'] > $bulkPurchase->total_value_received) {
                throw new RuntimeException(
                    'value_remaining (₹' . ($dirty['value_remaining']) . ') ' .
                    'cannot exceed total_value_received (₹' . $bulkPurchase->total_value_received . ')'
                );
            }

            if ($dirty['value_remaining'] < 0) {
                throw new RuntimeException('value_remaining cannot be negative');
            }
        }
    }

    /**
     * Handle the BulkPurchase "deleting" event.
     * Prevents deletion if has active allocations.
     */
    public function deleting(BulkPurchase $bulkPurchase): void
    {
        // Calculate allocated amount
        $allocatedAmount = $bulkPurchase->total_value_received - $bulkPurchase->value_remaining;

        if ($allocatedAmount > 0) {
            // Check if there are actual UserInvestment records
            $investmentCount = \App\Models\UserInvestment::where('bulk_purchase_id', $bulkPurchase->id)
                ->where('is_reversed', false)
                ->count();

            Log::warning('BulkPurchase deletion blocked', [
                'bulk_purchase_id' => $bulkPurchase->id,
                'allocated_amount' => $allocatedAmount,
                'investment_count' => $investmentCount,
                'actor_id' => auth()->id(),
            ]);

            throw new RuntimeException(
                "Cannot delete BulkPurchase #{$bulkPurchase->id} with active allocations. " .
                "₹" . number_format($allocatedAmount, 2) . " has been allocated to {$investmentCount} investments. " .
                "All investments must be reversed before deletion."
            );
        }

        // Log deletion for audit
        \App\Models\AuditLog::create([
            'action' => 'bulk_purchase.deleted',
            'actor_id' => auth()->id(),
            'actor_type' => auth()->user() ? get_class(auth()->user()) : null,
            'description' => "Deleted BulkPurchase #{$bulkPurchase->id}",
            'old_values' => $bulkPurchase->toArray(),
            'metadata' => [
                'bulk_purchase_id' => $bulkPurchase->id,
                'product_id' => $bulkPurchase->product_id,
                'company_id' => $bulkPurchase->company_id,
                'total_value_received' => $bulkPurchase->total_value_received,
            ],
        ]);
    }

    /**
     * Handle the BulkPurchase "created" event.
     * Log creation for audit trail.
     */
    public function created(BulkPurchase $bulkPurchase): void
    {
        Log::info('BulkPurchase created', [
            'bulk_purchase_id' => $bulkPurchase->id,
            'product_id' => $bulkPurchase->product_id,
            'company_id' => $bulkPurchase->company_id,
            'total_value_received' => $bulkPurchase->total_value_received,
            'source_type' => $bulkPurchase->source_type,
            'actor_id' => auth()->id(),
        ]);

        \App\Models\AuditLog::create([
            'action' => 'bulk_purchase.created',
            'actor_id' => auth()->id(),
            'actor_type' => auth()->user() ? get_class(auth()->user()) : null,
            'description' => "Created BulkPurchase #{$bulkPurchase->id} for Product #{$bulkPurchase->product_id}",
            'new_values' => $bulkPurchase->toArray(),
            'metadata' => [
                'bulk_purchase_id' => $bulkPurchase->id,
                'product_id' => $bulkPurchase->product_id,
                'company_id' => $bulkPurchase->company_id,
                'source_type' => $bulkPurchase->source_type,
            ],
        ]);

        // STORY 2.2: Automatically lock the product when the first inventory exists AND product is in 'approved' status
        $product = $bulkPurchase->product()->first(); // Eager load the product if not already loaded

        if ($product) {
            // Race-safe check to confirm this is truly the first inventory item
            $isFirstInventory = $product->bulkPurchases()
                ->whereKeyNot($bulkPurchase->id)
                ->doesntExist();

            // Rule: If product status is approved AND this is the first inventory
            if ($product->status === 'approved' && $isFirstInventory) {
                $product->status = 'locked';
                $product->save(); // This will trigger the Product model's booted() method for status transition

                Log::info('Product automatically transitioned to LOCKED due to first inventory being added and prior APPROVED status.', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'bulk_purchase_id' => $bulkPurchase->id,
                    'previous_status' => 'approved',
                    'new_status' => 'locked',
                ]);
            }
        }
    }

    /**
     * Handle the BulkPurchase "updated" event.
     * Log allowed updates for audit trail.
     */
    public function updated(BulkPurchase $bulkPurchase): void
    {
        $dirty = $bulkPurchase->getDirty();

        // Only log if actual changes occurred
        if (!empty($dirty)) {
            Log::info('BulkPurchase updated', [
                'bulk_purchase_id' => $bulkPurchase->id,
                'changes' => $dirty,
                'actor_id' => auth()->id(),
            ]);

            \App\Models\AuditLog::create([
                'action' => 'bulk_purchase.updated',
                'actor_id' => auth()->id(),
                'actor_type' => auth()->user() ? get_class(auth()->user()) : null,
                'description' => "Updated BulkPurchase #{$bulkPurchase->id}",
                'old_values' => collect($dirty)->mapWithKeys(fn($value, $field) => [
                    $field => $bulkPurchase->getOriginal($field)
                ])->toArray(),
                'new_values' => $dirty,
                'metadata' => [
                    'bulk_purchase_id' => $bulkPurchase->id,
                    'product_id' => $bulkPurchase->product_id,
                ],
            ]);
        }
    }
}
