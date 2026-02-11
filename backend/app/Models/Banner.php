<?php
// V-FINAL-1730-240 (Created) | V-FINAL-1730-513 (Created) | V-FINAL-1730-517 (V2.0 Fields Added)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperBanner
 */
class Banner extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title', 'content', 'link_url', 'type', 
        'start_at', 'end_at', 'is_active', 'display_order',
        // --- NEW V2.0 Fields ---
        'trigger_type',
        'trigger_value',
        'frequency',
        'targeting_rules',
        'style_config',
        'variant_of',
        'display_weight',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
        'targeting_rules' => 'array',
        'style_config' => 'array',
    ];
    
    /**
     * Scope for active, scheduled banners.
     */
    public function scopeActive($query)
    {
        $now = now();
        return $query->where('is_active', true)
                     ->where(function($q) use ($now) {
                         $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
                     })
                     ->where(function($q) use ($now) {
                         $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
                     });
    }
}