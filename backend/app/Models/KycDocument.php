<?php
// V-PHASE1-1730-011 (Created) | V-FINAL-1730-329 (Logic Upgraded)| V-FINAL-1730-474 (Processing Status Added)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocument extends Model
{
    use HasFactory;

    // Constants for Validation
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    const TYPE_AADHAAR_FRONT = 'aadhaar_front';
    const TYPE_AADHAAR_BACK = 'aadhaar_back';
    const TYPE_PAN = 'pan';
    const TYPE_BANK_PROOF = 'bank_proof';
    const TYPE_DEMAT_PROOF = 'demat_proof';

    protected $fillable = [
        'user_kyc_id',
        'doc_type',
        'file_path',
        'file_name',
        'mime_type',
        'status',
	'processing_status', // <-- NEW: API status (pending, verified, failed)
        'verified_by',
        'verified_at',
        'verification_notes'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    public function kyc(): BelongsTo
    {
        return $this->belongsTo(UserKyc::class, 'user_kyc_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // --- LOGIC ---

    public static function getValidTypes(): array
    {
        return [
            self::TYPE_AADHAAR_FRONT,
            self::TYPE_AADHAAR_BACK,
            self::TYPE_PAN,
            self::TYPE_BANK_PROOF,
            self::TYPE_DEMAT_PROOF,
        ];
    }

    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }
}