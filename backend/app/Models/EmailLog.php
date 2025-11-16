<?php
// V-FINAL-1730-598 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory;

    // We only use created_at, not updated_at
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'template_slug',
        'to_email',
        'subject',
        'body',
        'status',
        'error_message',
    ];

    /**
     * Get the user this email was sent to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}