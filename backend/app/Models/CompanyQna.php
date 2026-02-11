<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperCompanyQna
 */
class CompanyQna extends Model
{
    use HasFactory;

    protected $table = 'company_qna';

    protected $fillable = [
        'company_id',
        'user_id',
        'asked_by_name',
        'asked_by_email',
        'question',
        'answer',
        'answered_by',
        'answered_at',
        'is_public',
        'is_featured',
        'helpful_count',
        'status',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'helpful_count' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answeredByUser()
    {
        return $this->belongsTo(CompanyUser::class, 'answered_by');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true)->where('status', 'answered');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
