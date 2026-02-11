<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperApiTestResult
 */
class ApiTestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_case_id',
        'status',
        'response_time',
        'status_code',
        'response_body',
        'error_message',
        'executed_by',
    ];

    protected $casts = [
        'response_body' => 'array',
        'response_time' => 'integer',
        'status_code' => 'integer',
    ];

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(ApiTestCase::class, 'test_case_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
