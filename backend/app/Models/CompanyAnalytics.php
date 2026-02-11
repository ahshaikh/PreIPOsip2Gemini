<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperCompanyAnalytics
 */
class CompanyAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'date',
        'profile_views',
        'document_downloads',
        'financial_report_downloads',
        'deal_views',
        'investor_interest_clicks',
        'viewer_demographics',
    ];

    protected $casts = [
        'date' => 'date',
        'viewer_demographics' => 'array',
        'profile_views' => 'integer',
        'document_downloads' => 'integer',
        'financial_report_downloads' => 'integer',
        'deal_views' => 'integer',
        'investor_interest_clicks' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Increment a specific metric
     */
    public static function incrementMetric($companyId, $metric)
    {
        $today = now()->toDateString();

        $analytics = self::firstOrCreate(
            ['company_id' => $companyId, 'date' => $today],
            [
                'profile_views' => 0,
                'document_downloads' => 0,
                'financial_report_downloads' => 0,
                'deal_views' => 0,
                'investor_interest_clicks' => 0,
            ]
        );

        $analytics->increment($metric);
    }
}
