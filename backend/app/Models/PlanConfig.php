// V-PHASE2-1730-038
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanConfig extends Model
{
    use HasFactory;

    protected $fillable = ['plan_id', 'config_key', 'value'];

    protected $casts = [
        'value' => 'json',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}