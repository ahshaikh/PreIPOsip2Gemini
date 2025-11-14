<?php
// V-REMEDIATE-1730-148 (Created) | V-FINAL-1730-380 (Verified)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_ticket_id',
        'user_id', // This is the author
        'is_admin_reply',
        'message',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'json',
        'is_admin_reply' => 'boolean',
    ];

    /**
     * Get the ticket this message belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    /**
     * Get the user who wrote the message (aliased as 'sender').
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}