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
        'legal_agreement_id',
        'document_type',
        'page_version',
        'accepted_version',
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

    /**
     * Get the legal agreement that was accepted.
     */
    public function legalAgreement(): BelongsTo
    {
        return $this->belongsTo(LegalAgreement::class);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by document type
     */
    public function scopeForDocument($query, $documentType)
    {
        return $query->where('document_type', $documentType);
    }
}
