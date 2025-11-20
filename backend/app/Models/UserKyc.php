<?php
// V-PHASE1-1730-010 (Created) | V-FINAL-1730-327 (Logic Upgraded)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserKyc extends Model
{
    use HasFactory;
    
    protected $table = 'user_kyc';

    // Centralized Validation Rules for Reuse
    const PAN_REGEX = '/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/';
    const AADHAAR_REGEX = '/^[2-9]{1}[0-9]{3}\\s[0-9]{4}\\s[0-9]{4}$|^[2-9]{1}[0-9]{11}$/'; // Matches "1234 5678 9012" or "123456789012"

    protected $fillable = [
        'user_id',
        'pan_number',
        'aadhaar_number',
        'demat_account',
        'bank_account',
        'bank_ifsc',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'submitted_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(KycDocument::class, 'user_kyc_id');
    }

    // --- LOGIC & ACCESSORS ---

    /**
     * Calculate completion percentage based on fields and docs.
     */
    public function getCompletionPercentageAttribute(): int
    {
        $totalSteps = 6; // PAN, Aadhaar, Bank, Demat, PAN Doc, Aadhaar Doc
        $completedSteps = 0;

        // 1. Text Fields
        if (!empty($this->pan_number)) $completedSteps++;
        if (!empty($this->aadhaar_number)) $completedSteps++;
        if (!empty($this->bank_account)) $completedSteps++;
        if (!empty($this->demat_account)) $completedSteps++;

        // 2. Documents (Check for existence of key types)
        // We assume documents relation is loaded or we load it
        $docs = $this->documents; 
        
        if ($docs->where('doc_type', 'pan')->count() > 0) $completedSteps++;
        if ($docs->where('doc_type', 'aadhaar_front')->count() > 0) $completedSteps++;

        if ($totalSteps == 0) return 0;
        
        return (int) round(($completedSteps / $totalSteps) * 100);
    }

    /**
     * Check if all required documents are present.
     */
    public function hasAllDocuments(): bool
    {
        $requiredTypes = ['pan', 'aadhaar_front', 'aadhaar_back', 'bank_proof'];
        $uploadedTypes = $this->documents->pluck('doc_type')->toArray();

        foreach ($requiredTypes as $type) {
            if (!in_array($type, $uploadedTypes)) {
                return false;
            }
        }
        return true;
    }
}