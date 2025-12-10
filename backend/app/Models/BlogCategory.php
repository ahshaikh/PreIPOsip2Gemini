<?php
// V-CMS-ENHANCEMENT-003 | BlogCategory Model
// Created: 2025-12-10 | Purpose: Dynamic blog category management

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            // Only update slug if name changed and slug is based on name
            if ($category->isDirty('name') && !$category->isDirty('slug')) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Relationship: Category has many blog posts
     */
    public function blogPosts()
    {
        return $this->hasMany(BlogPost::class, 'category_id');
    }

    /**
     * Scope: Only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered by display_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Get posts count for this category
     */
    public function getPostsCountAttribute()
    {
        return $this->blogPosts()->count();
    }

    /**
     * Get published posts count for this category
     */
    public function getPublishedPostsCountAttribute()
    {
        return $this->blogPosts()->where('status', 'published')->count();
    }
}
