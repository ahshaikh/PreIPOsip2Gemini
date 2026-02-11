<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperLegalAgreementAuditTrail
 */
class LegalAgreementAuditTrail extends Model
{
    use HasFactory;

    protected $table = 'legal_agreement_audit_trail';

    protected $fillable = [
        'legal_agreement_id',
        'event_type',
        'description',
        'changes',
        'version',
        'user_id',
        'user_name',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function legalAgreement()
    {
        return $this->belongsTo(LegalAgreement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
