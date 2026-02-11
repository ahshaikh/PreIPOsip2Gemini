<?php
// V-FINAL-1730-241

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperRedirect
 */
class Redirect extends Model
{
    protected $fillable = ['from_url', 'to_url', 'status_code', 'is_active'];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
}