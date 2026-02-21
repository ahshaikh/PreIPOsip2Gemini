<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-NUMERIC-PRECISION | V-ANALYTICS-ENGINE
 * Refactored to address Module 5 Audit Gaps:
 * 1. Casts: Enforces high-precision decimal casting for units and currency.
 * 2. Relationships: Fully hydrated belongsTo methods for deep analytics.
 * 3. Server-Side Calculations: ROI and ProfitLoss are now calculated on the server
 * using BCMath logic to ensure 100% parity across web and mobile apps.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @mixin IdeHelperUserInvestment
 */
class UserInvestment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'product_id', 'payment_id', 'subscription_id',
        'bulk_purchase_id', 'units_allocated', 'value_allocated',
        'status', 'is_reversed', 'reversed_at', 'reversal_reason', 'reversal_source', 'source'
    ];

    /**
     * [AUDIT FIX]: Standardized casting to prevent floating point drift.
     */
    protected $casts = [
        'units_allocated' => 'decimal:4',
        'value_allocated' => 'decimal:2',
        'is_reversed' => 'boolean',
        'reversed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * FIX 23: Boot logic to prevent double-reversal
     */
    protected static function booted()
    {
        static::updating(function ($investment) {
            // FIX 23: Prevent reversal of already-reversed investments
            if ($investment->isDirty('is_reversed') && $investment->is_reversed) {
                $wasAlreadyReversed = $investment->getOriginal('is_reversed');

                if ($wasAlreadyReversed) {
                    throw new \RuntimeException(
                        "Investment #{$investment->id} is already reversed. Cannot reverse twice."
                    );
                }

                // Automatically set reversed_at timestamp
                if (!$investment->reversed_at) {
                    $investment->reversed_at = now();
                }

                \Log::warning('Investment being reversed', [
                    'investment_id' => $investment->id,
                    'user_id' => $investment->user_id,
                    'value_allocated' => $investment->value_allocated,
                    'reason' => $investment->reversal_reason,
                ]);
            }

            // FIX 42: Validate source='bonus' has corresponding bonus transaction
            if ($investment->isDirty('source') && $investment->source === 'bonus') {
                // Ensure there's a corresponding BonusTransaction
                $hasMatchingBonus = \App\Models\BonusTransaction::where('user_id', $investment->user_id)
                    ->when($investment->payment_id, function($query) use ($investment) {
                        // If payment_id exists, match on it for stronger validation
                        return $query->where('payment_id', $investment->payment_id);
                    })
                    ->when(!$investment->payment_id && $investment->subscription_id, function($query) use ($investment) {
                        // If no payment_id but subscription_id exists, match on that
                        return $query->where('subscription_id', $investment->subscription_id);
                    })
                    ->exists();

                if (!$hasMatchingBonus) {
                    $identifier = $investment->payment_id
                        ? "payment #{$investment->payment_id}"
                        : ($investment->subscription_id
                            ? "subscription #{$investment->subscription_id}"
                            : "user #{$investment->user_id}");

                    throw new \RuntimeException(
                        "Investment source set to 'bonus' but no corresponding BonusTransaction found for {$identifier}. " .
                        "Bonus investments must have a valid bonus transaction record."
                    );
                }

                \Log::info('Investment source validated with bonus transaction', [
                    'investment_id' => $investment->id,
                    'user_id' => $investment->user_id,
                    'payment_id' => $investment->payment_id,
                    'subscription_id' => $investment->subscription_id,
                ]);
            }
        });

        // V-CHARGEBACK-SEMANTICS-2026: Investment reversal is SHARE-ONLY
        //
        // FINANCIAL CONTRACT:
        // Investment reversal returns shares to inventory. Period.
        // Wallet implications are handled EXPLICITLY by the calling service:
        // - Refund flow (handleRefundProcessed): Credits wallet AFTER reversal
        // - Chargeback flow (handleChargebackConfirmed): Debits wallet AFTER reversal
        //
        // This observer MUST NOT mutate wallet. Doing so creates:
        // 1. Hidden side effects (not visible in service layer)
        // 2. Dual mutation layers (observer + service)
        // 3. Need for brittle string-based branching (str_contains)
        //
        // REMOVED: Automatic wallet credit on reversal
        // REASON: Violates single-responsibility, creates implicit behavior
        static::updated(function ($investment) {
            // Log reversal for audit trail (NO WALLET MUTATION)
            if ($investment->wasChanged('is_reversed') && $investment->is_reversed) {
                \Log::info('Investment reversed (share-only, no wallet mutation)', [
                    'investment_id' => $investment->id,
                    'user_id' => $investment->user_id,
                    'value_allocated' => $investment->value_allocated,
                    'reversal_reason' => $investment->reversal_reason,
                    'reversal_source' => $investment->reversal_source,
                    'note' => 'Wallet implications handled by calling service',
                ]);
            }
        });
    }

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bulkPurchase(): BelongsTo
    {
        return $this->belongsTo(BulkPurchase::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // --- ANALYTICS ACCESSORS (Backend-Driven Valuation) ---

    /**
     * [AUDIT FIX]: Current Market Value calculated on server.
     * Ensures parity across frontend devices.
     */
    protected function currentValue(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $product = $this->product; 
                if (!$product) return 0.00;

                $currentPrice = $product->current_market_price ?? $product->face_value_per_unit;
                return (float) bcmul($attributes['units_allocated'], $currentPrice, 2);
            }
        );
    }

    /**
     * [AUDIT FIX]: Accurate Profit/Loss calculation using BCMath.
     */
    protected function profitLoss(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $costBasis = (float) $attributes['value_allocated'];
                return (float) bcsub($this->current_value, $costBasis, 2);
            }
        );
    }

    /**
     * [AUDIT FIX]: Standardized ROI percentage.
     */
    protected function roiPercentage(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $costBasis = (float) $attributes['value_allocated'];
                if ($costBasis == 0) return 0.00;
                
                // (Profit / Cost) * 100
                return (float) bcmul(bcdiv($this->profit_loss, $costBasis, 4), '100', 2);
            }
        );
    }
}