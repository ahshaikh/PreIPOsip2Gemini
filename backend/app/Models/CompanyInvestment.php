<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * CompanyInvestment Model
 *
 * PURPOSE: Direct company investments (outside of SIP subscriptions)
 * This model handles one-time investments in companies via the investor portal
 *
 * DISTINCTION from Investment model:
 * - Investment: SIP-based investments with subscription_id and deal_id
 * - CompanyInvestment: Direct company investments with disclosure snapshots
 */
class CompanyInvestment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'amount',
        'disclosure_snapshot_id',
        'status',
        'invested_at',
        'idempotency_key',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'invested_at' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function disclosureSnapshot(): BelongsTo
    {
        return $this->belongsTo(InvestmentDisclosureSnapshot::class, 'disclosure_snapshot_id');
    }

    // --- SCOPES ---

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // --- HELPER METHODS ---

    /**
     * Check if investment can be cancelled
     */
    public function canCancel(): bool
    {
        return in_array($this->status, ['pending', 'active']);
    }
}
