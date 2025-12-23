<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Investment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'deal_id',
        'company_id',
        'investment_code',
        'shares_allocated',
        'price_per_share',
        'total_amount',
        'status',
        'invested_at',
        'exited_at',
        'exit_price_per_share',
        'exit_amount',
        'profit_loss',
        'notes',
    ];

    protected $casts = [
        'price_per_share' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'exit_price_per_share' => 'decimal:2',
        'exit_amount' => 'decimal:2',
        'profit_loss' => 'decimal:2',
        'invested_at' => 'datetime',
        'exited_at' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // --- SCOPES ---

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExited($query)
    {
        return $query->where('status', 'exited');
    }

    // --- ACCESSORS ---

    /**
     * Calculate current value based on latest share price from deal
     */
    public function getCurrentValueAttribute()
    {
        if ($this->status === 'exited') {
            return $this->exit_amount;
        }

        // Use deal's current share price if available, otherwise use purchase price
        $currentPrice = $this->deal->share_price ?? $this->price_per_share;
        return $this->shares_allocated * $currentPrice;
    }

    /**
     * Calculate unrealized profit/loss
     */
    public function getUnrealizedProfitLossAttribute()
    {
        if ($this->status === 'exited') {
            return $this->profit_loss;
        }

        return $this->current_value - $this->total_amount;
    }

    /**
     * Calculate profit/loss percentage
     */
    public function getProfitLossPercentageAttribute()
    {
        if ($this->total_amount == 0) {
            return 0;
        }

        $profitLoss = $this->status === 'exited' ? $this->profit_loss : $this->unrealized_profit_loss;
        return ($profitLoss / $this->total_amount) * 100;
    }
}
