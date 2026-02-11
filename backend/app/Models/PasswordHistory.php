<?php
// V-FINAL-1730-441 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPasswordHistory
 */
class PasswordHistory extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'password_hash'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}