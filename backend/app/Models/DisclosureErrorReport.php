<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PHASE 3 - MODEL: DisclosureErrorReport
 * 
 * PURPOSE:
 * Tracks self-reported errors/omissions in approved disclosures.
 * Treats issuer honesty as a POSITIVE signal, not a penalty.
 * 
 * WORKFLOW:
 * 1. Company discovers error in approved disclosure
 * 2. Company reports error (this record created)
 * 3. System creates NEW draft with corrections (doesn't modify approved)
 * 4. Admin is notified
 * 5. New draft goes through normal review process
 * 
 * SAFEGUARDS:
 * - Original approved data is preserved
 * - All corrections are transparent
 * - Admin can see what changed and why
 * - Self-reporting is encouraged, not punished
 *
 * @property int $id
 * @property int $company_disclosure_id Original approved disclosure
 * @property int $company_id
 * @property int $reported_by User who reported error
 * @property \Illuminate\Support\Carbon $reported_at
 * @property string $error_description What was wrong
 * @property string $correction_reason Why correction needed
 * @property array $original_data Snapshot of approved data
 * @property array $corrected_data Proposed corrected data
 * @property string|null $admin_notes Admin response to error report
 * @property string|null $admin_reviewed_by
 * @property \Illuminate\Support\Carbon|null $admin_reviewed_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin IdeHelperDisclosureErrorReport
 */
class DisclosureErrorReport extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'disclosure_error_reports';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_disclosure_id',
        'company_id',
        'reported_by',
        'reported_at',
        'error_description',
        'correction_reason',
        'original_data',
        'corrected_data',
        'admin_notes',
        'admin_reviewed_by',
        'admin_reviewed_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'reported_at' => 'datetime',
        'original_data' => 'array',
        'corrected_data' => 'array',
        'admin_reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Original approved disclosure with error
     */
    public function originalDisclosure()
    {
        return $this->belongsTo(CompanyDisclosure::class, 'company_disclosure_id');
    }

    /**
     * Company that reported the error
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * User who reported the error
     */
    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Admin who reviewed the error report
     */
    public function adminReviewedBy()
    {
        return $this->belongsTo(User::class, 'admin_reviewed_by');
    }

    /**
     * New draft disclosure created from this error report
     */
    public function newDraft()
    {
        return CompanyDisclosure::where('error_report_id', $this->id)->first();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to unreviewed error reports
     */
    public function scopeUnreviewed($query)
    {
        return $query->whereNull('admin_reviewed_at');
    }

    /**
     * Scope to error reports by company
     */
    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to recent error reports
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('reported_at', '>=', now()->subDays($days));
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    /**
     * Mark error report as reviewed by admin
     */
    public function markAsReviewed(int $adminId, ?string $notes = null): void
    {
        $this->admin_reviewed_by = $adminId;
        $this->admin_reviewed_at = now();
        $this->admin_notes = $notes;
        $this->save();
    }

    /**
     * Get diff between original and corrected data
     */
    public function getDiff(): array
    {
        $changes = [];

        foreach ($this->corrected_data as $key => $value) {
            if (!isset($this->original_data[$key]) || $this->original_data[$key] !== $value) {
                $changes[$key] = [
                    'field' => $key,
                    'old' => $this->original_data[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }

    /**
     * Check if error report has been reviewed
     */
    public function isReviewed(): bool
    {
        return !is_null($this->admin_reviewed_at);
    }
}
