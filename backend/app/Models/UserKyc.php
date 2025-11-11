<?php
// V-PHASE1-1730-010

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserKyc extends Model
{
    use HasFactory;
    
    protected $table = 'user_kyc';

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }
}