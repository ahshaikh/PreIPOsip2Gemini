<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyWebinar extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'created_by',
        'title',
        'description',
        'type',
        'scheduled_at',
        'duration_minutes',
        'meeting_link',
        'meeting_id',
        'meeting_password',
        'max_participants',
        'registered_count',
        'speakers',
        'agenda',
        'status',
        'recording_available',
        'recording_url',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'speakers' => 'array',
        'recording_available' => 'boolean',
        'duration_minutes' => 'integer',
        'max_participants' => 'integer',
        'registered_count' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(CompanyUser::class, 'created_by');
    }

    public function registrations()
    {
        return $this->hasMany(WebinarRegistration::class, 'webinar_id');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
