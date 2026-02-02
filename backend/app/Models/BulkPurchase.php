<?php
// V-PHASE2-1730-041 (Created) | V-FINAL-1730-349 (Financial Logic Added)
// V-PHASE4.1: Added double-entry ledger support (expense-based model)
// STORY 4.2: Added provenance enforcement

namespace App\Models;

use App\Exceptions\BulkPurchaseProvenanceException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BulkPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'admin_id',
        'company_id', // PROVENANCE: Which company supplied this inventory
        'company_share_listing_id', // PROVENANCE: Source listing (if from listing)
        'source_type', // PROVENANCE: 'company_listing' or 'manual_entry'
        'approved_by_admin_id', // PROVENANCE: Admin who approved manual entry
        'platform_ledger_entry_id', // DEPRECATED: Link to legacy platform_ledger_entries (Phase 4.0)
        'ledger_entry_id', // PHASE 4.1: Link to double-entry ledger_entries table
        'manual_entry_reason', // PROVENANCE: Why manual entry was needed
        'source_documentation', // PROVENANCE: Supporting documents
        'verified_at', // PROVENANCE: When provenance was verified
        'face_value_purchased',
        'actual_cost_paid',
        'discount_percentage',
        'extra_allocation_percentage',
        'total_value_received',
        'value_remaining',
        'seller_name',
        'purchase_date',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'verified_at' => 'datetime',
        'face_value_purchased' => 'decimal:2',
        'actual_cost_paid' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'extra_allocation_percentage' => 'decimal:2',
        'total_value_received' => 'decimal:2',
        'value_remaining' => 'decimal:2',
    ];

    /**
     * Boot logic to auto-calculate fields on creation.
     * STORY 4.2: Added provenance enforcement for audit compliance.
     */
    protected static function booted()
    {
        static::creating(function ($purchase) {
            // STORY 4.2: Provenance Enforcement
            // INVARIANT: All inventory must have verifiable provenance
            // Manual entries require explicit justification and documentation
            self::enforceProvenance($purchase);

            // Validation
            if ($purchase->face_value_purchased <= 0) {
                throw new \InvalidArgumentException("Face value must be positive.");
            }
            if ($purchase->actual_cost_paid < 0) {
                throw new \InvalidArgumentException("Actual cost cannot be negative.");
            }

            // Auto-calculate
            $purchase->total_value_received = $purchase->face_value_purchased * (1 + ($purchase->extra_allocation_percentage / 100));
            $purchase->discount_percentage = (($purchase->face_value_purchased - $purchase->actual_cost_paid) / $purchase->face_value_purchased) * 100;

            // On creation, remaining is the total received
            $purchase->value_remaining = $purchase->total_value_received;
        });
    }

    /**
     * STORY 4.2: Enforce Provenance Requirements
     *
     * GOVERNANCE INVARIANT:
     * - source_type is mandatory (either 'company_listing' or 'manual_entry')
     * - company_listing source requires company_share_listing_id
     * - manual_entry source requires:
     *   - manual_entry_reason (why manual instead of listing)
     *   - source_documentation (supporting documents)
     *
     * WHY: Audit trail for regulator/compliance review. Every unit of inventory
     * must be traceable to either an approved company listing or have explicit
     * justification for manual entry.
     */
    private static function enforceProvenance(BulkPurchase $purchase): void
    {
        // GATE 1: source_type is mandatory
        if (empty($purchase->source_type)) {
            throw BulkPurchaseProvenanceException::sourceTypeRequired();
        }

        // GATE 2: company_listing requires listing ID
        if ($purchase->source_type === 'company_listing') {
            if (empty($purchase->company_share_listing_id)) {
                throw BulkPurchaseProvenanceException::listingSourceRequiresListingId();
            }
            // Listing-based entries have automatic provenance through the listing
            return;
        }

        // GATE 3: manual_entry requires reason
        if ($purchase->source_type === 'manual_entry') {
            if (empty($purchase->manual_entry_reason)) {
                throw BulkPurchaseProvenanceException::manualEntryRequiresReason();
            }

            // GATE 4: manual_entry requires documentation
            if (empty($purchase->source_documentation)) {
                throw BulkPurchaseProvenanceException::manualEntryRequiresDocumentation();
            }
        }
    }

    // --- RELATIONSHIPS ---

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * [P1 FIX]: UserInvestments allocated from this bulk purchase batch.
     *
     * This enables Deal to traverse to UserInvestment via:
     * Deal → Product → BulkPurchase → UserInvestment
     */
    public function userInvestments()
    {
        return $this->hasMany(UserInvestment::class);
    }

    /**
     * PROVENANCE: Company that supplied this inventory
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * PROVENANCE: Company share listing (if inventory from approved listing)
     */
    public function companyShareListing(): BelongsTo
    {
        return $this->belongsTo(CompanyShareListing::class);
    }

    /**
     * PROVENANCE: Admin who approved manual inventory entry
     */
    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_admin_id');
    }

    /**
     * @deprecated PHASE 4.1: Use ledgerEntry() instead.
     *
     * GAP 1 FIX: Link to platform ledger entry that proves capital movement.
     * This references the LEGACY platform_ledger_entries table.
     *
     * INVARIANT: Inventory existence === proven platform capital movement.
     * This relationship enables audit verification that every BulkPurchase
     * has a corresponding ledger debit proving capital was expended.
     */
    public function platformLedgerEntry(): BelongsTo
    {
        return $this->belongsTo(PlatformLedgerEntry::class);
    }

    /**
     * PHASE 4.1: Link to double-entry ledger entry (expense model).
     *
     * ACCOUNTING MODEL:
     * - Inventory cost is expensed immediately (DEBIT COST_OF_SHARES, CREDIT BANK)
     * - This entry proves capital movement and expense recognition
     * - Replaces legacy platform_ledger_entry_id for new purchases
     */
    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class);
    }

    /**
     * GAP 1 FIX: Check if this inventory has proven capital movement.
     *
     * PHASE 4.1 UPDATE: Checks both legacy and new ledger systems.
     * Returns true if EITHER ledger entry exists, proving the invariant holds.
     *
     * AUDIT HELPER: Use this to verify inventory has proper financial backing.
     */
    public function hasProvenCapitalMovement(): bool
    {
        // Check new double-entry ledger first (Phase 4.1+)
        if ($this->ledger_entry_id !== null) {
            return true;
        }

        // Fall back to legacy platform ledger (Phase 4.0 and earlier)
        return $this->platform_ledger_entry_id !== null;
    }

    // --- ACCESSORS (CALCULATIONS) ---

    /**
     * Calculates the total value allocated from this purchase.
     */
    protected function allocatedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_value_received - $this->value_remaining
        );
    }
    
    /**
     * Alias for value_remaining.
     */
    protected function availableAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->value_remaining
        );
    }

    /**
     * Calculates the total profit margin in Rupees.
     * (Total Value - What we Paid)
     */
    protected function grossMargin(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_value_received - $this->actual_cost_paid
        );
    }

    /**
     * Calculates the profit margin as a percentage.
     * (Margin / Cost)
     */
    protected function grossMarginPercentage(): Attribute
    {
        return Attribute::make(
            get: function ()  {
                if ($this->actual_cost_paid == 0) {
                    return 0;
                }
                return ($this->gross_margin / $this->actual_cost_paid) * 100;
            }
        );
    }
}