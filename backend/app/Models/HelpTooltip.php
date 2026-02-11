<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperHelpTooltip
 */
class HelpTooltip extends Model
{
    use HasFactory;

    protected $fillable = [
        'element_id',
        'title',
        'content',
        'position',
        'page_url',
        'user_role',
        'conditions',
        'icon',
        'image_url',
        'video_url',
        'show_once',
        'dismissible',
        'auto_hide_seconds',
        'priority',
        'learn_more_url',
        'cta_text',
        'cta_url',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'show_once' => 'boolean',
        'dismissible' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'auto_hide_seconds' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPage($query, string $url)
    {
        return $query->where(function ($q) use ($url) {
            $q->where('page_url', $url)
              ->orWhere('page_url', 'like', '%*%')
              ->orWhereNull('page_url');
        });
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where(function ($q) use ($role) {
            $q->where('user_role', $role)
              ->orWhere('user_role', 'all');
        });
    }
}
