<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PHASE 4 - MODEL: DisclosureChangeLog
 * 
 * PURPOSE:
 * Complete audit trail of all disclosure modifications.
 * Powers "what's new" feature for investors.
 * 
 * REGULATORY COMPLIANCE:
 * - Immutable audit trail of all changes
 * - Includes before/after values for transparency
 * - Tracks who made changes and why
 * - Material changes trigger investor notifications
 *
 * @mixin IdeHelperDisclosureChangeLog
 */
class DisclosureChangeLog extends Model
{
    use HasFactory;

    protected $table = 'disclosure_change_log';

    protected $fillable = [
        'company_disclosure_id',
        'company_id',
        'change_type',
        'change_summary',
        'changed_fields',
        'field_diffs',
        'changed_by',
        'changed_at',
        'change_reason',
        'is_material_change',
        'investor_notification_priority',
        'version_before',
        'version_after',
        'is_visible_to_investors',
        'investor_visible_at',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'field_diffs' => 'array',
        'changed_at' => 'datetime',
        'is_material_change' => 'boolean',
        'is_visible_to_investors' => 'boolean',
        'investor_visible_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function disclosure()
    {
        return $this->belongsTo(CompanyDisclosure::class, 'company_disclosure_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeMaterialChanges($query)
    {
        return $query->where('is_material_change', true);
    }

    public function scopeVisibleToInvestors($query)
    {
        return $query->where('is_visible_to_investors', true)
            ->whereNotNull('investor_visible_at');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('investor_notification_priority', ['high', 'critical']);
    }
}
