<?php
// V-FINAL-1730-391 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'to_mobile',
        'template_slug',
        'dlt_template_id',
        'message',
        'status',
        'error_message',
        'gateway_message_id',
    ];
}