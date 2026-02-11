<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPrivacyRequest
 */
class PrivacyRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type', // 'export', 'deletion', 'rectification'
        'status', // 'pending', 'processing', 'completed', 'failed'
        'requested_at',
        'completed_at',
        'details', // JSON for any specific details (e.g. download link)
        'ip_address',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}