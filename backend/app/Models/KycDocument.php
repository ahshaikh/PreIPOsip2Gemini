// V-PHASE1-1730-011
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_kyc_id',
        'doc_type',
        'file_path',
        'file_name',
        'mime_type',
    ];

    public function kyc(): BelongsTo
    {
        return $this->belongsTo(UserKyc::class, 'user_kyc_id');
    }
}