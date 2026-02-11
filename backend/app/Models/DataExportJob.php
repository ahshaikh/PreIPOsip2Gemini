<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperDataExportJob
 */
class DataExportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'format',
        'filters',
        'columns',
        'status',
        'file_path',
        'file_size',
        'record_count',
        'created_by',
        'started_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'file_size' => 'integer',
        'record_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
