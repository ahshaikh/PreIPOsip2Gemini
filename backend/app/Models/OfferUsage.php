<?php

/**
 * DEPRECATED - P0.2 FIX
 *
 * This model has been DEPRECATED.
 * The offer_usages table was dropped in migration 2025_12_27_150000.
 * Replaced by campaign_usages (created in migration 2025_12_26_000002).
 *
 * USE: App\Models\CampaignUsage instead (if it exists)
 *
 * This model will throw an exception if instantiated to prevent dual semantics bug.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Offer Usage Tracking.
 *
 * Records each time a user applies an offer to an investment.
 */
class OfferUsage extends Model
{
    const UPDATED_AT = null;

    /**
     * [PROTOCOL 1]: Make OfferUsage model STRUCTURALLY IMPOSSIBLE to use.
     *
     * WHY: offer_usages table was dropped and replaced by campaign_usages.
     * This constructor throws exception to fail-fast on any instantiation attempt.
     */
    public function __construct(array $attributes = [])
    {
        throw new \RuntimeException(
            'P0.2 FIX: OfferUsage model is DEPRECATED. ' .
            'The offer_usages table was dropped. ' .
            'Use campaign_usages table (CampaignUsage model if exists). ' .
            'See migration 2025_12_27_150000_consolidate_offer_to_campaign_rename_pivots.php'
        );
    }

    protected $fillable = [
        'offer_id',
        'user_id',
        'investment_id',
        'product_id',
        'deal_id',
        'discount_applied',
        'investment_amount',
        'code_used',
        'used_at',
    ];

    protected $casts = [
        'discount_applied' => 'decimal:2',
        'investment_amount' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}
