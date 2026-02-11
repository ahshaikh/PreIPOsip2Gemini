<?php
// V-FIX-MISSING-MODEL - ProfitShare Model (was referenced but never created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperProfitShare
 */
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

    /**
     * FIX 47: Boot logic to validate calculation_metadata schema
     */
    protected static function booted()
    {
        static::saving(function ($profitShare) {
            // FIX 47: Validate calculation_metadata schema when present
            if (!empty($profitShare->calculation_metadata)) {
                $metadata = $profitShare->calculation_metadata;

                // Required top-level fields
                $requiredFields = [
                    'formula_type',
                    'eligibility_criteria',
                    'eligible_users',
                    'total_eligible_investment',
                    'calculated_at',
                ];

                $missingFields = [];
                foreach ($requiredFields as $field) {
                    if (!isset($metadata[$field])) {
                        $missingFields[] = $field;
                    }
                }

                if (!empty($missingFields)) {
                    throw new \InvalidArgumentException(
                        "Profit share calculation_metadata is missing required fields: " .
                        implode(', ', $missingFields) . ". " .
                        "Expected schema: {formula_type, eligibility_criteria, eligible_users, " .
                        "total_eligible_investment, calculated_at}"
                    );
                }

                // Validate eligibility_criteria sub-object
                if (isset($metadata['eligibility_criteria'])) {
                    $criteria = $metadata['eligibility_criteria'];
                    $requiredCriteria = ['min_months', 'min_investment', 'require_active'];

                    $missingCriteria = [];
                    foreach ($requiredCriteria as $criterion) {
                        if (!isset($criteria[$criterion])) {
                            $missingCriteria[] = $criterion;
                        }
                    }

                    if (!empty($missingCriteria)) {
                        throw new \InvalidArgumentException(
                            "Profit share calculation_metadata.eligibility_criteria is missing required fields: " .
                            implode(', ', $missingCriteria) . ". " .
                            "Expected schema: {min_months, min_investment, require_active}"
                        );
                    }
                }

                // Validate data types
                if (!is_string($metadata['formula_type'])) {
                    throw new \InvalidArgumentException(
                        "calculation_metadata.formula_type must be a string, got: " .
                        gettype($metadata['formula_type'])
                    );
                }

                if (!is_numeric($metadata['eligible_users']) || $metadata['eligible_users'] < 0) {
                    throw new \InvalidArgumentException(
                        "calculation_metadata.eligible_users must be a non-negative number, got: " .
                        ($metadata['eligible_users'] ?? 'null')
                    );
                }

                if (!is_numeric($metadata['total_eligible_investment']) || $metadata['total_eligible_investment'] < 0) {
                    throw new \InvalidArgumentException(
                        "calculation_metadata.total_eligible_investment must be a non-negative number, got: " .
                        ($metadata['total_eligible_investment'] ?? 'null')
                    );
                }

                \Log::info('Profit share metadata validated', [
                    'profit_share_id' => $profitShare->id ?? 'new',
                    'period_name' => $profitShare->period_name,
                    'formula_type' => $metadata['formula_type'],
                    'eligible_users' => $metadata['eligible_users'],
                ]);
            }
        });
    }

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
