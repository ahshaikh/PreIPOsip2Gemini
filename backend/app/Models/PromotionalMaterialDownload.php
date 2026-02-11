<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPromotionalMaterialDownload
 */
class PromotionalMaterialDownload extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'promotional_material_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the user who downloaded
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the promotional material
     */
    public function promotionalMaterial(): BelongsTo
    {
        return $this->belongsTo(PromotionalMaterial::class);
    }
}
