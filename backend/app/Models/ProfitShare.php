<?php
// V-FIX-MISSING-MODEL - ProfitShare Model (was referenced but never created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfitShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_name',
        'start_date',
        'end_date',
        'total_pool',
        'net_profit',
        'status', // pending, calculated, distributed, cancelled, reversed
        'report_visibility', // public, private, partners_only
        'report_url',
        'calculation_metadata',
        'admin_id',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_pool' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'calculation_metadata' => 'array',
        'published_at' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    /**
     * Get the admin who created/processed this profit share period.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get all individual user distributions for this period.
     */
    public function distributions(): HasMany
    {
        return $this->hasMany(UserProfitShare::class);
    }

    /**
     * Get the admin who published the report.
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Get the total distributed amount.
     */
    public function getTotalDistributedAttribute(): float
    {
        return $this->distributions()->sum('amount');
    }

    /**
     * Get the number of beneficiaries.
     */
    public function getBeneficiaryCountAttribute(): int
    {
        return $this->distributions()->count();
    }

    // --- SCOPES ---

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDistributed($query)
    {
        return $query->where('status', 'distributed');
    }

    // --- HELPERS ---

    /**
     * Check if the period can be calculated.
     */
    public function canCalculate(): bool
    {
        return $this->status === 'pending' && $this->total_pool > 0;
    }

    /**
     * Check if the period can be distributed.
     */
    public function canDistribute(): bool
    {
        return $this->status === 'calculated' && $this->distributions()->exists();
    }

    /**
     * Check if the period can be reversed.
     */
    public function canReverse(): bool
    {
        return $this->status === 'distributed';
    }
}
