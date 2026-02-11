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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @mixin IdeHelperUserInvestment
 */
class UserInvestment extends Model
{
    protected $fillable = [
        'user_id', 'product_id', 'payment_id', 'subscription_id',
        'bulk_purchase_id', 'units_allocated', 'value_allocated',
        'status', 'is_reversed', 'reversed_at', 'reversal_reason', 'source'
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

        // FIX 46: Create compensating wallet transaction when investment is reversed
        static::updated(function ($investment) {
            // Check if is_reversed just changed from false to true
            if ($investment->wasChanged('is_reversed') && $investment->is_reversed) {
                try {
                    // Get WalletService and create refund transaction
                    $walletService = app(\App\Services\WalletService::class);

                    // Bypass compliance check since this is a reversal (internal operation)
                    $transaction = $walletService->deposit(
                        user: $investment->user,
                        amount: $investment->value_allocated,
                        type: \App\Enums\TransactionType::REFUND,
                        description: "Investment reversal refund - Investment #{$investment->id}" .
                                   ($investment->reversal_reason ? " - Reason: {$investment->reversal_reason}" : ""),
                        reference: $investment,
                        bypassComplianceCheck: true
                    );

                    \Log::info('Investment reversal compensation created', [
                        'investment_id' => $investment->id,
                        'user_id' => $investment->user_id,
                        'refund_amount' => $investment->value_allocated,
                        'transaction_id' => $transaction->id,
                        'reason' => $investment->reversal_reason,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to create investment reversal compensation', [
                        'investment_id' => $investment->id,
                        'user_id' => $investment->user_id,
                        'amount' => $investment->value_allocated,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Re-throw the exception to prevent silent failures
                    throw new \RuntimeException(
                        "Failed to create reversal refund for investment #{$investment->id}: {$e->getMessage()}",
                        0,
                        $e
                    );
                }
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