<?php
// V-FINAL-1730-283 | V-CMS-ENHANCEMENT-004 (Updated)
// Updated: 2025-12-10 | Added category relationship and new fields

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperBlogPost
 */
class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'author_id',
        'category_id',
        'category', // Old string-based category (backward compatibility)
        'status',
        'is_featured',
        'seo_title',
        'seo_description',
        'tags',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }

            // Auto-set published_at when status changes to published
            if ($post->status === 'published' && empty($post->published_at)) {
                $post->published_at = now();
            }
        });

        static::updating(function ($post) {
            // Update slug if title changed
            if ($post->isDirty('title') && !$post->isDirty('slug')) {
                $post->slug = Str::slug($post->title);
            }

            // Set published_at when publishing for first time
            if ($post->isDirty('status') && $post->status === 'published' && empty($post->published_at)) {
                $post->published_at = now();
            }

            // Clear published_at when unpublishing
            if ($post->isDirty('status') && $post->status === 'draft') {
                $post->published_at = null;
            }
        });
    }

    /**
     * Relationship: Post belongs to author (User)
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Relationship: Post belongs to category
     */
    public function blogCategory()
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    /**
     * Scope: Only published posts
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    /**
     * Scope: Only featured posts
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Search by title or content
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%")
              ->orWhere('excerpt', 'like', "%{$search}%");
        });
    }

    /**
     * Get the category name (supports both old and new system)
     */
    public function getCategoryNameAttribute()
    {
        if ($this->blogCategory) {
            return $this->blogCategory->name;
        }
        return $this->category ?? 'Uncategorized';
    }
}