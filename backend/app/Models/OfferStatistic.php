<?php

/**
 * DEPRECATED - P0.2 FIX
 *
 * This model has been DEPRECATED.
 * The offer_statistics table was dropped in migration 2025_12_27_150000.
 * Replaced by campaign_usages (created in migration 2025_12_26_000002).
 *
 * USE: Campaign statistics should be queried from campaign_usages table
 *
 * This model will throw an exception if instantiated to prevent dual semantics bug.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Campaign Performance Statistics.
 *
 * Aggregated daily metrics for offer performance tracking.
 */
class OfferStatistic extends Model
{
    /**
     * [PROTOCOL 1]: Make OfferStatistic model STRUCTURALLY IMPOSSIBLE to use.
     *
     * WHY: offer_statistics table was dropped and replaced by campaign_usages.
     * This constructor throws exception to fail-fast on any instantiation attempt.
     */
    public function __construct(array $attributes = [])
    {
        throw new \RuntimeException(
            'P0.2 FIX: OfferStatistic model is DEPRECATED. ' .
            'The offer_statistics table was dropped. ' .
            'Statistics should be queried from campaign_usages table. ' .
            'See migration 2025_12_27_150000_consolidate_offer_to_campaign_rename_pivots.php'
        );
    }
    protected $fillable = [
        'offer_id',
        'product_id',
        'deal_id',
        'stat_date',
        'total_views',
        'total_applications',
        'total_conversions',
        'total_discount_given',
        'total_revenue_generated',
        'conversion_rate',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'total_views' => 'integer',
        'total_applications' => 'integer',
        'total_conversions' => 'integer',
        'total_discount_given' => 'decimal:2',
        'total_revenue_generated' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
    ];

    // --- RELATIONSHIPS ---

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    // --- HELPERS ---

    /**
     * Increment view count for this stat record.
     */
    public function incrementViews(int $count = 1): void
    {
        $this->increment('total_views', $count);
    }

    /**
     * Increment application count (offer attempted).
     */
    public function incrementApplications(int $count = 1): void
    {
        $this->increment('total_applications', $count);
        $this->updateConversionRate();
    }

    /**
     * Increment conversion count (investment completed).
     */
    public function incrementConversions(float $discountGiven, float $revenue): void
    {
        $this->increment('total_conversions');
        $this->increment('total_discount_given', $discountGiven);
        $this->increment('total_revenue_generated', $revenue);
        $this->updateConversionRate();
    }

    /**
     * Update conversion rate based on applications vs conversions.
     */
    protected function updateConversionRate(): void
    {
        if ($this->total_applications > 0) {
            $this->conversion_rate = ($this->total_conversions / $this->total_applications) * 100;
            $this->saveQuietly();
        }
    }

    /**
     * Get or create stat record for today.
     */
    public static function getTodayStats(int $offerId, ?int $productId = null, ?int $dealId = null): self
    {
        return self::firstOrCreate([
            'offer_id' => $offerId,
            'product_id' => $productId,
            'deal_id' => $dealId,
            'stat_date' => now()->toDateString(),
        ], [
            'total_views' => 0,
            'total_applications' => 0,
            'total_conversions' => 0,
            'total_discount_given' => 0,
            'total_revenue_generated' => 0,
            'conversion_rate' => 0,
        ]);
    }
}
