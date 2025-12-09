<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemHealthCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'check_name',
        'status',
        'message',
        'details',
        'response_time',
        'checked_at',
    ];

    protected $casts = [
        'details' => 'array',
        'response_time' => 'integer',
        'checked_at' => 'datetime',
    ];
}
