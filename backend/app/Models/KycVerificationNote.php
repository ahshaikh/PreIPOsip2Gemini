<?php
// V-AUDIT-MODULE2-011 (Created) - KycVerificationNote model
// Purpose: Track admin notes during KYC verification process

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycVerificationNote extends Model
{
    use HasFactory;

    protected $table = 'kyc_verification_notes';

    protected $fillable = [
        'user_kyc_id',
        'admin_id',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    /**
     * Get the KYC record that this note belongs to
     */
    public function kyc(): BelongsTo
    {
        return $this->belongsTo(UserKyc::class, 'user_kyc_id');
    }

    /**
     * Get the admin who created this note
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
