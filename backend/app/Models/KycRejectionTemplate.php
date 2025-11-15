<?php
// V-FINAL-1730-473 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KycRejectionTemplate extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'reason', 'is_active'];
}