<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperWebinarRegistration
 */
class WebinarRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'webinar_id',
        'user_id',
        'attendee_name',
        'attendee_email',
        'attendee_phone',
        'questions',
        'attended',
        'attended_at',
        'status',
    ];

    protected $casts = [
        'attended' => 'boolean',
        'attended_at' => 'datetime',
    ];

    public function webinar()
    {
        return $this->belongsTo(CompanyWebinar::class, 'webinar_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }
}
