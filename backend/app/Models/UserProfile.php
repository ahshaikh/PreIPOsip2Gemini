// V-PHASE1-1730-009
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'dob',
        'gender',
        'address',
        'city',
        'state',
        'pincode',
        'avatar_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}