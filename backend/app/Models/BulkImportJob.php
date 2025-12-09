<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkImportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'filename',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'errors',
        'notes',
        'created_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'successful_rows' => 'integer',
        'failed_rows' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
