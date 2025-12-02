<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'title',
        'slug',
        'description',
        'company_name',
        'company_logo',
        'sector',
        'deal_type',
        'min_investment',
        'max_investment',
        'valuation',
        'valuation_currency',
        'share_price',
        'total_shares',
        'available_shares',
        'deal_opens_at',
        'deal_closes_at',
        'days_remaining',
        'highlights',
        'documents',
        'video_url',
        'status',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'min_investment' => 'decimal:2',
        'max_investment' => 'decimal:2',
        'valuation' => 'decimal:2',
        'share_price' => 'decimal:2',
        'deal_opens_at' => 'datetime',
        'deal_closes_at' => 'datetime',
        'highlights' => 'array',
        'documents' => 'array',
        'is_featured' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeLive($query)
    {
        return $query->where('deal_type', 'live')
                    ->where('status', 'active')
                    ->where('deal_opens_at', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('deal_closes_at')
                          ->orWhere('deal_closes_at', '>', now());
                    });
    }

    public function scopeUpcoming($query)
    {
        return $query->where('deal_type', 'upcoming')
                    ->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                    ->where('status', 'active');
    }
}
