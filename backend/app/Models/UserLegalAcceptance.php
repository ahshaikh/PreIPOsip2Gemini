<?php
// V-MODEL-FIX (Created - was referenced in migration but missing)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLegalAcceptance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'page_id',
        'accepted_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    /**
     * Get the user who accepted the legal document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the legal page that was accepted.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
