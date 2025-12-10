<?php
// V-CMS-ENHANCEMENT-011 | PageBlock Model
// Created: 2025-12-10 | Purpose: Model for flexible page building with content blocks

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBlock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'page_id',
        'type',
        'name',
        'config',
        'display_order',
        'container_width',
        'background_type',
        'background_config',
        'spacing',
        'is_active',
        'visibility',
        'variant',
        'views_count',
        'clicks_count',
    ];

    protected $casts = [
        'config' => 'array',
        'background_config' => 'array',
        'spacing' => 'array',
        'is_active' => 'boolean',
        'views_count' => 'integer',
        'clicks_count' => 'integer',
    ];

    /**
     * Relationship: Block belongs to a page
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Scope: Only active blocks
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
        return $query->orderBy('display_order', 'asc');
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by visibility
     */
    public function scopeVisibleOn($query, string $device)
    {
        return $query->where(function ($q) use ($device) {
            $q->where('visibility', 'always')
              ->orWhere('visibility', $device === 'desktop' ? 'desktop_only' : 'mobile_only');
        });
    }

    /**
     * Get block type configuration (default settings for each block type)
     */
    public static function getBlockTypeConfig(string $type): array
    {
        $configs = [
            'hero' => [
                'label' => 'Hero Section',
                'description' => 'Full-width banner with headline, subheading, and CTA buttons',
                'icon' => 'Layout',
                'fields' => ['heading', 'subheading', 'cta_primary', 'cta_secondary', 'background_image'],
            ],
            'cta' => [
                'label' => 'Call-to-Action',
                'description' => 'Prominent CTA box with button',
                'icon' => 'Megaphone',
                'fields' => ['heading', 'text', 'button_text', 'button_url', 'background_color'],
            ],
            'features' => [
                'label' => 'Features Grid',
                'description' => '2/3/4 column grid of features with icons',
                'icon' => 'Grid',
                'fields' => ['heading', 'items' /* array of {icon, title, description} */],
            ],
            'testimonials' => [
                'label' => 'Testimonials',
                'description' => 'Customer quotes with avatars',
                'icon' => 'MessageSquare',
                'fields' => ['heading', 'items' /* array of {quote, author, avatar, role} */],
            ],
            'stats' => [
                'label' => 'Stats Counter',
                'description' => 'Animated number counters',
                'icon' => 'TrendingUp',
                'fields' => ['heading', 'items' /* array of {number, label, suffix} */],
            ],
            'gallery' => [
                'label' => 'Image Gallery',
                'description' => 'Grid or masonry layout of images',
                'icon' => 'Image',
                'fields' => ['heading', 'layout', 'images' /* array of {url, alt, caption} */],
            ],
            'video' => [
                'label' => 'Video Embed',
                'description' => 'YouTube or Vimeo video',
                'icon' => 'Play',
                'fields' => ['heading', 'video_url', 'thumbnail', 'autoplay', 'loop'],
            ],
            'accordion' => [
                'label' => 'Accordion',
                'description' => 'Expandable Q&A sections',
                'icon' => 'ChevronDown',
                'fields' => ['heading', 'items' /* array of {question, answer} */],
            ],
            'tabs' => [
                'label' => 'Tabs',
                'description' => 'Tabbed content sections',
                'icon' => 'Layers',
                'fields' => ['items' /* array of {title, content} */],
            ],
            'pricing' => [
                'label' => 'Pricing Table',
                'description' => 'Plan comparison table',
                'icon' => 'DollarSign',
                'fields' => ['heading', 'plans' /* array of {name, price, features, cta} */],
            ],
            'team' => [
                'label' => 'Team Members',
                'description' => 'Staff profiles with photos',
                'icon' => 'Users',
                'fields' => ['heading', 'members' /* array of {name, role, photo, bio, socials} */],
            ],
            'logos' => [
                'label' => 'Logo Cloud',
                'description' => 'Partner/client logo showcase',
                'icon' => 'Image',
                'fields' => ['heading', 'logos' /* array of {url, alt, link} */],
            ],
            'timeline' => [
                'label' => 'Timeline',
                'description' => 'Event history with dates',
                'icon' => 'Clock',
                'fields' => ['heading', 'events' /* array of {date, title, description} */],
            ],
            'newsletter' => [
                'label' => 'Newsletter Signup',
                'description' => 'Email capture form',
                'icon' => 'Mail',
                'fields' => ['heading', 'text', 'placeholder', 'button_text', 'success_message'],
            ],
            'social' => [
                'label' => 'Social Media Feed',
                'description' => 'Instagram/Twitter feed embed',
                'icon' => 'Share2',
                'fields' => ['heading', 'platform', 'feed_url', 'count'],
            ],
            'richtext' => [
                'label' => 'Rich Text',
                'description' => 'WYSIWYG content editor',
                'icon' => 'FileText',
                'fields' => ['content' /* HTML content */],
            ],
        ];

        return $configs[$type] ?? [
            'label' => 'Unknown Block',
            'description' => 'Unknown block type',
            'icon' => 'Square',
            'fields' => [],
        ];
    }

    /**
     * Get all available block types
     */
    public static function getAvailableBlockTypes(): array
    {
        return [
            'hero',
            'cta',
            'features',
            'testimonials',
            'stats',
            'gallery',
            'video',
            'accordion',
            'tabs',
            'pricing',
            'team',
            'logos',
            'timeline',
            'newsletter',
            'social',
            'richtext',
        ];
    }

    /**
     * Increment views count
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Increment clicks count
     */
    public function incrementClicks(): void
    {
        $this->increment('clicks_count');
    }
}
