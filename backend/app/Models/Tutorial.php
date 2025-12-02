<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tutorial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'content',
        'video_url',
        'thumbnail',
        'difficulty',
        'duration_minutes',
        'steps',
        'resources',
        'category',
        'tags',
        'views_count',
        'likes_count',
        'rating',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'steps' => 'array',
        'resources' => 'array',
        'tags' => 'array',
        'rating' => 'decimal:2',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function incrementViews()
    {
        $this->increment('views_count');
    }
}
