// V-PHASE2-1730-045
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'subject', 'body', 'variables'];
    protected $casts = ['variables' => 'json'];
}