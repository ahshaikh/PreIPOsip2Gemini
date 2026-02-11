<?php
// V-PHASE2-1730-046


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperSmsTemplate
 */
class SmsTemplate extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'body', 'dlt_template_id'];
}