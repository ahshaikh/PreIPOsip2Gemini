// V-PHASE2-1730-037
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'monthly_amount',
        'duration_months',
        'description',
        'is_active',
        'is_featured',
        'display_order',
    ];

    public function configs(): HasMany
    {
        return $this->hasMany(PlanConfig::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }
}