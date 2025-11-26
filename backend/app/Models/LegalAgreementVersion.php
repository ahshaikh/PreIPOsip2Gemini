<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalAgreementVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'legal_agreement_id',
        'version',
        'content',
        'change_summary',
        'status',
        'effective_date',
        'acceptance_count',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function legalAgreement()
    {
        return $this->belongsTo(LegalAgreement::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
