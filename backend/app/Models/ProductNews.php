<?php
// V-PRODUCT-NEWS-1210 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductNews extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'title',
        'summary',
        'content',
        'author',
        'source_url',
        'thumbnail_url',
        'published_date',
        'is_featured',
        'is_published',
        'display_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'published_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get only published news
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to get featured news
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
