<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiTestCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'method',
        'endpoint',
        'headers',
        'body',
        'expected_response',
        'expected_status_code',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'headers' => 'array',
        'body' => 'array',
        'expected_response' => 'array',
        'expected_status_code' => 'integer',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ApiTestResult::class, 'test_case_id');
    }
}
