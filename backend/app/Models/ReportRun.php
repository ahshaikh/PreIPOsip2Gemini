<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperReportRun
 */
class ReportRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheduled_report_id',
        'status',
        'file_path',
        'file_size',
        'error_details',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'error_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function scheduledReport()
    {
        return $this->belongsTo(ScheduledReport::class);
    }
}
