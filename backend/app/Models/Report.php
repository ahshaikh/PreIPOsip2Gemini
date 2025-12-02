<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'type',
        'file_path',
        'cover_image',
        'file_size',
        'pages',
        'access_level',
        'requires_subscription',
        'author',
        'published_date',
        'tags',
        'downloads_count',
        'rating',
        'status',
    ];

    protected $casts = [
        'published_date' => 'datetime',
        'tags' => 'array',
        'requires_subscription' => 'boolean',
        'rating' => 'decimal:2',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopePublicAccess($query)
    {
        return $query->where('access_level', 'public');
    }

    public function incrementDownloads()
    {
        $this->increment('downloads_count');
    }
}
