<?php
// V-FINAL-1730-240

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title', 'content', 'image_url', 'link_url', 'type', 
        'position', 'start_at', 'end_at', 'is_active', 'display_order'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    // Scope for active banners
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function($q) {
                         $q->whereNull('start_at')->orWhere('start_at', '<=', now());
                     })
                     ->where(function($q) {
                         $q->whereNull('end_at')->orWhere('end_at', '>=', now());
                     });
    }
}