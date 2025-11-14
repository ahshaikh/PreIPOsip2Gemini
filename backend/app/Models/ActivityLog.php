<?php
// V-PHASE1-1730-012 (Created) | V-FINAL-1730-405 (Logic Upgraded)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ActivityLog extends Model
{
    use HasFactory;

    // We disable 'updated_at' for logs. They are write-once.
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',     // <-- NEW
        'target_type',    // <-- NEW
        'target_id',      // <-- NEW
        'old_values',     // <-- NEW
        'new_values',     // <-- NEW
    ];

    protected $casts = [
        'old_values' => 'array', // Automatically cast JSON to Array
        'new_values' => 'array', // Automatically cast JSON to Array
    ];

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model that this log entry pertains to (e.g., a Payment or User).
     */
    public function target()
    {
        return $this->morphTo();
    }

    // --- SCOPES ---

    /**
     * Filter logs by a specific date range.
     */
    public function scopeDateRange(Builder $query, string $startDate, string $endDate): void
    {
        $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}