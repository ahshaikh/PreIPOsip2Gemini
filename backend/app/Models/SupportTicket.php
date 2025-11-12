<?php
// V-REMEDIATE-1730-147

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ticket_code',
        'subject',
        'category',
        'priority',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the user who owns the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all messages for the ticket.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the admin who resolved the ticket.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}