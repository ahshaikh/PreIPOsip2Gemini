<?php
// V-PHASE2-1730-042 (Created) | V-FINAL-1730-558 (Versioning Added)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany; // <-- IMPORT

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'seo_meta',
        'status',
        'current_version', // <-- NEW
        'require_user_acceptance', // <-- NEW
    ];

    protected $casts = [
        'content' => 'array',
        'seo_meta' => 'array',
        'require_user_acceptance' => 'boolean', // <-- NEW
    ];

    // --- RELATIONSHIPS ---

    /**
     * Get all historical versions of this page.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class)->orderBy('version', 'desc');
    }

    /**
     * Get the user acceptances for this page.
     */
    public function acceptances(): HasMany
    {
        return $this->hasMany(UserLegalAcceptance::class);
    }

    /**
     * Get all content blocks for this page (V-CMS-ENHANCEMENT-011)
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('display_order');
    }

    /**
     * Get only active blocks for this page
     */
    public function activeBlocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->where('is_active', true)->orderBy('display_order');
    }
}