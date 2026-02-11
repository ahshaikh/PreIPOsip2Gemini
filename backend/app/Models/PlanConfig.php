<?php
// V-PHASE2-1730-038 (Created) | V-FINAL-1730-332

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPlanConfig
 */
class PlanConfig extends Model
{
    use HasFactory;

    protected $fillable = ['plan_id', 'config_key', 'value'];

    protected $casts = [
        'value' => 'json', // Critical for storing complex rules
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}