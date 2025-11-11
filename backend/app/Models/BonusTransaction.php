// V-PHASE3-1730-079
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonusTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'payment_id',
        'type',
        'amount',
        'multiplier_applied',
        'base_amount',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}