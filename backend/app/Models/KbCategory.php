<?php
// V-FINAL-1730-550 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperKbCategory
 */
class KbCategory extends Model
{
    use HasFactory;
    
    protected $table = 'kb_categories';
    
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'parent_id',
        'display_order',
        'is_active',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('display_order');
    }

    /**
     * Get all articles in this category.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(KbArticle::class);
    }
}