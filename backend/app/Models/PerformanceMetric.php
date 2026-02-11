<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPerformanceMetric
 */
class PerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric_type',
        'endpoint',
        'value',
        'unit',
        'metadata',
        'recorded_at',
    ];

    protected $casts = [
        'value' => 'float',
        'metadata' => 'array',
        'recorded_at' => 'datetime',
    ];
}
