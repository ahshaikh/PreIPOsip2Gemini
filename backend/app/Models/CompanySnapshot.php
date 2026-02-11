<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompanySnapshot Model
 * 
 * FIX 5 (P1): Immutable record of company data at specific points in time
 * Created when company is frozen (listing approval, deal launch)
 * Provides audit trail and regulatory compliance
 *
 * @mixin IdeHelperCompanySnapshot
 */
class CompanySnapshot extends Model
{
    protected $fillable = [
        'company_id',
        'company_share_listing_id',
        'bulk_purchase_id',
        'snapshot_data',
        'snapshot_reason',
        'snapshot_at',
        'snapshot_by_admin_id',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'snapshot_at' => 'datetime',
    ];

    /**
     * Prevent modifications to snapshots (immutable audit records)
     */
    protected static function booted()
    {
        static::updating(function () {
            throw new \RuntimeException('Company snapshots are immutable and cannot be modified');
        });

        static::deleting(function () {
            throw new \RuntimeException('Company snapshots cannot be deleted (audit requirement)');
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyShareListing(): BelongsTo
    {
        return $this->belongsTo(CompanyShareListing::class);
    }

    public function bulkPurchase(): BelongsTo
    {
        return $this->belongsTo(BulkPurchase::class);
    }

    public function snapshotByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'snapshot_by_admin_id');
    }

    /**
     * Get specific field from snapshot data
     */
    public function getSnapshotField(string $field, $default = null)
    {
        return data_get($this->snapshot_data, $field, $default);
    }

    /**
     * Compare current company data to snapshot
     */
    public function getChanges(): array
    {
        $currentData = $this->company->toArray();
        $snapshotData = $this->snapshot_data;
        $changes = [];

        foreach ($snapshotData as $key => $oldValue) {
            $newValue = $currentData[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }
}
