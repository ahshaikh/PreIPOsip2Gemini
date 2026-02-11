<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PHASE 4 - MODEL: InvestorViewHistory
 * 
 * PURPOSE:
 * Track what investors saw and when, enabling "what's new" feature.
 * 
 * USE CASE:
 * When investor returns to company profile, platform can show:
 * "Since your last visit on Jan 5:
 *  - Financial disclosures updated (revenue increased)
 *  - New risk flag detected (cash flow negative)
 *  - Peer comparison data refreshed"
 * 
 * PRIVACY NOTE:
 * - View history is personal to each investor
 * - Used only for showing relevant changes
 * - Not shared with companies or other investors
 *
 * @mixin IdeHelperInvestorViewHistory
 */
class InvestorViewHistory extends Model
{
    use HasFactory;

    protected $table = 'investor_view_history';

    protected $fillable = [
        'user_id',
        'company_id',
        'viewed_at',
        'view_type',
        'disclosure_snapshot',
        'metrics_snapshot',
        'risk_flags_snapshot',
        'was_under_review',
        'data_as_of',
        'session_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'disclosure_snapshot' => 'array',
        'metrics_snapshot' => 'array',
        'risk_flags_snapshot' => 'array',
        'was_under_review' => 'boolean',
        'data_as_of' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
