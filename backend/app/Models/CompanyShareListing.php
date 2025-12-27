<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Company Share Listing - Self-service share upload.
 *
 * WORKFLOW:
 * - Company creates listing
 * - Admin reviews
 * - Approved → BulkPurchase created
 * - Deal can be created from listing
 */
class CompanyShareListing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'submitted_by',
        'listing_title',
        'description',
        'total_shares_offered',
        'face_value_per_share',
        'asking_price_per_share',
        'total_value',
        'minimum_purchase_value',
        'current_company_valuation',
        'valuation_currency',
        'percentage_of_company',
        'terms_and_conditions',
        'offer_valid_until',
        'lock_in_period',
        'rights_attached',
        'documents',
        'financial_documents',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'rejection_reason',
        'bulk_purchase_id',
        'approved_quantity',
        'approved_price',
        'discount_percentage',
        'view_count',
        'last_viewed_at',
    ];

    protected $casts = [
        'total_shares_offered' => 'decimal:4',
        'face_value_per_share' => 'decimal:2',
        'asking_price_per_share' => 'decimal:2',
        'total_value' => 'decimal:2',
        'minimum_purchase_value' => 'decimal:2',
        'current_company_valuation' => 'decimal:2',
        'percentage_of_company' => 'decimal:4',
        'approved_quantity' => 'decimal:4',
        'approved_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'offer_valid_until' => 'date',
        'reviewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'lock_in_period' => 'array',
        'rights_attached' => 'array',
        'documents' => 'array',
        'financial_documents' => 'array',
    ];

    protected static function booted()
    {
        // Auto-calculate total_value on creation
        static::creating(function ($listing) {
            $listing->total_value = $listing->total_shares_offered * $listing->asking_price_per_share;
        });

        // Log activity on status change
        static::updated(function ($listing) {
            if ($listing->isDirty('status')) {
                CompanyShareListingActivity::create([
                    'listing_id' => $listing->id,
                    'actor_id' => auth()->id(),
                    'actor_type' => auth()->guard('company_user')->check() ? 'company_user' : 'admin',
                    'action' => 'status_changed',
                    'notes' => "Status changed from {$listing->getOriginal('status')} to {$listing->status}",
                    'metadata' => [
                        'old_status' => $listing->getOriginal('status'),
                        'new_status' => $listing->status,
                    ],
                ]);
            }
        });
    }

    // --- RELATIONSHIPS ---

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(CompanyUser::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function bulkPurchase(): BelongsTo
    {
        return $this->belongsTo(BulkPurchase::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CompanyShareListingActivity::class, 'listing_id');
    }

    // --- SCOPES ---

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeAwaitingReview($query)
    {
        return $query->whereIn('status', ['pending', 'under_review']);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'under_review'])
                    ->where(function ($q) {
                        $q->whereNull('offer_valid_until')
                          ->orWhere('offer_valid_until', '>=', now());
                    });
    }

    // --- HELPERS ---

    /**
     * Check if offer has expired.
     */
    public function isExpired(): bool
    {
        return $this->offer_valid_until && $this->offer_valid_until->isPast();
    }

    /**
     * Mark as viewed by admin.
     */
    public function markAsViewed()
    {
        $this->increment('view_count');
        $this->update(['last_viewed_at' => now()]);

        CompanyShareListingActivity::create([
            'listing_id' => $this->id,
            'actor_id' => auth()->id(),
            'actor_type' => 'admin',
            'action' => 'viewed',
        ]);
    }

    /**
     * Calculate potential discount if admin negotiates.
     */
    public function calculateDiscount(float $approvedPrice): float
    {
        if ($this->asking_price_per_share == 0) {
            return 0;
        }

        return (($this->asking_price_per_share - $approvedPrice) / $this->asking_price_per_share) * 100;
    }

    /**
     * Get estimated platform profit margin.
     */
    public function getEstimatedProfitAttribute(): float
    {
        if (!$this->approved_price || !$this->approved_quantity) {
            return 0;
        }

        $costPaid = $this->approved_quantity * $this->approved_price;
        $faceValue = $this->approved_quantity * $this->face_value_per_share;

        return $faceValue - $costPaid;
    }
}
