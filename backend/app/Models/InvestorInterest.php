<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperInvestorInterest
 */
class InvestorInterest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'investor_email',
        'investor_name',
        'investor_phone',
        'interest_level',
        'investment_range_min',
        'investment_range_max',
        'message',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'investment_range_min' => 'decimal:2',
        'investment_range_max' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeQualified($query)
    {
        return $query->where('status', 'qualified');
    }
}
