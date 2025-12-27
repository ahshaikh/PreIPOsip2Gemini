<?php

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
