<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ReferralTransaction Model
 * * [AUDIT FIX]: Tracks commissions earned through the affiliate tree.
 */
class ReferralTransaction extends Model
{
    protected $fillable = [
        'referrer_id', 
        'referee_id', 
        'investment_id', 
        'amount_paise', 
        'level', 
        'status'
    ];

    protected $casts = [
        'amount_paise' => 'integer',
        'status' => 'string',
    ];

    public function referrer() {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referee() {
        return $this->belongsTo(User::class, 'referee_id');
    }
}