<?php
// V-FINAL-1730-485 (Created)

namespace App\Models;

use Illuminate{Database\Eloquent\Factories\HasFactory;
use Illuminate{Database\Eloquent\Model;

class CannedResponse extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'body', 'is_active'];
}