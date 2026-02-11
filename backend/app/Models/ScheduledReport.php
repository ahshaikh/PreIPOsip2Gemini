<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperScheduledReport
 */
class ScheduledReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'report_type',
        'frequency',
        'parameters',
        'recipients',
        'format',
        'is_active',
        'last_run_at',
        'next_run_at',
        'created_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function runs()
    {
        return $this->hasMany(ReportRun::class);
    }

    public function latestRun()
    {
        return $this->hasOne(ReportRun::class)->latestOfMany();
    }
}
